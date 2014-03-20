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
use Drupal\purge\Purgeable\PurgeableFactoryInterface;
use Drupal\purge\Purgeable\InvalidPurgeableConstruction;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Factory responsible for generating purgeable objects.
 */
class PurgeableFactory extends PluginManagerBase implements PurgeableFactoryInterface {

  /**
   * Constructs the PurgeableFactory.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/Purgeable', $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, 'purge_purgeable_types');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * {@inheritdoc}
   */
  public function fromQueueItemData($data) {
    return $this->createInstance($data[0], array($data[1]));
  }

  /**
   * {@inheritdoc}
   */
  public function matchFromStringRepresentation($representation) {
    $match = NULL;
    foreach ($this->getDefinitions() as $type) {
      try {
        $match = $this->createInstance($type['id'], array($representation));
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
    return new $plugin_class($configuration[0]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (count($options) !== 1) {
      throw new InvalidPurgeableConstruction("Only one string representation is allowed.");
    }
    if (!is_string($options[0])) {
      throw new InvalidPurgeableConstruction("First array value should be a string.");
    }
    return $this->matchFromStringRepresentation($options[0]);
  }
}

