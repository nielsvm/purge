<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Service.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\ServiceInterface;

/**
 * Provides a service that instantiates purgeable objects on-demand.
 */
class Service extends ServiceBase implements ServiceInterface {

  /**
   * Instantiates a \Drupal\purge\Purgeable\Service.
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
    return $this->pluginManager->createInstance($plugin_id, ['expression' => $expression]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFromQueueData($item_data) {
    $item_data = explode('>', $item_data);
    return $this->new($item_data[0], $item_data[1]);
  }

}
