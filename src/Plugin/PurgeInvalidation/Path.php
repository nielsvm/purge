<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Path.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes path based invalidation, e.g. "news/article-1".
 *
 * @PurgeInvalidation(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("Invalidates by path."),
 *   examples = {"news/article-1"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = TRUE
 * )
 */
class Path extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function validateExpression($wildcard_check = TRUE) {
    parent::validateExpression();
    if ($wildcard_check && (strpos($this->expression, '*') !== FALSE)) {
      throw new InvalidExpressionException('Path invalidations should not contain asterisks, use "wildcardpath"!');
    }
    if ($this->expression === '*') {
      throw new InvalidExpressionException('Path invalidations cannot be "*", use "wildcardpath".');
    }
    if (strpos($this->expression, ' ') !== FALSE) {
      throw new InvalidExpressionException(
      'Path invalidations cannot contain spaces, use %20 instead.');
    }
  }
}
