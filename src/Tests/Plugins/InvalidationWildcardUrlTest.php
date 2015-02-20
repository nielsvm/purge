<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationWildcardUrlTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\WildcardUrl.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationWildcardUrlTest extends PluginTestBase {
  protected $plugin_id = 'wildcardurl';
  protected $expressions = ['http://www.test.com/*', 'https://domain/path/*'];
  protected $expressionsInvalid = [
    NULL,
    '',
    'http:// /aa',
    'ftp://test.com/path',
    'http://www.test.com',
    'https://domain/path'
  ];
}
