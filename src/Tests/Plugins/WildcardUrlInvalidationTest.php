<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\WildcardUrlInvalidationTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\WildcardUrlInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\PluginInterface
 */
class WildcardUrlInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'wildcardurl';
  protected $expressions = ['http://www.test.com/*', 'https://domain/path/*'];
  protected $expressionsInvalid = [
    NULL,
    '',
    'http:// /aa',
    'http://www.test.com',
    'https://domain/path'
  ];

}
