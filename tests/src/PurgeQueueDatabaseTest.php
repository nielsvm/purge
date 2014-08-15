<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\PurgeQueueDatabaseTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\PurgeQueueTestBase;

/**
 * Tests the 'database' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\QueueInterface
 */
class PurgeQueueDatabaseTest extends PurgeQueueTestBase {
  protected $plugin_id = 'database';
}
