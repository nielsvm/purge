<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgeQueue\QueueB.
 */

namespace Drupal\purge_test\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\Memory;
use Drupal\purge\Queue\QueueInterface;

/**
 * A QueueInterface compliant memory queue for testing purposes.
 *
 * @PurgeQueue(
 *   id = "queue_b",
 *   label = @Translation("Memqueue B"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 *   service_dependencies = {}
 * )
 */
class QueueB extends Memory implements QueueInterface {}
