<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\UrlInvalidationTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\UrlInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class UrlInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'url';
  protected $expressions = [
    'http://www.test.com',
    'https://domain/path',
    'http://domain/path?param=1',
  ];
  protected $expressionsInvalid = [
    NULL,
    '',
    "35423523",
    'http:// /aa',
    'http://www.test.com/*',
  ];

}
