<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Tag.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface;
use Drupal\purge\Purgeable\PluginBase;

/**
 * Describes invalidation by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @PurgePurgeable(
 *   id = "tag",
 *   label = @Translation("Tag"),
 *   description = @Translation("Invalidates by Drupal cache tag."),
 *   examples = {"node:1", "menu:footer"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Tag extends PluginBase implements PluginInterface {}
