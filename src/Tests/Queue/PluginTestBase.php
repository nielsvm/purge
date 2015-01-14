<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queue\PluginTestBase.
 */

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\TestBase;

/**
 * Provides a abstract test class to aid thorough tests for queue plugins.
 *
 * @group purge
 * @see \Drupal\purge\Queue\PluginInterface
 */
abstract class PluginTestBase extends TestBase {

  /**
   * The plugin ID of the queue plugin being tested.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * The plugin manager for queues ('plugin.manager.purge.queue').
   *
   * @var \Drupal\purge\Queue\PluginManager
   */
  protected $pluginManagerPurgeQueue;

  /**
   * The queue plugin being tested.
   *
   * @var \Drupal\purge\Queue\PluginInterface
   */
  protected $queue;

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->pluginManagerPurgeQueue =
      $this->container->get('plugin.manager.purge.queue');
    $this->setUpQueuePlugin();
  }

  /**
   * Load the queue plugin and make $this->queue available.
   */
  protected function setUpQueuePlugin() {
    if (!is_null($this->queue)) {
      return;
    }

    // Perform essential assertions and prepare common variables.
    $plugins = $this->pluginManagerPurgeQueue->getDefinitions();
    $id = $this->plugin_id;
    $this->assertTrue(isset($plugins[$id]), 'The plugin is found.');
    if (!isset($plugins[$id])) return FALSE;
    $this->assertTrue(!empty(trim((string)$plugins[$id]['label'])),
      'The label is set.');
    $this->assertTrue(!empty(trim((string)$plugins[$id]['description'])),
      'The description is set.');
    $class = basename(str_replace('\\', '/', $plugins[$id]['class']));
    $this->assertFalse(strpos($class, 'Queue'), "Class doesn't contain 'Queue'.");
    $this->assertFalse(strpos($class, 'queue'), "Class doesn't contain 'queue'.");

    // Retrieve all the requested service arguments.
    $arguments = array();
    foreach ($plugins[$id]['service_dependencies'] as $service) {
      $arguments[] = $this->container->get($service);
    }

    // Use the Reflection API to instantiate our plugin.
    $reflector = new \ReflectionClass($plugins[$id]['class']);
    $this->queue = $reflector->newInstanceArgs($arguments);

    // Create the queue and confirm the output of createQueue().
    $this->assertNull($this->queue->createQueue(), 'createQueue returns NULL');
  }

  /**
   * Clean the queue and wait a little bit.
   */
  protected function cleanAndWait() {
    $this->queue->deleteQueue();
  }

  /**
   * Test the data integrity of data stored in the queue.
   */
  function testDataStorageIntegrity() {
    $samples = array(
      'a' => 'string',
      'b' => 'StrinG with Capitalization',
      'c' => 1,
      'd' => -1500,
      'e' => 0.1500,
      'f' => -99999,
      'g' => NULL,
      'h' => FALSE,
      'i' => TRUE
    );

    // Test if we get back the exact same thing if we store it as scalar value.
    foreach ($samples as $sample) {
      $this->queue->createItem($sample);
      $reference = $this->queue->claimItem(3600);
      $this->assertIdentical($sample, $reference->data,
        var_export($sample, TRUE) . " stored identically");
    }

    // Test that we get the same data back by storing it in an object.
    $this->queue->createItem($samples);
    $reference = $this->queue->claimItem(3600);
    $this->assertIdentical($samples, $reference->data,
      "Objects stored identically");

    $this->cleanAndWait();
  }

  /**
   * Test the queue counter by deleting items and emptying the queue.
   */
  public function testQueueCountBehavior() {
    $this->assertNull($this->queue->deleteQueue(), 'deleteQueue returns NULL');
    $this->assertEqual(0, $this->queue->numberOfItems(), 'numberOfItems returns 0');
    for ($i=1; $i <= 5; $i++) {
      $id = $this->queue->createItem($i);
      $this->assertTrue(is_scalar($id), 'createItem returned a scalar.');
      $this->assertTrue($id !== FALSE, 'createItem did not return FALSE.');
      $this->assertEqual($i, $this->queue->numberOfItems(), "numberOfItems returns $i");
    }
    $this->assertTrue(is_object($this->queue->claimItem(1)), 'claimItem gives object');
    $this->assertEqual(5, $this->queue->numberOfItems(), 'numberOfItems still returns 5 after claim');
    $this->assertNull($this->queue->deleteQueue(), 'deleteQueue returns NULL');
    $this->assertEqual(0, $this->queue->numberOfItems(), 'numberOfItems returns 0');
    for ($i=1; $i <= 10; $i++) {
      $this->queue->createItem($i);
    }
    for ($i=10; $i > 5; $i--) {
      $claim = $this->queue->claimItem();
      $this->assertNull($this->queue->deleteItem($claim), 'deleteItem returns NULL');
      $this->assertEqual($i-1, $this->queue->numberOfItems(),
        "numberOfItems returns " . ($i-1));
    }
    $claims = $this->queue->claimItemMultiple(5);
    $this->queue->deleteItemMultiple($claims);
    $this->assertEqual(0, $this->queue->numberOfItems(), 'numberOfItems returns 0');

    $this->cleanAndWait();
  }

  /**
   * Test that createQueue() doesn't empty the queue if already created.
   */
  function testCreateQueue() {
    $this->queue->createItem(array(1,2,3));
    $this->queue->createQueue();
    $this->assertEqual(1, $this->queue->numberOfItems(),
      'queue not emptied after double createQueue() call');

    $this->cleanAndWait();
  }

  /**
   * Test creating, claiming and releasing of items.
   */
  function testCreatingClaimingAndReleasing() {
    $this->queue->createItem(array(1,2,3));
    $claim = $this->queue->claimItem(3600);
    $this->assertFalse($this->queue->claimItem(3600), 'second claim fails');
    $this->assertTrue($this->queue->releaseItem($claim),
      'releaseItem() returns TRUE');
    $this->assertTrue($claim = $this->queue->claimItem(3600),
      'item can be claimed after releaseItem() was called.');
    $this->queue->releaseItem($claim);
    $this->assertIdentical(4,
      count($this->queue->createItemMultiple(array(1,2,3,4))),
      "createItemMultiple() returned four id's");
    $claims = $this->queue->claimItemMultiple(5, 3600);
    $this->assertIdentical(array(),
      $this->queue->claimItemMultiple(5, 3600),
      'claimItemMultiple() returned an empty array');
    $this->assertIdentical(array(),
      $this->queue->releaseItemMultiple($claims),
      'releaseItemMultiple() returned an empty array');
    $claims = $this->queue->claimItemMultiple(5, 3600);
    $this->assertIdentical(5,
      count($claims),
      "createItemMultiple() returned 5 items");

    $this->cleanAndWait();
  }

  /**
   * Test the behavior of lease time when claiming queue items.
   */
  function testLeaseTime() {
    $this->assertFalse($this->queue->claimItem(), 'claiming on empty queue.');
    $this->queue->createItem($this->randomString());
    $this->assertEqual(1, $this->queue->numberOfItems(), 'numberOfItems returns 1');
    $this->assertTrue($this->queue->claimItem(5), 'claiming item for 5s');
    $this->assertFalse($this->queue->claimItem(), 'FALSE on direct claim');
    sleep(6);
    $this->assertTrue($this->queue->claimItem(2),
      'after lease expired (6s), we can claim it again for 2s');
    $this->assertFalse($this->queue->claimItem(1), 'FALSE on direct claim');
    sleep(3);
    $this->assertTrue($claim = $this->queue->claimItem(2),
      'after lease expired (3s), we can claim it again for 2s');
    $this->queue->deleteQueue();

    // Test claimItemMultiple which should work in the same way.
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)),
      'claiming 2 on empty queue.');
    for ($i=1; $i <= 5; $i++) {
      $this->queue->createItem($this->randomString());
    }
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)),
      'claimItemMultiple(5,5) with 5s lease time.');
    $this->assertTrue(empty($this->queue->claimItemMultiple(2)),
      'claimItemMultiple(2,5) during lease gives empty array()');
    sleep(6);
    $this->assertIdentical(5, count($this->queue->claimItemMultiple(5, 5)),
      'claimItemMultiple(5,5) after lease expired.');

    $this->cleanAndWait();
  }
}
