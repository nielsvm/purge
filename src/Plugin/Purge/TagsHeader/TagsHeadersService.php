<?php

namespace Drupal\purge\Plugin\Purge\TagsHeader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\IteratingServiceBaseTrait;
use Drupal\purge\ServiceBase;

/**
 * Provides a service that provides access to available tags headers.
 */
class TagsHeadersService extends ServiceBase implements TagsHeadersServiceInterface {
  use IteratingServiceBaseTrait;

  /**
   * Construct the tags headers service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  public function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   *
   * @ingroup countable
   */
  public function count() {
    $this->initializePluginInstances();
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->reloadIterator();
  }

}
