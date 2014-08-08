<?php

/**
 * @file
 * Contains \Drupal\purge_core_invalidation\CacheTagDeletionListener.
 */

namespace Drupal\purge_core_invalidation;

use Drupal\Core\Cache\NullBackend;
use Drupal\purge\Queue\QueueServiceInterface;
use Drupal\purge\Purgeable\PurgeablesServiceInterface;

/**
 * Fake cache back-end that queues deleted cache tags (for getting purged).
 */
class CacheTagDeletionListener extends NullBackend {

  /**
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Purgeable\PurgeablesServiceInterface
   */
  protected $purgePurgeables;

  /**
   * @var array
   */
  protected $tags;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Purgeable\PurgeablesServiceInterface $purge_purgeables
   *   The purgeables factory service.
   */
  public function __construct(QueueServiceInterface $purge_queue, PurgeablesServiceInterface $purge_purgeables) {
    parent::__construct(__CLASS__);
    $this->purgeQueue = $purge_queue;
    $this->purgePurgeables = $purge_purgeables;
    $this->tags = array();
  }

  /**
   * Flattens a tags array into a numeric array suitable for string storage.
   *
   * @param array $tags
   *   Associative array of tags to flatten.
   *
   * @return array
   *   Indexed array of flattened tag identifiers.
   */
  protected function flattenTags(array $tags) {
    if (isset($tags[0])) {
      return $tags;
    }

    $flat_tags = array();
    foreach ($tags as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $flat_tags[] = "$namespace:$value";
        }
      }
      else {
        $flat_tags[] = "$namespace:$values";
      }
    }
    return $flat_tags;
  }

  /**
   * Collect the given tags and prevent in-request tag duplication.
   *
   * @param array $flattened_tags
   *   Indexed array of flatten tag identifiers.
   */
  protected function collectTags(array $flattened_tags) {
    foreach ($flattened_tags as $tag) {
      if (!in_array($tag, $this->tags)) {
        $this->tags[] = $tag;
      }
    }
  }

  /**
   * Queue collected tags as tag purgeables using the '@purge.queue' service.
   */
  protected function addTagsToQueue() {
    if (!empty($this->tags)) {
      $purgeables = array();
      foreach ($this->tags as $tag) {
        $purgeables[] = $this->purgePurgeables->matchFromStringRepresentation($tag);
      }

      // In reality PurgerService::addMultiple() will not even directly add the
      // items to its queue either, but keeps a buffer and commit mechanism as
      // well. However, as we are preventing double calls to these, the best
      // conditions are created to keep writing to the queue affordable.
      $this->purgeQueue->addMultiple($purgeables);

      // Theoretically this destructor can get called multiple times, so the
      // buffer has to be empty again; after we passed the tags to the queue.
      $this->tags = array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTags(array $tags) {
    $this->collectTags($this->flattenTags($tags));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $this->collectTags($this->flattenTags($tags));
  }

  /**
   * Write all collected tags to the queue upon object destruction.
   */
  function __destruct() {

    // Adding items to the queue is expensive, especially at the rate that
    // deleteTags() and invalidateTags() are called during single requests. So
    // by keeping a buffer during runtime we can add the tags to the queue in
    // one go, which will result in a minimum amount of queries/disk writes/etc.
    $this->addTagsToQueue();
  }
}