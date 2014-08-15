<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgeQueue\QueueC.
 */

namespace Drupal\purge_test\Plugin\PurgeQueue;

use Drupal\purge_test\Plugin\PurgeQueue\Memory;
use Drupal\purge\Queue\QueueInterface;

/**
 * A QueueInterface compliant memory queue for testing purposes.
 *
 * @PurgeQueue(
 *   id = "queue_c",
 *   label = @Translation("Memqueue C"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 *   service_dependencies = {}
 * )
 */
class QueueC extends Memory implements QueueInterface {}
