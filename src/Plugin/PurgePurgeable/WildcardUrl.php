<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\WildcardUrl.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Plugin\PurgePurgeable\Url;
use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes wildcard URL based invalidation, e.g. "http://site.com/node/*".
 *
 * @PurgePurgeable(
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
