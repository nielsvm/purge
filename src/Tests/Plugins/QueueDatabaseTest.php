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
 * @see \Drupal\purge\Queue\PluginInterface
 */
class QueueDatabaseTest extends PluginTestBase {
  protected $plugin_id = 'database';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', ['queue']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpQueuePlugin() {
    $this->queue = $this->pluginManagerPurgeQueue->createInstance($this->plugin_id);
    $this->assertNull($this->queue->createQueue(), 'createQueue returns NULL');
  }

  /**
   * {@inheritdoc}
   */
  function testDataStorageIntegrity() {
    $this->setUpQueuePlugin();
    parent::testDataStorageIntegrity();
  }

  /**
   * {@inheritdoc}
   */
  public function testQueueCountBehavior() {
    $this->setUpQueuePlugin();
    parent::testQueueCountBehavior();
  }

  /**
   * {@inheritdoc}
   */
  function testCreateQueue() {
    $this->setUpQueuePlugin();
    parent::testCreateQueue();
  }

  /**
   * {@inheritdoc}
   */
  function testCreatingClaimingAndReleasing() {
    $this->setUpQueuePlugin();
    parent::testCreatingClaimingAndReleasing();
  }

  /**
   * {@inheritdoc}
   */
  function testLeaseTime() {
    $this->setUpQueuePlugin();
    parent::testLeaseTime();
  }

}
