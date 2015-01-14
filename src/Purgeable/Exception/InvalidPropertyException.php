<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Exception\InvalidPropertyException.
 */

namespace Drupal\purge\Purgeable\Exception;

/**
 * Exception thrown when a data property on the purgeable is called that does
 * not exist, e.g. $purgeable->idontexist.
 */
class InvalidPropertyException extends \Exception {}
