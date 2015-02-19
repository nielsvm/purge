<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Route.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes invalidation by Drupal route, e.g.: '<front>', 'user.page'.
 *
 * @PurgePurgeable(
 *   id = "route",
 *   label = @Translation("Route"),
 *   description = @Translation("Invalidates by Drupal route, e.g.: '<front>'."),
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
  protected function validateExpression() {
    parent::validateExpression();
  }
}
