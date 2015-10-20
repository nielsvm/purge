<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when APIs aren't being called as intended.
 *
 * @see \Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface::__construct().
 * @see \Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface::set().
 * @see \Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface::increment().
 * @see \Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface::decrement().
 */
class BadBehaviorException extends \Exception {}
