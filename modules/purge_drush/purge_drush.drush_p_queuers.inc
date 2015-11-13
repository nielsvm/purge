<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * List all enabled queuers.
 *
 * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purgeQueuers
 *   The purge queuers service.
 *
 * @return string[]
 *   Array with the purger instance ID as key, and list of invalidation types.
 */
function drush_purge_drush_p_queuers(QueuersServiceInterface $purgeQueuers) {
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
