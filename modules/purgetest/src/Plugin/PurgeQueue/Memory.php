<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgeQueue\Memory.
 */

namespace Drupal\purgetest\Plugin\PurgeQueue;

use Drupal\Core\Queue\Memory as CoreMemoryQueue;
use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @PurgeQueue(
 *   id = "memory",
 *   label = @Translation("Memory"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 *   service_dependencies = {}
 * )
 */
class Memory extends QueueBase implements QueueInterface {

  /**
   * Drupal core's memory queue instance.
   *
   * @var object
   */
  protected $queue;

  /**
   * Setup a volatile, memory based queue.
   */
  function __construct() {
    $this->queue = new CoreMemoryQueue('purgememoryqueue');
  }
  /**
   * Adds a queue item and store it directly to the queue.
   *
   * @param $data
   *   Arbitrary data to be associated with the new task in the queue.
   *
   * @return
   *   A unique ID if the item was successfully created and was (best effort)
   *   added to the queue, otherwise FALSE. We don't guarantee the item was
   *   committed to disk etc, but as far as we know, the item is now in the
   *   queue.
   */
  public function createItem($data) {
    return $this->queue->createItem($data);
  }

  /**
   * Retrieves the number of items in the queue.
   *
   * This is intended to provide a "best guess" count of the number of items in
   * the queue. Depending on the implementation and the setup, the accuracy of
   * the results of this function may vary.
   *
   * e.g. On a busy system with a large number of consumers and items, the
   * result might only be valid for a fraction of a second and not provide an
   * accurate representation.
   *
   * @return
   *   An integer estimate of the number of items in the queue.
   */
  public function numberOfItems() {
    return $this->queue->numberOfItems();
  }

  /**
   * Claims an item in the queue for processing.
   *
   * @param $lease_time
   *   How long the processing is expected to take in seconds, defaults to an
   *   hour. After this lease expires, the item will be reset and another
   *   consumer can claim the item. For idempotent tasks (which can be run
   *   multiple times without side effects), shorter lease times would result
   *   in lower latency in case a consumer fails. For tasks that should not be
   *   run more than once (non-idempotent), a larger lease time will make it
   *   more rare for a given task to run multiple times in cases of failure,
   *   at the cost of higher latency.
   *
   * @return
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false. This implies a best effort to retrieve an item
   *   and either the queue is empty or there is some other non-recoverable
   *   problem.
   *
   *   If returned, the object will have at least the following properties:
   *   - data: the same as what what passed into createItem().
   *   - item_id: the unique ID returned from createItem().
   *   - created: timestamp when the item was put into the queue.
   */
  public function claimItem($lease_time = 30) {
    return $this->queue->claimItem($lease_time);
  }

  /**
   * Deletes a finished item from the queue.
   *
   * @param $item
   *   The item returned by Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function deleteItem($item) {
    return $this->queue->deleteItem($item);
  }

  /**
   * Releases an item that the worker could not process.
   *
   * Another worker can come in and process it before the timeout expires.
   *
   * @param $item
   *   The item returned by Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @return boolean
   *   TRUE if the item has been released, FALSE otherwise.
   */
  public function releaseItem($item) {
    return $this->queue->releaseItem($item);
  }

  /**
   * Creates a queue.
   *
   * Called during installation and should be used to perform any necessary
   * initialization operations. This should not be confused with the
   * constructor for these objects, which is called every time an object is
   * instantiated to operate on a queue. This operation is only needed the
   * first time a given queue is going to be initialized (for example, to make
   * a new database table or directory to hold tasks for the queue -- it
   * depends on the queue implementation if this is necessary at all).
   */
  public function createQueue() {
    return $this->queue->createQueue();
  }

  /**
   * Deletes a queue and every item in the queue.
   */
  public function deleteQueue() {
    return $this->queue->deleteQueue();
  }

}
