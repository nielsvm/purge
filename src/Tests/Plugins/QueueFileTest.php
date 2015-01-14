<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\QueueFileTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests the 'file' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\PluginInterface
 */
class QueueFileTest extends PluginTestBase {
  protected $plugin_id = 'file';
}
