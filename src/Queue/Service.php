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
use Drupal\purge\Queue\TxBuffer;
use Drupal\purge\Queue\ProxyItem;

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
   * The transaction buffer in which invalidation objects temporarily stay.
   *
   * @var \Drupal\purge\Queue\TxBuffer
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

    // Initialize the queue plugin and transaction buffer.
    $this->initializeQueue();
    $this->buffer = new TxBuffer();
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
    $this->queue = NULL;
    $this->buffer->deleteEverything();
    $this->configFactory = \Drupal::configFactory();
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
    if (!$this->buffer->has($invalidation)) {
      $this->buffer->set($invalidation, TxBuffer::ADDING);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      if (!$this->buffer->has($invalidation)) {
        $this->buffer->set($invalidation, TxBuffer::ADDING);
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

    // See if the invalidation object is still buffered locally, or instantiate.
    if (!($i = $this->buffer->getByProperty('item_id', $item->item_id))) {
      $i = $this->purgeInvalidationFactory->getFromQueueData($item->data);
    }

    // Ensure it is buffered, has the right state and properties, then return.
    $this->buffer->set($i, TxBuffer::CLAIMED);
    $this->buffer->setProperty($i, 'item_id', $item->item_id);
    $this->buffer->setProperty($i, 'created', $item->created);
    return $i;
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

      // See if the invalidation object is still buffered locally, or instantiate.
      if (!($inv = $this->buffer->getByProperty('item_id', $item->item_id))) {
        $inv = $this->purgeInvalidationFactory->getFromQueueData($item->data);
      }

      // Ensure it is buffered, has the right state and properties, then add it.
      $this->buffer->set($inv, TxBuffer::CLAIMED);
      $this->buffer->setProperty($inv, 'item_id', $item->item_id);
      $this->buffer->setProperty($inv, 'created', $item->created);
      $items[$i] = $inv;
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function release(Invalidation $invalidation) {
    $this->buffer->set($invalidation, TxBuffer::RELEASING);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseMultiple(array $invalidations) {
    $this->buffer->set($invalidations, TxBuffer::RELEASING);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(Invalidation $invalidation) {
    $this->buffer->set($invalidation, TxBuffer::DELETING);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $invalidations) {
    $this->buffer->set($invalidations, TxBuffer::DELETING);
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
    foreach($invalidations as $invalidation) {
      switch ($invalidation->getState()) {
        case Invalidation::STATE_PURGED:
          $this->buffer->set($invalidation, TxBuffer::DELETING);
          break;
        case Invalidation::STATE_PURGING:
        case Invalidation::STATE_FAILED:
          $this->buffer->set($invalidation, TxBuffer::RELEASING);
          break;
        default:
          throw new UnexpectedServiceConditionException("Unexpected state.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function emptyQueue() {
    $this->buffer->deleteEverything();
    $this->queue->deleteQueue();
    $this->buffer = [];
  }

  /**
   * Commit all actions in the internal buffer to the queue.
   */
  public function commit() {
    if (!count($this->buffer)) {
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
    $items = $this->buffer->getFiltered(TxBuffer::ADDING);
    if (empty($items)) {
      return;
    }

    // Small anonymous function that fetches the 'data' field for createItem()
    // and createItemMultiple() - keeps queue plugins out of Purge specifics.
    $getProxiedData = function($invalidation) {
      $proxy = new ProxyItem($invalidation, $this->buffer);
      return $proxy->data;
    };

    // Add just one item to the queue using createItem() on the queue.
    if (count($items) === 1) {
      $invalidation = current($items);
      if (!($id = $this->queue->createItem($getProxiedData($invalidation)))) {
        throw new UnexpectedServiceConditionException("The queue returned FALSE on createItem().");
      }
      else {
        $this->buffer->set($invalidation, TxBuffer::RELEASED);
        $this->buffer->setProperty($invalidation, 'item_id', $id);
        $this->buffer->setProperty($invalidation, 'created', time());
      }
    }

    // Add multiple at once to the queue using createItemMultiple() on the queue.
    else {
      $data_items = [];
      foreach ($items as $invalidation) {
        $data_items[] = $getProxiedData($invalidation);
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
        $this->buffer->set($invalidation, TxBuffer::ADDED);
        $this->buffer->setProperty($invalidation, 'item_id', $ids[$i]);
        $this->buffer->setProperty($invalidation, 'created', time());
      }
    }
  }

  /**
   * Commit all releasing invalidations in the buffer to the queue.
   */
  private function commitReleasing() {
    $items = $this->buffer->getFiltered(TxBuffer::RELEASING);
    if (empty($items)) {
      return;
    }
    if (count($items) === 1) {
      $invalidation = current($items);
      $this->queue->releaseItem($invalidation);
      $this->buffer->set($invalidation, TxBuffer::RELEASED);
    }
    else {
      $this->queue->releaseItemMultiple($items);
      $this->buffer->set($items, TxBuffer::RELEASED);
    }
  }

  /**
   * Commit all deleting invalidations in the buffer to the queue.
   */
  private function commitDeleting() {
    $items = $this->buffer->getFiltered(TxBuffer::DELETING);
    if (empty($items)) {
      return;
    }
    if (count($items) === 1) {
      $invalidation = current($items);
      $this->queue->deleteItem($invalidation);
      $this->buffer->delete($invalidation);
    }
    else {
      $this->queue->deleteItemMultiple($items);
      $this->buffer->delete($items);
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
