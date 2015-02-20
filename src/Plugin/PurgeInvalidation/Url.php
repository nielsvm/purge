<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Url.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\Component\Utility\UrlHelper;
use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes URL based invalidation, e.g. "http://site.com/node/1".
 *
 * @PurgeInvalidation(
 *   id = "url",
 *   label = @Translation("Url"),
 *   description = @Translation("Invalidates by URL."),
 *   examples = {"http://site.com/node/1"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Url extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression($wildcard_check = TRUE) {
    parent::validateExpression();
    if (!UrlHelper::isValid($this->expression, TRUE)) {
      throw new InvalidExpressionException('The URL is not valid.');
    }
    if ($wildcard_check && (strpos($this->expression, '*') !== FALSE)) {
      throw new InvalidExpressionException('URL invalidations should not contain asterisks, use "wildcardurl"!');
    }
    if (strpos($this->expression, ' ') !== FALSE) {
      throw new InvalidExpressionException(
      'URL invalidations cannot contain spaces, use %20 instead.');
    }
  }
}
