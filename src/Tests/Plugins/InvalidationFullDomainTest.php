<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationFullDomainTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests the 'fulldomain' invalidation type.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationFullDomainTest extends PluginTestBase {
  protected $plugin_id = 'fulldomain';
  protected $representations = ['*'];
  protected $representationsInvalid = ['/*'];
}
