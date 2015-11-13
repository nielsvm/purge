<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Claim a chunk of items from the queue and process them.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 *
 * @return array
 *   Drush render/format array.
 */
function drush_purge_drush_p_queue_work($format, QueueServiceInterface $purgeQueue, ProcessorsServiceInterface $purgeProcessors, PurgersServiceInterface $purgePurgers) {

  // Retrieve our queuer object and fail when it is not returned.
  if (!($processor = $purgeProcessors->get('drush_purge_queue_work'))) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('NOT_AUTHORIZED', dt('Not authorized, processor missing!'));
  }


  $args = func_get_args();
  foreach ($args as $i => $arg) {
    if (is_object($arg)) {
      var_dump(get_class($arg));
    }
    else {
      var_dump($arg);
    }
  }
  return [];
}
