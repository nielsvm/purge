<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\AQueue.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\MemoryQueue;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "a",
 *   label = @Translation("Memqueue A"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class AQueue extends MemoryQueue implements QueueInterface {}
