<?php

/**
 * @file
 * Contains \Drupal\purge_cachetags_queuer\Tests\CacheTagsInvalidationQueuerTest.
 */

namespace Drupal\purge_cachetags_queuer\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\purge\Tests\TestBase;
use Drupal\purge\Plugin\PurgePurgeable\Tag;

/**
 * Test \Drupal\purge_cachetags_queuer\CacheTagsInvalidationQueuer.
 *
 * @group purge
 * @see \Drupal\purge\Queue\ServiceInterface
 * @see \Drupal\purge\Purgeable\ServiceInterface
 */
class CacheTagsInvalidationQueuerTest extends TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_cachetags_queuer'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();

    // Configure the memory queue, which is fast, compliant and does the job.
    $this->initializeQueueService('memory');
  }

  /**
   * Test whether the DIC service is registered.
   */
  public function testTagInvalidationsAddedToQueue() {
    $test_tags = [
      'block_plugin:system_menu_block__account',
      'menu:main',
      'node:5',
      'extension:views',
      'extension',
      'node_list',
    ];

    // Confirm that the queue is empty and that there's nothing to claim yet.
    $this->assertFalse($this->purgeQueue->claim(), 'Queue starts empty');

    // Create a couple of cache objects tagged with our tags.
    foreach ($test_tags as $i => $tag) {
      \Drupal::cache()->set(
        'cache_tag_test_' . $i, $tag, Cache::PERMANENT, [$tag]);
    }

    // The listener should not directly commit tags, test the queue is empty.
    $this->assertFalse($this->purgeQueue->claim(), 'Cache objects created, queue still empty.');

    // Invalidate the tagged objects and claim an equal number from the queue.
    Cache::invalidateTags($test_tags);
    $claims = $this->purgeQueue->claimMultiple(count($test_tags));
    $this->assertEqual(count($test_tags), count($claims),
      'All invalidated tags can be claimed from the queue.');
    foreach($claims as $purgeable) {
      $this->assertTrue($purgeable instanceof Tag,
        (string)$purgeable . ' is a Tag-purgeable.');
      $this->assertTrue(in_array((string)$purgeable, $test_tags),
        (string)$purgeable . ' was one of the test tags.');
    }
  }
}
