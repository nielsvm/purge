<?php

/**
 * @file
 * Contains \Drupal\purge_cachetags_queuer\CacheTagsInvalidationQueuer.
 */

namespace Drupal\purge_cachetags_queuer;

use \Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Purgeable\ServiceInterface as PurgeableServiceInterface;

/**
 * Catch cachetags Drupal invalidates, and feed them to the purge.queue service.
 */
class CacheTagsInvalidationQueuer implements CacheTagsInvalidatorInterface {

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Purgeable\ServiceInterface
   */
  protected $purgePurgeables;

  /**
   * Holds a breadcrumb of all queued tags during this request.
   *
   * @var array
   */
  protected $breadcrumb = array();

  /**
   * {@inheritdoc}
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
   * Queue collected tags as tag purgeables using the '@purge.queue' service.
   */
   public function invalidateTags(array $tags) {
    $purgeables = array();
    foreach ($tags as $i => $tag) {
      if (!is_int($i)) {
        throw new \LogicException('$tags item key is not an integer: "' . $i
        . '" => "' . $tag . '"');
      }
      if (!in_array($tag, $this->breadcrumb)) {
        $purgeables[] = $this->purgePurgeables
          ->matchFromStringRepresentation($tag);
        $this->breadcrumb[] = $tag;
      }
    }

    if (count($purgeables)) {

      // Under the hood \Drupal\purge\Queue\Service will buffer all transactions
      // before writing to database/memory/disk, and only really do so bundled
      // together at the end of each request. This helps efficiency enormously.
      $this->purgeQueue->addMultiple($purgeables);
    }
  }
}
