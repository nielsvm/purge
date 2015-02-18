<?php

/**
 * @file
 * Contains \Drupal\purge_cachetags_queuer\CacheTagsQueuer.
 */

namespace Drupal\purge\Queuer;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Purgeable\ServiceInterface as PurgeableServiceInterface;

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
   * @var \Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgePurgeables;

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
   * @param \Drupal\purge\Purgeable\ServiceInterface $purge_purgeables
   *   The purgeables factory service.
   */
  public function __construct(QueueServiceInterface $purge_queue, PurgeableServiceInterface $purge_purgeables) {
    $this->purgeQueue = $purge_queue;
    $this->purgePurgeables = $purge_purgeables;
  }

  /**
   * {@inheritdoc}
   *
   * Queues invalidated cache tags as tag purgables.
   */
   public function invalidateTags(array $tags) {
    $purgeables = [];

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
          $purgeables[] = $this->purgePurgeables->fromNamedRepresentation('tag', $tag);
          $this->invalidatedTags[] = $tag;
        }
      }
    }

    // The queue buffers purgeables, though we don't care about that here.
    $this->purgeQueue->addMultiple($purgeables);
  }

}
