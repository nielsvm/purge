<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\AQueue.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\MemoryQueue;
use Drupal\purge\Queue\PluginInterface;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "a",
 *   label = @Translation("Memqueue A"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class AQueue extends MemoryQueue implements PluginInterface {}
