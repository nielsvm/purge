<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\PurgeableFullDomainTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Purgeable\PluginTestBase;

/**
 * Tests the 'fulldomain' purgeable plugin.
 *
 * @group purge
 * @see \Drupal\purge\Purgeable\PluginInterface
 */
class PurgeableFullDomainTest extends PluginTestBase {
  protected $plugin_id = 'fulldomain';
  protected $representations = ['*'];
  protected $representationsInvalid = ['/*'];
}
