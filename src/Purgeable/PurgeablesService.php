<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeablesService.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\PurgeablesServiceInterface;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Provides a service that instantiates purgeable objects on-demand.
 */
class PurgeablesService extends ServiceBase implements PurgeablesServiceInterface {

  /**
   * Instantiates a PurgeablesService.
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
  public function getPluginsLoaded() {
    return array_keys($this->getPlugins());
  }

  /**
   * Returns a preconfigured instance of a purgeable.
   *
   * @see \Drupal\Component\Plugin\Factory\FactoryInterface::createInstance.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param string $representation
   *   A string representing this type of purgeable, e.g. "node/1" for a
   *   path purgeable and "*" for a full domain purgeable.
   *
   * @return \Drupal\purge\Purgeable\PurgeableInterface
   */
  private function createInstance($plugin_id, $representation) {
    $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // Instantiate the purgeable and immediately set its plugin ID.
    $instance = new $plugin_class($representation);
    $instance->setPluginId($plugin_id);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function fromQueueItemData($data) {
    $data = explode('>', $data);
    return $this->createInstance($data[0], $data[1]);
  }

  /**
   * {@inheritdoc}
   */
  public function matchFromStringRepresentation($representation) {
    $match = NULL;
    foreach ($this->pluginManager->getDefinitions() as $type) {
      try {
        $match = $this->createInstance($type['id'], $representation);
      }
      catch (InvalidStringRepresentationException $e) {
        $match = NULL;
      }
      if (!is_null($match) && is_object($match)) {
        break;
      }
    }
    if (is_null($match)) {
      throw new InvalidStringRepresentationException(
        "The string '$representation' is not supported by any purgeable types");
    }
    return $match;
  }
}

