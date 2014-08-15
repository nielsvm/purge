<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\PurgeQueueFileTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\PurgeQueueTestBase;

/**
 * Tests the 'file' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\QueueInterface
 */
class PurgeQueueFileTest extends PurgeQueueTestBase {
  protected $plugin_id = 'file';
}
