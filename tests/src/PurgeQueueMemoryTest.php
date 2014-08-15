<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\PurgeQueueMemoryTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\PurgeQueueTestBase;

/**
 * Tests the 'memory' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\QueueInterface
 */
class PurgeQueueMemoryTest extends PurgeQueueTestBase {
  protected $plugin_id = 'memory';
}
