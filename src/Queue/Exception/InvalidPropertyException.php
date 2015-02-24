<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\Exception\InvalidPropertyException.
 */

namespace Drupal\purge\Queue\Exception;

/**
 * Exception thrown when a data property on a ProxyItem object is called
 * that does not exist, e.g. $proxyitem->idontexist.
 */
class InvalidPropertyException extends \Exception {}
