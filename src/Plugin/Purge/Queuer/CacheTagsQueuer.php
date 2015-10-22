<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queuer\CacheTagsQueuer.
 */

namespace Drupal\purge\Plugin\Purge\Queuer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;

/**
 * Queues invalidated cache tags.
 */
class CacheTagsQueuer implements CacheTagsInvalidatorInterface, QueuerInterface {
  use StringTranslationTrait;

  /**
   * The container id of this queuer.
   *
   * @var string
   */
  protected $id;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Whether this queuer is enabled.
   *
   * @var bool
   */
  protected $status;

  /**
   * The purge queue service.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueServiceInterface $purge_queue, InvalidationsServiceInterface $purge_invalidation_factory) {
    $this->configFactory = $config_factory;
    $this->status = $this->configFactory->get('purge.cache_tags_queuer')->get('status');
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeQueue = $purge_queue;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->configFactory->getEditable('purge.cache_tags_queuer')->set('status', FALSE)->save();
    $this->status = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->configFactory->getEditable('purge.cache_tags_queuer')->set('status', TRUE)->save();
    $this->status = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   *
   * Queues invalidated cache tags as tag purgables.
   */
   public function invalidateTags(array $tags) {
    if (!$this->status) {
      return;
    }
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
          try {
            $invalidations[] = $this->purgeInvalidationFactory->get('tag', $tag);
            $this->invalidatedTags[] = $tag;
          }
          catch (PluginNotFoundException $e) {
            // When Drupal uninstalls Purge, rebuilds plugin caches it might
            // run into the condition where the tag plugin isn't available. In
            // these scenarios we want the queuer to silently fail.
            return;
          }
        }
      }
    }

    // The queue buffers invalidations, though we don't care about that here.
    if (count($invalidations)) {
      $this->purgeQueue->addMultiple($invalidations);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t("Tags");
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Monitors Drupal's own content invalidations.");
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

}
