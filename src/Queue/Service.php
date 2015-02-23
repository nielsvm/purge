<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\Service.
 */

namespace Drupal\purge\Queue;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Invalidation\ServiceInterface as InvalidationService;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\purge\Queue\Exception\UnexpectedServiceConditionException;
use Drupal\purge\Queue\PluginInterface;

/**
 * Provides the service that lets invalidations interact with a queue backend.
 */
class Service extends ServiceBase implements ServiceInterface, DestructableInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The service that generates invalidation objects on-demand.
   *
   * @var \Drupal\purge\Invalidation\ServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * The Queue (plugin) object in which all items are stored.
   *
   * @var \Drupal\purge\Queue\PluginInterface
   */
  protected $queue;

  /**
   * The transaction buffer used to temporarily park invalidation objects.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Invalidation\ServiceInterface $purge_invalidation_factory
   *   The service that instantiates invalidation objects for queue items.
   */
  function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory, InvalidationService $purge_invalidation_factory) {
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;

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
  public function add(Invalidation $invalidation) {
    $duplicate = FALSE;
    foreach ($this->buffer as $bufferedInvalidation) {
      if ($invalidation->data === $bufferedInvalidation->data) {
        $duplicate = TRUE;
        break;
      }
    }
    if (!$duplicate) {
      $invalidation->setState(Invalidation::STATE_ADDING);
      $this->buffer[] = $invalidation;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $duplicate = FALSE;
      foreach ($this->buffer as $bufferedInvalidation) {
        if ($invalidation->data === $bufferedInvalidation->data) {
          $duplicate = TRUE;
          break;
        }
      }
      if (!$duplicate) {
        $invalidation->setState(Invalidation::STATE_ADDING);
        $this->buffer[] = $invalidation;
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
    foreach ($this->buffer as $invalidation) {
      if ($invalidation->item_id === $item->item_id) {
        $match = $invalidation;
        break;
      }
    }

    // If a locally buffered invalidation object was found, update & return it.
    if ($match) {
      $match->setState(Invalidation::STATE_CLAIMED);
      $match->setQueueItemInfo($item->item_id, $item->created);
      return $match;
    }

    // If the item was not locally buffered (usually), instantiate one.
    else {
      $invalidation = $this->purgeInvalidationFactory->getFromQueueData($item->data);
      $invalidation->setState(Invalidation::STATE_CLAIMED);
      $invalidation->setQueueItemInfo($item->item_id, $item->created);
      $this->buffer[] = $invalidation;
      return $invalidation;
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

      // See if this claimed item is locally available as invalidation object.
      $match = NULL;
      foreach ($this->buffer as $invalidation) {
        if ($invalidation->item_id === $item->item_id) {
          $match = $invalidation;
          break;
        }
      }

      // If a match was found, update and overwrite the object in $items.
      if ($match) {
        $match->setState(Invalidation::STATE_CLAIMED);
        $match->setQueueItemInfo($item->item_id, $item->created);
        $items[$i] = $match;
      }

      // If the item was not locally buffered (usually), instantiate one.
      else {
        $invalidation = $this->purgeInvalidationFactory->getFromQueueData($item->data);
        $invalidation->setState(Invalidation::STATE_CLAIMED);
        $invalidation->setQueueItemInfo($item->item_id, $item->created);
        $this->buffer[] = $invalidation;
        $items[$i] = $invalidation;
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function release(Invalidation $invalidation) {
    $this->bufferSetState(Invalidation::STATE_RELEASING, [$invalidation]);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseMultiple(array $invalidations) {
    $this->bufferSetState(Invalidation::STATE_RELEASING, $invalidations);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(Invalidation $invalidation) {
    $this->bufferSetState(Invalidation::STATE_DELETING, [$invalidation]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $invalidations) {
    $this->bufferSetState(Invalidation::STATE_DELETING, $invalidations);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteOrRelease(Invalidation $invalidation) {
    $this->deleteOrReleaseMultiple([$invalidation]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteOrReleaseMultiple(array $invalidations) {
    $release = [];
    $delete = [];
    foreach($invalidations as $invalidation) {
      switch ($invalidation->getState()) {
        case Invalidation::STATE_PURGED:
          $delete[] = $invalidation;
          break;
        case Invalidation::STATE_PURGING:
        case Invalidation::STATE_PURGEFAILED:
          $release[] = $invalidation;
          break;
        default:
          throw new UnexpectedServiceConditionException("Unexpected state.");
      }
    }
    $this->bufferSetState(Invalidation::STATE_DELETING, $delete);
    $this->bufferSetState(Invalidation::STATE_RELEASING, $release);
  }

  /**
   * {@inheritdoc}
   */
  function emptyQueue() {
    $this->bufferSetState(Invalidation::STATE_DELETED, $this->buffer);
    $this->queue->deleteQueue();
    $this->buffer = [];
  }

  /**
   * Only retrieve items from the buffer in a particular given state.
   *
   * @param array $states
   *   A non-associative array containing one or more state constants as
   *   found under \Drupal\purge\Invalidation\PluginInterface::STATE_*.
   *
   * @return
   *   Returns a non-associative array with invalidation objects, but only those
   *   that matched the given states requested.
   */
  private function bufferGetFiltered(array $states) {
    $results = [];
    foreach ($this->buffer as $invalidation) {
      if (in_array($invalidation->getState(), $states)) {
        $results[] = $invalidation;
      }
    }
    return $results;
  }

  /**
   * Set a certain state on each invalidation in the given array.
   *
   * @param $state
   *   Integer matching to any of the \Drupal\purge\Invalidation
   *   \PluginInterface::STATE_* constants.
   * @param array $invalidations
   *   A non-associative array with \Drupal\purge\Invalidation\PluginInterface
   *   objects that need the given state applied.
   * @param bool $checkid
   *   If TRUE, only change the state on objects that have a non-NULL item_id.
   */
  private function bufferSetState($state, array $invalidations, $checkid = TRUE) {
    foreach ($invalidations as $invalidation) {
      if ($checkid && is_null($invalidation->item_id)) {
        continue;
      }
      $buffered = FALSE;
      foreach ($this->buffer as $bufferedInvalidation) {
        if ($invalidation->item_id === $bufferedInvalidation->item_id) {
          $buffered = TRUE;
          break;
        }
      }
      if (!$buffered) {
        $this->buffer[] = $invalidation;
      }
      $invalidation->setState($state);
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
   * Commit all adding invalidations in the buffer to the queue.
   */
  private function commitAdding() {
    $items = $this->bufferGetFiltered([Invalidation::STATE_ADDING]);
    if (empty($items)) {
      return;
    }
    else {

      // Add just one item to the queue using createItem() on the queue.
      if (count($items) === 1) {
        $invalidation = current($items);
        if (!($id = $this->queue->createItem($invalidation->data))) {
          throw new UnexpectedServiceConditionException(
            "The queue returned FALSE on createItem().");
        }
        else {
          $invalidation->setQueueItemId($id);
          $invalidation->setQueueItemCreated(time());
          $invalidation->setState(Invalidation::STATE_ADDED);
        }
      }

      // Add multiple at once to the queue using createItemMultiple() on the queue.
      else {
        $data_items = [];
        foreach ($items as $invalidation) {
          $data_items[] = $invalidation->data;
        }
        if (!($ids = $this->queue->createItemMultiple($data_items))) {
          throw new UnexpectedServiceConditionException(
            "The queue returned FALSE on createItemMultiple().");
        }
        foreach ($items as $invalidation) {
          if (!isset($i)) {
            $i = 0;
          }
          else {
            $i++;
          }
          $invalidation->setQueueItemId($ids[$i]);
          $invalidation->setQueueItemCreated(time());
          $invalidation->setState(Invalidation::STATE_ADDED);
        }
      }
    }
  }

  /**
   * Commit all releasing invalidations in the buffer to the queue.
   */
  private function commitReleasing() {
    $items = $this->bufferGetFiltered([Invalidation::STATE_RELEASING]);
    if (empty($items)) {
      return;
    }
    else {

      // Release just one item back to the queue.
      if (count($items) === 1) {
        $invalidation = current($items);
        $this->queue->releaseItem($invalidation);
        $invalidation->setState(Invalidation::STATE_RELEASED);
      }

      // Release multiple items at once back to the queue.
      else {
        $this->queue->releaseItemMultiple($items);
        foreach ($items as $invalidation) {
          $invalidation->setState(Invalidation::STATE_RELEASED);
        }
      }
    }
  }

  /**
   * Commit all deleting invalidations in the buffer to the queue.
   */
  private function commitDeleting() {
    $items = $this->bufferGetFiltered([Invalidation::STATE_DELETING]);
    if (empty($items)) {
      return;
    }
    else {

      // Delete only one item from the queue.
      if (count($items) === 1) {
        $invalidation = current($items);
        $this->queue->deleteItem($invalidation);
        $invalidation->setState(Invalidation::STATE_DELETED);
        foreach ($this->buffer as $i => $bufferedInvalidation) {
          if ($invalidation->item_id === $bufferedInvalidation->item_id) {
            unset($this->buffer[$i]);
          }
        }
      }

      // Delete multiple items at once from the queue.
      else {
        $this->queue->deleteItemMultiple($items);
        foreach ($items as $invalidation) {
          $invalidation->setState(Invalidation::STATE_DELETED);
          foreach ($this->buffer as $i => $bufferedInvalidation) {
            if ($invalidation->item_id === $bufferedInvalidation->item_id) {
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

    // The queue service attempts to collect all actions done for invalidations
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
