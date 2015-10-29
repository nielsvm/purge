<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes the immutable invalidation.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
interface ImmutableInvalidationInterface extends PluginInspectionInterface {

  /**
   * Invalidation object just got instantiated.
   */
  const STATE_NEW = 0;

  /**
   * Invalidation is on-going and requires later confirmation by the purger
   * whether it is finished or not, turns into STATE_PURGED.
   */
  const STATE_PURGING = 1;

  /**
   * The invalidation succeeded.
   */
  const STATE_PURGED = 2;

  /**
   * The invalidation failed.
   */
  const STATE_FAILED = 3;

  /**
   * The invalidation type is not supported by any active purger.
   */
  const STATE_UNSUPPORTED = 4;

  /**
   * Return the string expression of the invalidation.
   *
   * @return string
   *   Returns the string serialization, e.g. "node/1".
   */
  public function __toString();

  /**
   * Get the invalidation expression.
   *
   * @return mixed|null
   *   Mixed expression (or NULL) that describes what needs to be invalidated.
   */
  public function getExpression();

  /**
   * Get the current state of the invalidation.
   *
   * @return int
   *   Integer matching to one of the InvalidationInterface::STATE_* constants.
   */
  public function getState();

  /**
   * Get the current state as string.
   *
   * @return string
   *   The string comes without the 'STATE_' prefix as on the constants.
   */
  public function getStateString();

  /**
   * Get the current state as user translated string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated string describing the current state of the invalidation.
   */
  public function getStateStringTranslated();
}
