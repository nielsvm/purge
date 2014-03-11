<?php

/**
 * @file
 * Contains Drupal\purge\Purgeable\UnsupportedPurgeableException.
 */

namespace Drupal\purge\Purgeable;

/**
 * Exception thrown by the purger when it does not support the given purgeable.
 */
class UnsupportedPurgeableException extends \Exception {}