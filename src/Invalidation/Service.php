<?php

/**
 * @file
 * Contains \Drupal\purge\Invalidation\Service.
 */

namespace Drupal\purge\Invalidation;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Invalidation\ServiceInterface;

/**
 * Provides a service that instantiates invalidation objects on-demand.
 */
class Service extends ServiceBase implements ServiceInterface {

  /**
   * Incremental ID counter for handing out unique instance_id's.
   *
   * @var int
   */
  protected $instance_counter = 0;

  /**
   * Instantiates a \Drupal\purge\Invalidation\Service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  public function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($plugin_id, $expression = NULL) {
    return $this->pluginManager->createInstance(
      $plugin_id, [
        'instance_id' => $this->instance_counter++,
        'expression' => $expression
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFromQueueData($item_data) {
    $item_data = explode('>', $item_data);
    return $this->get($item_data[0], $item_data[1]);
  }

}
