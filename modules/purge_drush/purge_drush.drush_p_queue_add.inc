<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;

/**
 * Schedule an item for later processing.
 *
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purgeQueuers
 *   The purge queuers service.
 * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purgeInvalidationFactory
 *   The purge invalidation factory service.
 * @param string $type
 *   The type of invalidation to queue.
 * @param mixed|null $expression
 *   Value - usually string - that describes the kind of invalidation, NULL
 *   when the type of invalidation doesn't require $expression. Types usually
 *   validate the given expression and throw exceptions for bad input.
 *
 * @return string[]
 *   Array with the purger instance ID as key, and list of invalidation types.
 */
function drush_purge_drush_p_queue_add(QueueServiceInterface $purgeQueue, QueuersServiceInterface $purgeQueuers, InvalidationsServiceInterface $purgeInvalidationFactory, $type, $expression = NULL) {
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
