<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\BQueue.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\MemoryQueue;
use Drupal\purge\Plugin\Purge\Queue\PluginInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\PluginInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "b",
 *   label = @Translation("Memqueue B"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class BQueue extends MemoryQueue implements PluginInterface {}
