<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\WildcardPath.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Plugin\PurgePurgeable\Path;
use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes wildcardpath based invalidation, e.g. "news/*".
 *
 * @PurgePurgeable(
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
