<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;

/**
 * Inspect what is in the queue by paging through it.
 *
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 *
 * @return string[]
 *   Array with the purger instance ID as key, and list of invalidation types.
 */
function drush_purge_drush_p_queue_browse(QueueServiceInterface $purgeQueue) {
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
