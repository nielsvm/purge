<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\Service.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\ServiceInterface as PurgeableService;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Queue\Exception\UnexpectedServiceConditionException;
use Drupal\purge\Queue\PluginInterface;

/**
 * Provides the service that lets purgeables interact with the underlying queue.
 */
class Service extends ServiceBase implements ServiceInterface, DestructableInterface {

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
   * @var \Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgePurgeableFactory;

  /**
   * The Queue (plugin) object in which all items are stored.
   *
   * @var \Drupal\purge\Queue\PluginInterface
   */
  protected $queue;

  /**
   * The transaction buffer used to temporarily park purgeable objects.
   */
  protected $buffer;

  /**
   * The plugin ID of the fallback backend.
   */
  const FALLBACK_PLUGIN = 'null';

  /**
   * Instantiate the queue service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Purgeable\ServiceInterface $purge_purgeable_factory
   *   The service that instantiates purgeable objects for claimed queue items.
   */
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, ConfigFactoryInterface $config_factory, PurgeableService $purge_purgeable_factory) {
    $this->pluginManager = $pluginManager;
    $this->serviceContainer = $service_container;
    $this->configFactory = $config_factory;
    $this->purgePurgeableFactory = $purge_purgeable_factory;

    // Initialize the queue plugin as configured.
    $this->initializeQueue();

    // Initialize the transaction buffer as empty.
    $this->buffer = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (empty($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
      unset($this->plugins[SELF::FALLBACK_PLUGIN]);
    }
    if (!$simple) {
      return $this->plugins;
    }
    $plugins = [];
    foreach ($this->plugins as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['label'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (empty($this->plugins_enabled)) {
      $plugin_ids = array_keys($this->getPlugins());

      // The queue service always interacts with just one underlying queue,
      // which is stored in configuration. When configuration is invalid - which
      // for instance occurs during module installation - we use the fallback.
      $plugin_id = $this->configFactory->get('purge.plugins')->get('queue');
      if (is_null($plugin_id) || !in_array($plugin_id, $plugin_ids)) {
        $this->plugins_enabled[] = SELF::FALLBACK_PLUGIN;
      }
      else {
        $this->plugins_enabled[] = $plugin_id;
      }
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->commit();
    parent::reload();
    $this->buffer = [];
    $this->queue = NULL;
    $this->initializeQueue();
  }

  /**
   * Load the queue plugin and make $this->queue available.
   */
  protected function initializeQueue() {
    if (!is_null($this->queue)) {
      return;
    }

    // Lookup the plugin ID and instantiate the queue.
    $plugin_id = current($this->getPluginsEnabled());
    $this->queue = $this->pluginManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function add(Purgeable $purgeable) {
    $duplicate = FALSE;
    foreach ($this->buffer as $bufferedPurgeable) {
      if ($purgeable->data === $bufferedPurgeable->data) {
        $duplicate = TRUE;
        break;
      }
    }
    if (!$duplicate) {
      $purgeable->setState(Purgeable::STATE_ADDING);
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
        $purgeable->setState(Purgeable::STATE_ADDING);
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
      $match->setState(Purgeable::STATE_CLAIMED);
      $match->setQueueItemInfo($item->item_id, $item->created);
      return $match;
    }

    // If the item was not locally buffered (usually), instantiate one.
    else {
      $purgeable = $this->purgePurgeableFactory->fromQueueItemData($item->data);
      $purgeable->setState(Purgeable::STATE_CLAIMED);
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
      return [];
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
        $match->setState(Purgeable::STATE_CLAIMED);
        $match->setQueueItemInfo($item->item_id, $item->created);
        $items[$i] = $match;
      }

      // If the item was not locally buffered (usually), instantiate one.
      else {
        $purgeable = $this->purgePurgeableFactory->fromQueueItemData($item->data);
        $purgeable->setState(Purgeable::STATE_CLAIMED);
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
  public function release(Purgeable $purgeable) {
    $this->bufferSetState(Purgeable::STATE_RELEASING, [$purgeable]);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseMultiple(array $purgeables) {
    $this->bufferSetState(Purgeable::STATE_RELEASING, $purgeables);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(Purgeable $purgeable) {
    $this->bufferSetState(Purgeable::STATE_DELETING, [$purgeable]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $purgeables) {
    $this->bufferSetState(Purgeable::STATE_DELETING, $purgeables);
  }

  /**
   * {@inheritdoc}
   */
  function emptyQueue() {
    $this->bufferSetState(Purgeable::STATE_DELETED, $this->buffer);
    $this->queue->deleteQueue();
    $this->buffer = [];
  }

  /**
   * Only retrieve items from the buffer in a particular given state.
   *
   * @param array $states
   *   A non-associative array containing one or more state constants as
   *   found under \Drupal\purge\Purgeable\PluginInterface::STATE_*.
   *
   * @return
   *   Returns a non-associative array with purgeable objects, but only those
   *   that matched the given states requested.
   */
  private function bufferGetFiltered(array $states) {
    $results = [];
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
   *   Integer matching to any of the \Drupal\purge\Purgeable
   *   \PluginInterface::STATE_* constants.
   * @param array $purgeables
   *   A non-associative array with \Drupal\purge\Purgeable\PluginInterface
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
    $items = $this->bufferGetFiltered([Purgeable::STATE_ADDING]);
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
          $purgeable->setState(Purgeable::STATE_ADDED);
        }
      }

      // Add multiple at once to the queue using createItemMultiple() on the queue.
      else {
        $data_items = [];
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
          $purgeable->setState(Purgeable::STATE_ADDED);
        }
      }
    }
  }

  /**
   * Commit all releasing purgeables in the buffer to the queue.
   */
  private function commitReleasing() {
    $items = $this->bufferGetFiltered([Purgeable::STATE_RELEASING]);
    if (empty($items)) {
      return;
    }
    else {

      // Release just one item back to the queue.
      if (count($items) === 1) {
        $purgeable = current($items);
        $this->queue->releaseItem($purgeable);
        $purgeable->setState(Purgeable::STATE_RELEASED);
      }

      // Release multiple items at once back to the queue.
      else {
        $this->queue->releaseItemMultiple($items);
        foreach ($items as $purgeable) {
          $purgeable->setState(Purgeable::STATE_RELEASED);
        }
      }
    }
  }

  /**
   * Commit all deleting purgeables in the buffer to the queue.
   */
  private function commitDeleting() {
    $items = $this->bufferGetFiltered([Purgeable::STATE_DELETING]);
    if (empty($items)) {
      return;
    }
    else {

      // Delete only one item from the queue.
      if (count($items) === 1) {
        $purgeable = current($items);
        $this->queue->deleteItem($purgeable);
        $purgeable->setState(Purgeable::STATE_DELETED);
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
          $purgeable->setState(Purgeable::STATE_DELETED);
          foreach ($this->buffer as $i => $bufferedPurgeable) {
            if ($purgeable->item_id === $bufferedPurgeable->item_id) {
              unset($this->buffer[$i]);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {

    // The queue service attempts to collect all actions done for purgeables
    // in $this->buffer, and commits them as infrequent as possible during
    // runtime. At minimum it will commit to the underlying queue plugin upon
    // shutdown and by doing so, attempts to reduce and bundle the amount of
    // work the queue has to do (e.g., queries, disk writes, mallocs). This
    // helps purge to scale better and should cause no noticeable side-effects.
    $this->commit();
  }

  /**
   * In case PHP's destructor gets called, call destruct.
   */
  function __destruct() {
    $this->destruct();
  }
}
