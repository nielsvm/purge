<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\Service.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\Exception\InvalidExpressionException;
use Drupal\purge\Purgeable\PluginInterface;
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
  public function fromQueueItemData($data) {
    $data = explode('>', $data);
    return $this->fromNamedRepresentation($data[0], $data[1]);
  }

  /**
   * {@inheritdoc}
   */
  public function fromNamedRepresentation($plugin_id, $representation) {
    $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    $instance = new $plugin_class($representation);
    $instance->setPluginId($plugin_id);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function fromRepresentation($representation) {
    $match = NULL;
    foreach ($this->pluginManager->getDefinitions() as $id => $type) {
      try {
        $match = $this->fromNamedRepresentation($id, $representation);
      }
      catch (InvalidExpressionException $e) {
        $match = NULL;
      }
      if ((!is_null($match)) && ($match instanceof PluginInterface)) {
        break;
      }
    }
    if (is_null($match)) {
      throw new InvalidExpressionException(
        sprintf("The argument %s is not supported",
          var_export($representation, TRUE)));
    }
    return $match;
  }
}
