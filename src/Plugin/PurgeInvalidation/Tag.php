<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Tag.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes invalidation by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @PurgeInvalidation(
 *   id = "tag",
 *   label = @Translation("Tag"),
 *   description = @Translation("Invalidates by Drupal cache tag."),
 *   examples = {"node:1", "menu:footer"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE,
 * )
 */
class Tag extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    parent::validateExpression();
    if (strpos($this->expression, '*') !== FALSE) {
      throw new InvalidExpressionException('Tags cannot contain asterisks.');
    }
  }

}
