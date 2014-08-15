<?php

/**
 * @file
 * Contains \Drupal\purge_core_invalidation\Tests\PurgeCoreInvalidationTest.
 */

namespace Drupal\purge_core_invalidation\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Cache\Cache;
use Drupal\purge\Plugin\PurgePurgeable\Tag;

/**
 * Test the outer and inner workings of CacheTagDeletionListener.
 *
 * @group purge
 * @see \Drupal\purge_core_invalidation\CacheTagDeletionListener
 * @see \Drupal\purge\Queue\QueueServiceInterface
 * @see \Drupal\purge\Purgeable\PurgeableServiceInterface
 */
class PurgeCoreInvalidationTest extends WebTestBase {

  /**
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Purgeable\PurgeableServiceInterface
   */
  protected $purgePurgeables;

  /**
   * @var \Drupal\purge_core_invalidation\CacheTagDeletionListener
   */
  protected $listener;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('purge_core_invalidation');

  function setUp() {
    parent::setUp();
    $this->purgeQueue = $this->container->get('purge.queue');
    $this->purgePurgeables = $this->container->get('purge.purgeables');
    $this->listener = $this->container->get('purge_core_invalidation.listener');
  }

  /**
   * Test whether the DIC service is registered.
   */
  public function testTagDeletionsAddedToQueue() {
    $test_tags = array(
      'tag:1' => array('tag' => '1'),
      'tag:2' => array('tag' => '2'),
      'tag:3' => array('tag' => '3'),
      'tag:4' => array('tag' => '4'),
    );

    // Confirm that the queue is empty and that there's nothing to claim yet.
    $this->assertFalse($this->purgeQueue->claim(), 'Queue starts empty');

    // Create and delete/validate each test tag.
    $op = 'delete';
    foreach ($test_tags as $flat => $tag) {
      \Drupal::cache()->set($flat, $tag['tag'], Cache::PERMANENT, $tag);
      if ($op == 'delete') {
        Cache::deleteTags($tag);
        $op = 'invalidate';
      }
      elseif ($op == 'invalidate') {
        Cache::invalidateTags($tag);
        $op = 'delete';
      }
    }

    // The listener should not directly commit tags, test the queue is empty.
    $this->assertFalse($this->purgeQueue->claim(), 'Tags wiped, queue still empty.');

    // Call the destructor - which is dirty - to enforce commits to the queue.
    $this->listener->__destruct();

    // Collect all tags in the queue and test if ours are in there.
    $claims = $this->purgeQueue->claimMultiple(1000);
    foreach ($test_tags as $flat => $tag) {
      $found = FALSE;
      foreach ($claims as $claim) {
        if ((string)$claim === $flat) {
          $found = $claim;
          break;
        }
      }
      $this->assertTrue($found, "$flat is in the queue.");
      $this->assertTrue($found instanceof Tag, "$flat is a Tag-purgeable.");
    }
  }
}
