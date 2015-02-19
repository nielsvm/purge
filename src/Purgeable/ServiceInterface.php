<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\ServiceInterface.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\ServiceInterface as PurgeServiceInterface;

/**
 * Describes a service that instantiates purgeable objects on-demand.
 */
interface ServiceInterface extends PurgeServiceInterface {

  /**
   * Create a new purgeable object of the given type.
   *
   * @param string $plugin_id
   *   The id of the purgeable plugin being instantiated.
   * @param string|null $expression
   *   String that describes what needs to be invalidated, or NULL when the
   *   requested type of purgeable doesn't require one. Purgeable types often
   *   validate if the given expression makes sense and throw exceptions in case
   *   of bad input.
   *
   * @throws \Drupal\purge\Purgeable\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Purgeable\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of purgeable.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  public function get($plugin_id, $expression = NULL);

  /**
   * Replicate a purgeable object from serialized queue item data.
   *
   * @param string $item_data
   *   Arbitrary PHP data structured that was stored into the queue.
   *
   * @throws \Drupal\purge\Purgeable\Exception\MissingExpressionException
   *   Thrown when plugin defined expression_required = TRUE and when it is
   *   instantiated without expression (NULL).
   * @throws \Drupal\purge\Purgeable\Exception\InvalidExpressionException
   *   Exception thrown when plugin got instantiated with an expression that is
   *   not deemed valid for the type of purgeable.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  public function getFromQueueData($item_data);

}
