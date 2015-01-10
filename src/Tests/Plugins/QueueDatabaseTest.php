<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\QueueDatabaseTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests the 'database' queue plugin.
 *
 * @group purge
 * @see \Drupal\purge\Queue\QueueInterface
 */
class QueueDatabaseTest extends PluginTestBase {
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
