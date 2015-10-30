<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface;

/**
 * Describes the immutable invalidation.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
interface ImmutableInvalidationInterface extends InvStatesInterface, PluginInspectionInterface {

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
   *   Any \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface constant.
   */
  public function getState();

  /**
   * Get the current state as string.
   *
   * @return string
   *   A capitalized string exactly matching the names of the constants in
   *   \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
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
