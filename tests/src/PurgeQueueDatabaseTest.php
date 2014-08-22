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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', array('queue'));
  }
}
