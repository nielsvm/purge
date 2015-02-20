<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationWildcardPathTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\WildcardPath.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationWildcardPathTest extends PluginTestBase {
  protected $plugin_id = 'wildcardpath';
  protected $expressions = [
    '*',
    '*?page=0',
    'news/*',
    'products/*'
  ];
  protected $expressionsInvalid = [
    NULL,
    '',    
    '/*',
    '/',
    '?page=0',
    'news',
    'news/',
    '012/442',
    'news/article-1',
    'news/article-1?page=0&secondparam=1'
  ];
}
