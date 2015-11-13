<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Directly invalidate an item without going through the queue.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purgeInvalidationFactory
 *   The purge invalidation factory service.
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
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
function drush_purge_drush_p_invalidate($format, InvalidationsServiceInterface $purgeInvalidationFactory, ProcessorsServiceInterface $purgeProcessors, PurgersServiceInterface $purgePurgers, $type, $expression = NULL) {
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
