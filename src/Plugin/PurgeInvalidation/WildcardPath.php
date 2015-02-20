<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\WildcardPath.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Plugin\PurgeInvalidation\Path;
use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes wildcardpath based invalidation, e.g. "news/*".
 *
 * @PurgeInvalidation(
 *   id = "wildcardpath",
 *   label = @Translation("Path with wildcard"),
 *   description = @Translation("Invalidates by path."),
 *   examples = {"news/*"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = TRUE
 * )
 */
class WildcardPath extends Path implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function validateExpression() {
    parent::validateExpression(FALSE);
    if (strpos($this->expression, '*') === FALSE) {
      throw new InvalidExpressionException('Wildcard invalidations should contain an asterisk.');
    }
  }
}
