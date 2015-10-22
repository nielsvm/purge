<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\DomainInvalidation.
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
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE
 * )
 */
class DomainInvalidation extends PluginBase implements PluginInterface {}
