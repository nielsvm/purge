<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationRouteTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\Route.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationRouteTest extends PluginTestBase {
  protected $plugin_id = 'route';
  protected $expressions = ['<front>', 'user.page'];
  protected $expressionsInvalid = [NULL, '', 'nonexisting.route'];
}
