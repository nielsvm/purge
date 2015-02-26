<?php

/**
 * @file
 * Contains \Drupal\purge_queue_test\Plugin\PurgeQueue\QueueC.
 */

namespace Drupal\purge_queue_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\Memory;
use Drupal\purge\Queue\PluginInterface;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "queue_c",
 *   label = @Translation("Memqueue C"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class QueueC extends Memory implements PluginInterface {}
