<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueService.
 */

namespace Drupal\purge\Queue;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\purge\Queue\QueueInterface;

/**
 * Provides the service that holds the underlying QueueInterface plugin.
 */
class QueueService extends ServiceProviderBase implements QueueServiceInterface {

  /**
   * The Queue (plugin) instance that holds the underlying items.
   *
   * @var \Drupal\purge\Queue\QueueInterface
   */
  private $queue;

  /**
   * {@inheritdoc}
   */
  function __construct(QueueInterface $queue) {
    $this->queue = $queue;
  }

  /**
   * Empty the entire queue and reset all statistics.
   */
  function emptyQueue() {
    $this->queue->deleteQueue();
  }
}