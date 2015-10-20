<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\Exception\BadPluginBehaviorException.
 */

namespace Drupal\purge\Purger\Exception;

/**
 * Thrown when purgers are not implemented as outlined in the documentation.
 *
 * @see \Drupal\purge\Purger\SharedInterface::invalidate().
 * @see \Drupal\purge\Purger\SharedInterface::invalidateMultiple().
 * @see \Drupal\purge\Purger\ServiceInterface::getClaimTimeHint().
 */
class BadPluginBehaviorException extends \Exception {}
