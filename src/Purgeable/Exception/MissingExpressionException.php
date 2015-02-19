<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Exception\MissingExpressionException.
 */

namespace Drupal\purge\Purgeable\Exception;

/**
 * Thrown when purgeables are instantiated without a expression when required.
 */
class MissingExpressionException extends \Exception {}
