<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\EverythingInvalidationTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\EverythingInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class EverythingInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'everything';
  protected $expressions = [NULL];
  protected $expressionsInvalid = ['', 'foobar'];

}
