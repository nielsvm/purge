<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;

/**
 * List all enabled processors.
 *
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 *
 * @return string[]
 *   Array with the purger instance ID as key, and list of invalidation types.
 */
function drush_purge_drush_p_processors(ProcessorsServiceInterface $purgeProcessors) {
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
