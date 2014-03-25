<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueService.
 */

namespace Drupal\purge\Queue;

use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\PurgeablesServiceInterface;
use Drupal\purge\Purgeable\PurgeableInterface;
use Drupal\purge\Queue\UnexpectedServiceConditionException;
use Drupal\purge\Queue\QueueInterface;

/**
 * Provides the service that holds the underlying QueueInterface plugin.
 */
class QueueService extends ServiceBase implements QueueServiceInterface {

  /**
   * The Queue (plugin) instance that holds the underlying items.
   *
   * @var \Drupal\purge\Queue\QueueInterface
   */
  private $queue;

  /**
   * The service that generates purgeable objects on-demand.
   *
   * @var \Drupal\purge\Purgeable\PurgeablesServiceInterface
   */
  private $purgePurgeables;

  /**
   * The transaction buffer used to park purgeable objects.
   */
  private $buffer;

  /**
   * {@inheritdoc}
   */
  function __construct(QueueInterface $queue, PurgeablesServiceInterface $purge_purgeables) {
    $this->purgePurgeables = $purge_purgeables;
    $this->queue = $queue;
    $this->buffer = array();

    // The queue service attempts to collect all actions done for purgeables
    // in $this->buffer, and commits them as infrequent as possible during
    // runtime. At minimum it will commit to the underlying queue plugin upon
    // shutdown and by doing so, attempts to reduce and bundle the amount of
    // work the queue has to do (e.g., queries, disk writes, mallocs). This
    // helps purge to scale better and should cause no noticeable side-effects.
    register_shutdown_function(array($this, 'commit'));
  }

  /**
   * {@inheritdoc}
   */
  public function add(PurgeableInterface $purgeable) {
    $duplicate = FALSE;
    foreach ($this->buffer as $bufferedPurgeable) {
      if ($purgeable->dedupeid === $bufferedPurgeable->dedupeid) {
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
        if ($purgeable->dedupeid === $bufferedPurgeable->dedupeid) {
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

    // Claim the raw item from the queue.
    $item = $this->queue->claimItem($lease_time);

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