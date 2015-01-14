<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Exception\InvalidStateException.
 */

namespace Drupal\purge\Purgeable\Exception;

/**
 * Condition when \Drupal\purge\Purgeable\PluginInterface::setState gets called
 * with a $state parameter that is not valid. The passed integer should match
 * one of the 11 constants in \Drupal\purge\Purgeable\PluginInterface::STATE_*.
 */
class InvalidStateException extends \Exception {}
