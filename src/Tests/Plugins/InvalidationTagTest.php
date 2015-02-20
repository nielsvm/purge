<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationTagTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests the 'tag' invalidation type.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationTagTest extends PluginTestBase {
  protected $plugin_id = 'tag';
  protected $representations = [
    'tag',
    'user:1',
    'menu:footer'
  ];
  protected $representationsInvalid = [
    ['node', '1'],
    'wildtag:*',
    '/path:notation'
  ];
}
