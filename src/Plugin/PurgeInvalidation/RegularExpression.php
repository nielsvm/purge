<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\RegularExpression.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;
use Drupal\purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes invalidation by regular expression, e.g.: '\.(jpg|jpeg|css|js)$'.
 *
 * @PurgeInvalidation(
 *   id = "regex",
 *   label = @Translation("Regular Expression"),
 *   description = @Translation("Invalidates by regular expression."),
 *   examples = {"\.(jpg|jpeg|css|js)$"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE
 * )
 */
class RegularExpression extends PluginBase implements PluginInterface {}
