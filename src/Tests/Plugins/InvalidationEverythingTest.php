<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationEverythingTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\Everything.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationEverythingTest extends PluginTestBase {
  protected $plugin_id = 'everything';
  protected $expressions = [NULL];
  protected $expressionsInvalid = ['', 'foobar'];

}
