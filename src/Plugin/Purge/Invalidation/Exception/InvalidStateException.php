<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation\Exception;

/**
 * Thrown when the incoming or outgoing object states are not valid.
 *
 * InvalidStateException gets thrown in the following circumstances:
 *
 * 1) in \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface::setState when the $state
 *    parameter is out of range and doesn't match any of the STATE_* constants.
 *
 * 2) When a \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface object gets fed to
 *    the purger service that isn't a valid condition to purge objects in.
 *
 * 2) When a purger plugin doesn't set a valid state after processing the
 *    \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface object.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface::setState
 * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::purge
 * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::purgeMultiple
 */
class InvalidStateException extends \Exception {}
