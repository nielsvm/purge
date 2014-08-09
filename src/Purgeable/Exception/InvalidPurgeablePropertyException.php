<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Exception\InvalidPurgeablePropertyException.
 */

namespace Drupal\purge\Purgeable\Exception;

/**
 * Exception thrown when a data property on the purgeable is called that does
 * not exist, e.g. $purgeable->idontexist.
 */
class InvalidPurgeablePropertyException extends \Exception {}