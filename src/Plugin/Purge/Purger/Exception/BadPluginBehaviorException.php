<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when purgers are not implemented as outlined in the documentation.
 *
 * @see \Drupal\purge\Purger\SharedInterface::invalidate().
 * @see \Drupal\purge\Purger\SharedInterface::invalidateMultiple().
 * @see \Drupal\purge\Plugin\Purge\Purger\ResourceTracking\TrackerInterface::getTimeHint().
 */
class BadPluginBehaviorException extends \Exception {}
