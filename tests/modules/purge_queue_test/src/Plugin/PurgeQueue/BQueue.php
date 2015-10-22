<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\BQueue.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\MemoryQueue;
use Drupal\purge\Queue\PluginInterface;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "b",
 *   label = @Translation("Memqueue B"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class BQueue extends MemoryQueue implements PluginInterface {}
