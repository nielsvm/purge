<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\RegularExpression.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;

/**
 * Describes invalidation by regular expression, e.g.: '\.(jpg|jpeg|css|js)$'.
 *
 * @PurgePurgeable(
 *   id = "regex",
 *   label = @Translation("Regular Expression"),
 *   description = @Translation("Invalidates by regular expression, e.g.: '\.(jpg|jpeg|css|js)$'."),
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class RegularExpression extends PluginBase implements PluginInterface {}
