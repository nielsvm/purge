<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Url.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\Component\Utility\UrlHelper;
use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes URL based invalidation, e.g. "http://site.com/node/1".
 *
 * @PurgePurgeable(
 *   id = "url",
 *   label = @Translation("Url"),
 *   description = @Translation("Invalidates by URL, e.g. 'http://site.com/node/1'."),
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Url extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function validateExpression($wildcard_check = TRUE) {
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
