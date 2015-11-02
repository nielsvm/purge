<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException.
 */

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when purgers are not implemented as outlined in the documentation.
 *
 * @see \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface::getTimeHint().
 */
class BadPluginBehaviorException extends \Exception {}
