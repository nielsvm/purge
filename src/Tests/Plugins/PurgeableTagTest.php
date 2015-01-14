<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\PurgeableTagTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Purgeable\PluginTestBase;

/**
 * Tests the 'tag' purgeable plugin.
 *
 * @group purge
 * @see \Drupal\purge\Purgeable\PluginInterface
 */
class PurgeableTagTest extends PluginTestBase {
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
