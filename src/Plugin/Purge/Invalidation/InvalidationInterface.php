<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes the invalidation: which instructs the purger what to invalidate.
 */
interface InvalidationInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

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
   * Get the instance ID.
   *
   * @return int
   *   Unique integer ID for this object instance (during runtime).
   */
  public function getId();

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
   * Set the state of the invalidation.
   *
   * @param int $state
   *   Integer matching to any of the InvalidationInterface::STATE_* constants.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException
   *   Thrown when the given value doesn't match any of the STATE_ constants.
   *
   * @return void
   */
  public function setState($state);

  /**
   * Validate the expression given to the invalidation during instantiation.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_required
   * @see \Drupal\purge\Annotation\PurgeInvalidation::$expression_can_be_empty
   *
   * @return void
   */
  public function validateExpression();

}