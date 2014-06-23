<?php

/**
 * @file
 * Contains Drupal\purge\Purger\UnsupportedPurgeableException.
 */

namespace Drupal\purge\Purger;

/**
 * Exception thrown by the purger when it does not support the given purgeable.
 */
class UnsupportedPurgeableException extends \Exception {}