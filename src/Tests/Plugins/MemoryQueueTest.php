<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\MemoryQueueTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeQueue\MemoryQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\PluginInterface
 */
class MemoryQueueTest extends PluginTestBase {
  protected $plugin_id = 'memory';

}
