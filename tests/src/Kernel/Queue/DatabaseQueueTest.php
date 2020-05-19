<?php

namespace Drupal\Tests\purge\Kernel\Queue;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\DatabaseQueue.
 *
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class DatabaseQueueTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'database';

}
