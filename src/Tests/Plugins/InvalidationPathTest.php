<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationPathTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\Path.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationPathTest extends PluginTestBase {
  protected $plugin_id = 'path';
  protected $expressions = [
    '',
    '?page=0',
    'news',
    'news/',
    '012/442',
    'news/article-1',
    'news/article-1?page=0&secondparam=1'
  ];
  protected $expressionsInvalid = [
    NULL,
    '/news',
    'news/*',
    '/news/*',
    '*'
  ];
  
}
