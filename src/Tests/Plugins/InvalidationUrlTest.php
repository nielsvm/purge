<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationUrlTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\Url.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationUrlTest extends PluginTestBase {
  protected $plugin_id = 'url';
  protected $expressions = ['http://www.test.com', 'https://domain/path'];
  protected $expressionsInvalid = [NULL, '', 'http:// /aa', 'ftp://test.com/path'];
}
