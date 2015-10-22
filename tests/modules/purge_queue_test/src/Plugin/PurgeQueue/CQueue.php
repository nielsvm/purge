<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\CQueue.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\MemoryQueue;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "c",
 *   label = @Translation("Memqueue C"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class CQueue extends MemoryQueue implements QueueInterface {}
