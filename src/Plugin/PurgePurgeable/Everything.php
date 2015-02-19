<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Everything.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;

/**
 * Describes that everything is to be invalidated.
 *
 * @PurgePurgeable(
 *   id = "everything",
 *   label = @Translation("Everything"),
 *   description = @Translation("Invalidates everything."),
 *   expression_required = FALSE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Everything extends PluginBase implements PluginInterface {}
