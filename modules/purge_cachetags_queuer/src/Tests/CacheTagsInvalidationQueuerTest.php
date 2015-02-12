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
   * {@inheritdoc}
   */
  public static $modules = ['purge_cachetags_queuer'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Configure the memory queue, which is fast, compliant and does the job.
    $this->initializeQueueService('memory');
  }

  /**
   * Tests whether the cache tags queuer works as expected.
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
    $this->assertFalse($this->purgeQueue->claim());

    // Create a couple of cache items tagged with our test tags.
    foreach ($test_tags as $i => $tag) {
      \Drupal::cache()->set('cache_tag_test_' . $i, $this->randomString(), Cache::PERMANENT, [$tag]);
    }

    // The listener should not directly commit tags, test the queue is still empty.
    $this->assertFalse($this->purgeQueue->claim(), 'Cache objects created, queue still empty.');

    // Invalidate the tagged objects and claim an equal number from the queue.
    Cache::invalidateTags($test_tags);
    $claims = $this->purgeQueue->claimMultiple(count($test_tags));
    $this->assertEqual(count($test_tags), count($claims), 'All invalidated tags can be claimed from the queue.');
    foreach($claims as $purgeable) {
      $this->assertTrue($purgeable instanceof Tag, (string)$purgeable . ' is a Tag-purgeable.');
      $this->assertTrue(in_array((string)$purgeable, $test_tags), (string)$purgeable . ' was one of the test tags.');
    }
  }

}
