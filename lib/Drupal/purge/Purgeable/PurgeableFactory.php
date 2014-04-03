<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableFactory.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\purge\Purgeable\InvalidPurgeableConstruction;

/**
 * Provides a factory that instantiates purgeable objects.
 */
class PurgeableFactory extends PluginManagerBase {

  /**
   * Constructs the PurgeableFactory.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/PurgePurgeable', $namespaces, 'Drupal\purge\Annotation\PurgePurgeable');
    $this->discovery = new CacheDecorator($this->discovery, 'purge_purgeable_types');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Gets the definition of all plugins for this type.
   *
   * @return mixed
   *   An array of plugin definitions (empty array if no definitions were
   *   found).
   */
  public function getDefinitions() {
    return $this->discovery->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    if (count($configuration) !== 1) {
      throw new InvalidPurgeableConstruction("Only one string representation is allowed.");
    }
    if (!is_string($configuration[0])) {
      throw new InvalidPurgeableConstruction("First array value should be a string.");
    }

    // Instantiate the purgeable and immediately set its plugin ID.
    $instance = new $plugin_class($configuration[0]);
    $instance->setPluginId($plugin_id);

    return $instance;
  }
}

