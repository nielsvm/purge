<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Domain.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes an entire domain to be invalidated.
 *
 * @PurgePurgeable(
 *   id = "domain",
 *   label = @Translation("Domain"),
 *   description = @Translation("Invalidates an entire domain name."),
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
  protected function validateExpression() {
    parent::validateExpression();
  }
}
