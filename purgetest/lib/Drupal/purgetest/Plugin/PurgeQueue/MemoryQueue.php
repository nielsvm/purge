<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgeQueue\MemoryQueue.
 */

namespace Drupal\purgetest\Plugin\PurgeQueue;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @ingroup purge_queue_types
 *
 * @Plugin(
 *   id = "MemoryQueue",
 *   label = @Translation("A volatile memory-based queue.")
 * )
 */
class MemoryQueue extends QueueBase implements QueueInterface {

}
