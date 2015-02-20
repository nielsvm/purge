<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\WildcardUrl.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Plugin\PurgeInvalidation\Url;
use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes wildcard URL based invalidation, e.g. "http://site.com/node/*".
 *
 * @PurgeInvalidation(
 *   id = "wildcardurl",
 *   label = @Translation("Url with wildcard"),
 *   description = @Translation("Invalidates by URL."),
 *   examples = {"http://site.com/node/*"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class WildcardUrl extends Url implements PluginInterface {

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
