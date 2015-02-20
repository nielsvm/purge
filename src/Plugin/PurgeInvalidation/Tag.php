<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\Tag.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Invalidation\PluginInterface;
use Drupal\purge\Invalidation\PluginBase;

/**
 * Describes invalidation by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @PurgeInvalidation(
 *   id = "tag",
 *   label = @Translation("Tag"),
 *   description = @Translation("Invalidates by Drupal cache tag."),
 *   examples = {"node:1", "menu:footer"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class Tag extends PluginBase implements PluginInterface {}
