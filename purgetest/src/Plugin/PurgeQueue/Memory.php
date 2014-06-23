<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgeQueue\Memory.
 */

namespace Drupal\purgetest\Plugin\PurgeQueue;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @PurgeQueue(
 *   id = "memory",
 *   label = @Translation("Memory"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 *   service_dependencies = {}
 * )
 */
class Memory extends QueueBase implements QueueInterface {

}
