<?php

namespace Drupal\purge\Tests\Queue;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\MemoryQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class MemoryQueueTest extends PluginTestBase {
  protected $pluginId = 'memory';

}
