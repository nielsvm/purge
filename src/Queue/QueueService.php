<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueService.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\PurgeableServiceInterface;
use Drupal\purge\Purgeable\PurgeableInterface;
use Drupal\purge\Queue\Exception\UnexpectedServiceConditionException;
use Drupal\purge\Queue\Exception\InvalidQueueConfiguredException;
use Drupal\purge\Queue\QueueInterface;

/**
 * Provides the service that lets purgeables interact with the underlying queue.
 */
class QueueService extends ServiceBase implements QueueServiceInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $serviceContainer;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The service that generates purgeable objects on-demand.
   *
   * @var \Drupal\purge\Purgeable\PurgeableServiceInterface
   */
  protected $purgePurgeables;

  /**
   * The Queue (plugin) object in which all items are stored.
   *
   * @var \Drupal\purge\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The transaction buffer used to temporarily park purgeable objects.
   */
  protected $buffer;

  /**
   * Instantiate the queue service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Purgeable\PurgeableServiceInterface $purge_purgeables
   *   The service that instantiates purgeable objects for claimed queue items.
   */
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, ConfigFactoryInterface $config_factory, PurgeableServiceInterface $purge_purgeables) {
    $this->pluginManager = $pluginManager;
    $this->serviceContainer = $service_container;
    $this->configFactory = $config_factory;
    $this->purgePurgeables = $purge_purgeables;

    // Initialize the queue plugin as configured in purge.queue.yml.
    $this->initializeQueue();

    // Initialize the transaction buffer as empty.
    $this->buffer = array();
  }

  /**
   * Commit the queue buffer upon service destruction.
   */
  public function __destruct() {

    // The queue service attempts to collect all actions done for purgeables
    // in $this->buffer, and commits them as infrequent as possible during
    // runtime. At minimum it will commit to the underlying queue plugin upon
    // shutdown and by doing so, attempts to reduce and bundle the amount of
    // work the queue has to do (e.g., queries, disk writes, mallocs). This
    // helps purge to scale better and should cause no noticeable side-effects.
    $this->commit();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (!$simple) {
      return $this->pluginManager->getDefinitions();
    }
    $plugins = array();
    foreach ($this->pluginManager->getDefinitions() as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['label'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsLoaded() {
    static $plugin_id;
    if (is_null($plugin_id)) {

      // The queue service always interacts with just one underlying queue,
      // which is stored in configuration. Configuring a queue plugin that
      // does not exist, will cause a InvalidQueueConfiguredException thrown.
      $plugin_id = $this->configFactory->get('purge.queue')->get('plugin');

      // Test if the configuration returned is valid.
      if (is_null($plugin_id) || !is_scalar($plugin_id)) {
        throw new InvalidQueueConfiguredException(
          "The purge.queue configuration key 'plugin' seems missing.");
      }

      // Test if the configured queue is a valid and existing queue plugin.
      if (is_null($this->pluginManager->getDefinition($plugin_id))) {
        throw new InvalidQueueConfiguredException(
          "The queue plugin '$plugin_id' does not exist.");
      }
    }
    return array($plugin_id);
  }

  /**
   * Load the queue plugin and make $this->queue available.
   */
  protected function initializeQueue() {
    if (!is_null($this->queue)) {
      return;
    }

    // Lookup the plugin ID, definition and class from the discoverer.
    $plugin_id = current($this->getPluginsLoaded());
    $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // Retrieve all the requested service arguments.
    $arguments = array();
    foreach ($plugin_definition['service_dependencies'] as $service) {
      $arguments[] = $this->serviceContainer->get($service);
    }

    // Use the Reflection API to instantiate our plugin.
    $reflector = new \ReflectionClass($plugin_class);
    $this->queue = $reflector->newInstanceArgs($arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function add(PurgeableInterface $purgeable) {
    $duplicate = FALSE;
    foreach ($this->buffer as $bufferedPurgeable) {
      if ($purgeable->data === $bufferedPurgeable->data) {
        $duplicate = TRUE;
        break;
      }
    }
    if (!$duplicate) {
      $purgeable->setState(PurgeableInterface::STATE_ADDING);
      $this->buffer[] = $purgeable;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addMultiple(array $purgeables) {
    foreach ($purgeables as $purgeable) {
      $duplicate = FALSE;
      foreach ($this->buffer as $bufferedPurgeable) {
        if ($purgeable->data === $bufferedPurgeable->data) {
          $duplicate = TRUE;
          break;
        }
      }
      if (!$duplicate) {
        $purgeable->setState(PurgeableInterface::STATE_ADDING);
        $this->buffer[] = $purgeable;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claim($lease_time = 3600) {
    $this->commitAdding();
    $this->commitReleasing();

    // Claim the raw item from the queue, or cancel the call.
    if (!($item = $this->queue->claimItem($lease_time))) {
      return FALSE;
    }

    // Lookup if this item is accidentally in our local buffer.
    $match = NULL;
    foreach ($this->buffer as $purgeable) {
      if ($purgeable->item_id === $item->item_id) {
        $match = $purgeable;
        break;
      }
    }

    // If a locally buffered purgeable object was found, update and return it.
    if ($match) {
      $match->setState(PurgeableInterface::STATE_CLAIMED);
      $match->setQueueItemInfo($item->item_id, $item->created);
      return $match;
    }

    // If the item was not locally buffered (usually), instantiate one.
    else {
      $purgeable = $this->purgePurgeables->fromQueueItemData($item->data);
      $purgeable->setState(PurgeableInterface::STATE_CLAIMED);
      $purgeable->setQueueItemInfo($item->item_id, $item->created);
      $this->buffer[] = $purgeable;
      return $purgeable;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimMultiple($claims = 10, $lease_time = 3600) {
    $this->commitAdding();
    $this->commitReleasing();

    // Claim multiple (raw) items from the queue, return if its empty.
    if (!($items = $this->queue->claimItemMultiple($claims, $lease_time))) {
      return array();
    }

    // Iterate the $items array and replace each with full instances.
    foreach ($items as $i => $item) {

      // See if this claimed item is locally available as purgeable object.
      $match = NULL;
      foreach ($this->buffer as $purgeable) {
        if ($purgeable->item_id === $item->item_id) {
          $match = $purgeable;
          break;
        }
      }

      // If a match was found, update and overwrite the object in $items.
      if ($match) {
        $match->setState(PurgeableInterface::STATE_CLAIMED);
        $match->setQueueItemInfo($item->item_id, $item->created);
        $items[$i] = $match;
      }

      // If the item was not locally buffered (usually), instantiate one.
      else {
        $purgeable = $this->purgePurgeables->fromQueueItemData($item->data);
        $purgeable->setState(PurgeableInterface::STATE_CLAIMED);
        $purgeable->setQueueItemInfo($item->item_id, $item->created);
        $this->buffer[] = $purgeable;
        $items[$i] = $purgeable;
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function release(PurgeableInterface $purgeable) {
    $this->bufferSetState(PurgeableInterface::STATE_RELEASING, array($purgeable));
  }

  /**
   * {@inheritdoc}
   */
  public function releaseMultiple(array $purgeables) {
    $this->bufferSetState(PurgeableInterface::STATE_RELEASING, $purgeables);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(PurgeableInterface $purgeable) {
    $this->bufferSetState(PurgeableInterface::STATE_DELETING, array($purgeable));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $purgeables) {
    $this->bufferSetState(PurgeableInterface::STATE_DELETING, $purgeables);
  }

  /**
   * {@inheritdoc}
   */
  function emptyQueue() {
    $this->bufferSetState(PurgeableInterface::STATE_DELETED, $this->buffer);
    $this->queue->deleteQueue();
    $this->buffer = array();
  }

  /**
   * Only retrieve items from the buffer in a particular given state.
   *
   * @param array $states
   *   A non-associative array containing one or more state constants as
   *   found under PurgeableInterface::STATE_*.
   *
   * @return
   *   Returns a non-associative array with purgeable objects, but only those
   *   that matched the given states requested.
   */
  private function bufferGetFiltered(array $states) {
    $results = array();
    foreach ($this->buffer as $purgeable) {
      if (in_array($purgeable->getState(), $states)) {
        $results[] = $purgeable;
      }
    }
    return $results;
  }

  /**
   * Set a certain state on each purgeable in the given array.
   *
   * @param $state
   *   Integer matching to any of the PurgeableInterface::STATE_* constants.
   * @param array $purgeables
   *   A non-associative array with \Drupal\purge\Purgeable\PurgeableInterface
   *   objects that need the given state applied.
   * @param bool $checkid
   *   If TRUE, only change the state on objects that have a non-NULL item_id.
   */
  private function bufferSetState($state, array $purgeables, $checkid = TRUE) {
    foreach ($purgeables as $purgeable) {
      if ($checkid && is_null($purgeable->item_id)) {
        continue;
      }
      $buffered = FALSE;
      foreach ($this->buffer as $bufferedPurgeable) {
        if ($purgeable->item_id === $bufferedPurgeable->item_id) {
          $buffered = TRUE;
          break;
        }
      }
      if (!$buffered) {
        $this->buffer[] = $purgeable;
      }
      $purgeable->setState($state);
    }
  }

  /**
   * Commit all actions in the internal buffer to the queue.
   */
  public function commit() {
    if (empty($this->buffer)) {
      return;
    }
    $this->commitAdding();
    $this->commitReleasing();
    $this->commitDeleting();
  }

  /**
   * Commit all adding purgeables in the buffer to the queue.
   */
  private function commitAdding() {
    $items = $this->bufferGetFiltered(array(PurgeableInterface::STATE_ADDING));
    if (empty($items)) {
      return;
    }
    else {

      // Add just one item to the queue using createItem() on the queue.
      if (count($items) === 1) {
        $purgeable = current($items);
        if (!($id = $this->queue->createItem($purgeable->data))) {
          throw new UnexpectedServiceConditionException(
            "The queue returned FALSE on createItem().");
        }
        else {
          $purgeable->setQueueItemId($id);
          $purgeable->setQueueItemCreated(time());
          $purgeable->setState(PurgeableInterface::STATE_ADDED);
        }
      }

      // Add multiple at once to the queue using createItemMultiple() on the queue.
      else {
        $data_items = array();
        foreach ($items as $purgeable) {
          $data_items[] = $purgeable->data;
        }
        if (!($ids = $this->queue->createItemMultiple($data_items))) {
          throw new UnexpectedServiceConditionException(
            "The queue returned FALSE on createItemMultiple().");
        }
        foreach ($items as $purgeable) {
          if (!isset($i)) {
            $i = 0;
          }
          else {
            $i++;
          }
          $purgeable->setQueueItemId($ids[$i]);
          $purgeable->setQueueItemCreated(time());
          $purgeable->setState(PurgeableInterface::STATE_ADDED);
        }
      }
    }
  }

  /**
   * Commit all releasing purgeables in the buffer to the queue.
   */
  private function commitReleasing() {
    $items = $this->bufferGetFiltered(array(PurgeableInterface::STATE_RELEASING));
    if (empty($items)) {
      return;
    }
    else {

      // Release just one item back to the queue.
      if (count($items) === 1) {
        $purgeable = current($items);
        $this->queue->releaseItem($purgeable);
        $purgeable->setState(PurgeableInterface::STATE_RELEASED);
      }

      // Release multiple items at once back to the queue.
      else {
        $this->queue->releaseItemMultiple($items);
        foreach ($items as $purgeable) {
          $purgeable->setState(PurgeableInterface::STATE_RELEASED);
        }
      }
    }
  }

  /**
   * Commit all deleting purgeables in the buffer to the queue.
   */
  private function commitDeleting() {
    $items = $this->bufferGetFiltered(array(PurgeableInterface::STATE_DELETING));
    if (empty($items)) {
      return;
    }
    else {

      // Delete only one item from the queue.
      if (count($items) === 1) {
        $purgeable = current($items);
        $this->queue->deleteItem($purgeable);
        $purgeable->setState(PurgeableInterface::STATE_DELETED);
        foreach ($this->buffer as $i => $bufferedPurgeable) {
          if ($purgeable->item_id === $bufferedPurgeable->item_id) {
            unset($this->buffer[$i]);
          }
        }
      }

      // Delete multiple items at once from the queue.
      else {
        $this->queue->deleteItemMultiple($items);
        foreach ($items as $purgeable) {
          $purgeable->setState(PurgeableInterface::STATE_DELETED);
          foreach ($this->buffer as $i => $bufferedPurgeable) {
            if ($purgeable->item_id === $bufferedPurgeable->item_id) {
              unset($this->buffer[$i]);
            }
          }
        }
      }
    }
  }
}
