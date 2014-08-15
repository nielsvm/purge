<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgeQueue\Memory.
 */

namespace Drupal\purge_test\Plugin\PurgeQueue;

use Drupal\Core\Queue\Memory as CoreMemoryQueue;
use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant memory backed queue.
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
   * @var \Drupal\Core\Queue\Memory
   */
  protected $queue;

  /**
   * Setup a volatile, memory based queue.
   */
  function __construct() {
    $this->queue = new CoreMemoryQueue('purge');
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    return $this->queue->createItem($data);
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    return $this->queue->numberOfItems();
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 30) {
    return $this->queue->claimItem($lease_time);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    return $this->queue->deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    return $this->queue->releaseItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    return $this->queue->createQueue();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    return $this->queue->deleteQueue();
  }

}
