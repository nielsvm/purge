<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\PurgeableWildcardPathTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Purgeable\PluginTestBase;

/**
 * Tests the 'wildcardpath' purgeable plugin.
 *
 * @group purge
 * @see \Drupal\purge\Purgeable\PluginInterface
 */
class PurgeableWildcardPathTest extends PluginTestBase {
  protected $plugin_id = 'wildcardpath';
  protected $representations = [
    '/*',
    '/*?page=0',
    '/news/*',
    '/products/*'
  ];
  protected $representationsInvalid = [
    '*',
    '/',
    '/?page=0',
    '/news',
    '/news/',
    '/012/442',
    '/news/article-1',
    '/news/article-1?page=0&secondparam=1'
  ];
}
