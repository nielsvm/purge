<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Everything.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;

/**
 * Describes that everything is to be invalidated.
 *
 * @PurgeInvalidation(
 *   id = "everything",
 *   label = @Translation("Everything"),
 *   description = @Translation("Invalidates everything."),
 *   expression_required = FALSE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Everything extends PluginBase implements PluginInterface {}
