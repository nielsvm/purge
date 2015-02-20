<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\Exception\InvalidPropertyException.
 */

namespace Drupal\purge\Invalidation\Exception;

/**
 * Exception thrown when a data property on the invalidation object is called
 * that does not exist, e.g. $invalidation->idontexist.
 */
class InvalidPropertyException extends \Exception {}
