<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsService.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;

/**
 * Provides a service that instantiates invalidation objects on-demand.
 */
class InvalidationsService extends ServiceBase implements InvalidationsServiceInterface {

  /**
   * Incremental ID counter for handing out unique instance IDs.
   *
   * @var int
   */
  protected $instance_counter = 0;

  /**
   * Instantiates a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsService.
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
        'id' => $this->instance_counter++,
        'expression' => $expression
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFromQueueData($item_data) {
    $instance = $this->get($item_data[0], $item_data[2]);
    $instance->setState($item_data[1]);
    return $instance;
  }

}
