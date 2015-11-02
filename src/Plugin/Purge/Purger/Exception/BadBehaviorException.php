<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when APIs aren't being called as intended.
 *
 * @see \Drupal\purge\Plugin\Purge\Purger\SharedInterface::invalidate().
 * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface::__construct().
 * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface::set().
 * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface::increment().
 * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\CounterInterface::decrement().
 */
class BadBehaviorException extends \Exception {}
