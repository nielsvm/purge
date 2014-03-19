<?php

/**
 * @file
 * Contains \Drupal\purge_fakerefbackend\Plugin\PurgeQueue\FileQueue.
 */

namespace Drupal\purge_fakerefbackend\Plugin\PurgeQueue;

use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant file backed queue.
 *
 * @ingroup purge_queue_types
 *
 * @Plugin(
 *   id = "FileQueue",
 *   label = @Translation("A file based purge queue.")
 * )
 */
class FileQueue extends QueueBase implements QueueInterface {

}
