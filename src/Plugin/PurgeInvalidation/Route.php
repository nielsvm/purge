<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Route.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes invalidation by Drupal route, e.g.: '<front>', 'user.page'.
 *
 * @PurgeInvalidation(
 *   id = "route",
 *   label = @Translation("Route"),
 *   description = @Translation("Invalidates by Drupal route."),
 *   examples = {"user.page", "<front>"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Route extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   *
   * @todo
   *   Validate the route or else throw InvalidExpressionException.
   */
  public function validateExpression() {
    parent::validateExpression();
  }
}
