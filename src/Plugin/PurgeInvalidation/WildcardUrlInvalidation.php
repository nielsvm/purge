<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\WildcardUrlInvalidation.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Plugin\PurgeInvalidation\UrlInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\PluginInterface;
use Drupal\purge\Plugin\Purge\Invalidation\PluginBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

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
class WildcardUrlInvalidation extends UrlInvalidation implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $url = parent::validateExpression(FALSE);
    if (strpos($url, '*') === FALSE) {
      throw new InvalidExpressionException($this->t('Wildcard invalidations should contain an asterisk.'));
    }
  }

}
