<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\ServiceInterface.
 */

namespace Drupal\purge\Invalidation;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;

/**
 * Describes a service that instantiates invalidation objects on-demand.
 */
interface ServiceInterface extends PurgeServiceInterface {

  /**
   * Create a new invalidation object of the given type.
   *
   * @param string $plugin_id
   *   The id of the invalidation type being instantiated.
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   *
   * @throws \Drupal\purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Invalidation\PluginInterface
   */
  public function get($plugin_id, $expression = NULL);

  /**
   * Replicate a invalidation object from serialized queue item data.
   *
   * @param string $item_data
   *   Arbitrary PHP data structured that was stored into the queue.
   *
   * @throws \Drupal\purge\Invalidation\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Invalidation\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of invalidation.
   *
   * @return \Drupal\purge\Invalidation\PluginInterface
   */
  public function getFromQueueData($item_data);

}
