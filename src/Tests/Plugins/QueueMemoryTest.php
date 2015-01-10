<?php

/**
* @file
* Contains \Drupal\purge\Tests\Plugins\QueueMemoryTest.
*/

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
* Tests the 'memory' queue plugin.
*
* @group purge
* @see \Drupal\purge\Queue\QueueInterface
*/
class QueueMemoryTest extends PluginTestBase {
  protected $plugin_id = 'memory';
}
