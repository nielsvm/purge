<?php

/**
 * @file
 * Contains \Drupal\Tests\purge_cachetags_queuer\Unit\CacheTagsQueuerTest.
 */

namespace Drupal\Tests\purge_cachetags_queuer\Unit;

use Drupal\purge_cachetags_queuer\CacheTagsQueuer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheTagsQueuer
 * @group purge
 */
class CacheTagsQueuerTest extends UnitTestCase {

  /**
   * The tested cache tags queuer.
   *
   * @var \Drupal\purge_cachetags_queuer\CacheTagsQueuer
   */
  protected $cacheTagsQueuer;

  /**
   * The mocked queue.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Queue\ServiceInterface
   */
  protected $queue;

  /**
   * The mocked purgeable factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgeableFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->queue = $this->getMock('\Drupal\purge\Queue\ServiceInterface');
    $this->purgeableFactory = $this->getMock('\Drupal\purge\Purgeable\ServiceInterface');
    $this->cacheTagsQueuer = new CacheTagsQueuer($this->queue, $this->purgeableFactory);
  }

  /**
   * @covers ::invalidateTags
   *
   * @dataProvider providerTestInvalidateTags()
   */
  public function testInvalidateTags(array $tag_invalidations, $purgeable_instantiations, array $queue_additions) {
    $tag_purgeable = $this->getMockBuilder('\Drupal\purge\Plugin\PurgePurgeable\Tag')
      ->disableOriginalConstructor();
    $this->purgeableFactory->expects($this->exactly($purgeable_instantiations))
      ->method('fromNamedRepresentation')
      ->with('tag')
      ->willReturn($tag_purgeable);

    $this->queue->expects($this->exactly(count($tag_invalidations)))
      ->method('addMultiple');
    for ($i = 0; $i < count($tag_invalidations); $i++) {
      $this->queue->expects($this->at($i))
        ->method('addMultiple')
        ->with($this->callback(function($purgeables) use ($queue_additions, $i) {
          // Ensure we have an array of purgeables of the right size.
          return is_array($purgeables) && count($purgeables) == $queue_additions[$i];
        }));
    }

    // Perform the provided tag invalidations.
    foreach ($tag_invalidations as $tags) {
      $this->cacheTagsQueuer->invalidateTags($tags);
    }
  }

  /**
   * Provides test data for testInvalidateTags().
   */
  public function providerTestInvalidateTags() {
    $tags = [
      'menu:main',
      'node:5',
      'extension:views',
      'extension',
      'node_list',
    ];

    // Each of these test cases simulate the cache tag invalidations within one
    // request.
    return [
      // Many invalidations with one tag each.
      [
        [[$tags[0]], [$tags[1]], [$tags[2]], [$tags[3]], [$tags[4]]],
        5,
        [1, 1, 1, 1, 1],
      ],
      // One invalidation with many tags.
      [
        [[$tags[0], $tags[1], $tags[2], $tags[3], $tags[4]]],
        5,
        [5],
      ],
      // Two invalidations, with 2 and 3 tags respectively.
      [
        [[$tags[0], $tags[1]], [$tags[2], $tags[3], $tags[4]]],
        5,
        [2, 3],
      ]
    ];
  }

}
