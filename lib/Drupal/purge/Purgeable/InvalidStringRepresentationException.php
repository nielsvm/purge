<?php

/**
 * @file
 * Contains Drupal\purge\Purgeable\InvalidStringRepresentationException.
 */

namespace Drupal\purge\Purgeable;

/**
 * Exception that gets thrown when no purgeable type supports the input string.
 */
class InvalidStringRepresentationException extends \Exception {}