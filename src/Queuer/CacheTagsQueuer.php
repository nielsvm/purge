<?php

/**
 * @file
 * Contains \Drupal\purge_cachetags_queuer\CacheTagsQueuer.
 */

namespace Drupal\purge\Queuer;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Invalidation\ServiceInterface as InvalidationServiceInterface;

/**
 * Queues invalidated cache tags.
 */
class CacheTagsQueuer implements CacheTagsInvalidatorInterface {

  /**
   * The purge queue service.
   *
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Invalidation\ServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * A list of tag prefixes that should not go into the queue.
   *
   * @var string[]
   */
  protected $blacklistedTagPrefixes = ['config:', 'configFindByPrefix'];

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var string[]
   */
  protected $invalidatedTags = [];

  /**
   * Constructs a new CacheTagsQueuer.
   *
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Invalidation\ServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   */
  public function __construct(QueueServiceInterface $purge_queue, InvalidationServiceInterface $purge_invalidation_factory) {
    $this->purgeQueue = $purge_queue;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Queues invalidated cache tags as tag purgables.
   */
   public function invalidateTags(array $tags) {
    $invalidations = [];

    // Iterate each given tag and only add those we didn't queue before.
    foreach ($tags as $tag) {
      if (!in_array($tag, $this->invalidatedTags)) {

        // Check the tag against the blacklist and skip if it matches.
        $blacklisted = FALSE;
        foreach ($this->blacklistedTagPrefixes as $prefix) {
          if (strpos($tag, $prefix) !== FALSE) {
            $blacklisted = TRUE;
          }
        }
        if (!$blacklisted) {
          $invalidations[] = $this->purgeInvalidationFactory->get('tag', $tag);
          $this->invalidatedTags[] = $tag;
        }
      }
    }

    // The queue buffers invalidations, though we don't care about that here.
    $this->purgeQueue->addMultiple($invalidations);
  }

}
