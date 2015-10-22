<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\PathInvalidation.
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
 *   expression_can_be_empty = TRUE,
 *   expression_must_be_string = TRUE
 * )
 */
class PathInvalidation extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression($wildcard_check = TRUE) {
    parent::validateExpression();
    if ($wildcard_check && (strpos($this->expression, '*') !== FALSE)) {
      throw new InvalidExpressionException($this->t('Path invalidations should not contain asterisks.'));
    }
    if ($wildcard_check && $this->expression === '*') {
      throw new InvalidExpressionException($this->t('Path invalidations cannot be "*".'));
    }
    if (strpos($this->expression, ' ') !== FALSE) {
      throw new InvalidExpressionException($this->t('Path invalidations cannot contain spaces, use %20 instead.'));
    }
    if (strpos($this->expression, '/') === 0) {
      throw new InvalidExpressionException($this->t('Path invalidations cannot start with slashes.'));
    }
  }

}
