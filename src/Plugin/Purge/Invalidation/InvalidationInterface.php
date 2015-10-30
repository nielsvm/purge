<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;

/**
 * Desribes the invalidation object.
 *
 * Invalidations are small value objects that decribe and track invalidations
 * on one or more external caching systems within the Purge pipeline. These
 * objects can be directly instantiated from InvalidationsService and float
 * freely between the QueueService and the PurgersService.
 */
interface InvalidationInterface extends ImmutableInvalidationInterface, ContainerFactoryPluginInterface {

  /**
   * Get the instance ID.
   *
   * @return int
   *   Unique integer ID for this object instance (during runtime).
   */
  public function getId();

  /**
   * Set the state of the invalidation.
   *
   * @param int $state
   *   Any \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface constant.
   *
   * @throws \Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException
   *   Thrown when the $state parameter doesn't match any of the constants
   *   defined in \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface.
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
