<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;

/**
 * Inspect what is in the queue by paging through it.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param string|int $limit
 *   The number of items to show on a single page.
 * @param string|int $page
 *   The page to show data for, pages start at 1.
 *
 * @return array
 *   Drush render/format array.
 */
function drush_purge_drush_p_queue_browse($format, QueueServiceInterface $purgeQueue, $limit, $page) {
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
