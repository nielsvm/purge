<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\TagInvalidationTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\TagInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class TagInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'tag';
  protected $expressions = [
    'tag',
    'user:1',
    'menu:footer'
  ];
  protected $expressionsInvalid = [
    NULL,
    '',
    ['node', '1'],
    'wildtag:*'
  ];

}
