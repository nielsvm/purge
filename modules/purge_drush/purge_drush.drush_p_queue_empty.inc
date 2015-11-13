<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

/**
 * TITLE
 *
 * @param TYPE $name
 *   Description.
 * @param TYPE $name
 *   Description.
 *
 * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
 *   Description.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
 * @see \Drupal\purge\Plugin\Purge\Purger\PurgerCapacityDataInterface::getTimeHint()
 *
 * @return string[]
 *   Array with the purger instance ID as key, and list of invalidation types.
 */
function drush_purge_drush_p_queue_empty() {
  $args = func_get_args();
  print_r($args);
  echo "----\n";
  var_dump($args);
  die("----\n");
}
