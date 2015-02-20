<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Domain.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes an entire domain to be invalidated.
 *
 * @PurgeInvalidation(
 *   id = "domain",
 *   label = @Translation("Domain"),
 *   description = @Translation("Invalidates an entire domain name."),
 *   examples = {"www.site.com", "site.com"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Domain extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   *
   * @todo
   *   Find out if there's a - Drupal level - way to determine if the given
   *   name is valid or not.
   */
  public function validateExpression() {
    parent::validateExpression();
  }
}
