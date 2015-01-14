<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\PurgeablePathTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Purgeable\PluginTestBase;

/**
 * Tests the 'path' purgeable plugin.
 *
 * @group purge
 * @see \Drupal\purge\Purgeable\PluginInterface
 */
class PurgeablePathTest extends PluginTestBase {
  protected $plugin_id = 'path';
  protected $representations = [
    '/',
    '/?page=0',
    '/news',
    '/news/',
    '/012/442',
    '/news/article-1',
    '/news/article-1?page=0&secondparam=1'
  ];
  protected $representationsInvalid = [
    'news',
    'news?page=0',
    'news/*',
    '/news/*',
    '*',
    '/news /subpath'
  ];
}
