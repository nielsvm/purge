<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\Purge\Queue\Memory.
 */

namespace Drupal\purgetest\Plugin\Purge\Queue;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @ingroup purge_queue_types
 *
 * @Plugin(
 *   id = "Memory",
 *   label = @Translation("A volatile memory-based queue.")
 * )
 */
class Memory extends QueueBase implements QueueInterface {

}
