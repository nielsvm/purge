<?php

namespace Drupal\Tests\purge\Kernel\Queue;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\MemoryQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class MemoryQueueTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'memory';

}
