<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeablesService.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\ServiceBase;
use Drupal\purge\Purgeable\PurgeablesServiceInterface;
use Drupal\purge\Purgeable\PurgeableFactory;
use Drupal\purge\Purgeable\InvalidStringRepresentationException;

/**
 * Provides a service that instantiates purgeable objects on-demand.
 */
class PurgeablesService extends ServiceBase implements PurgeablesServiceInterface {

  /**
   * The PurgeableFactory object that generates the purgeable objects.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $factory;

  /**
   * Instantiates a PurgeablesService.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces) {
    $this->factory = new PurgeableFactory($namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (!$simple) {
      return $this->factory->getDefinitions();
    }
    $plugins = array();
    foreach ($this->factory->getDefinitions() as $plugin) {
      $plugins[$plugin['id']] = $plugin['label'];
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function fromQueueItemData($data) {
    $data = explode('>', $data);
    return $this->factory->createInstance($data[0], array($data[1]));
  }

  /**
   * {@inheritdoc}
   */
  public function matchFromStringRepresentation($representation) {
    $match = NULL;
    foreach ($this->factory->getDefinitions() as $type) {
      try {
        $match = $this->factory->createInstance($type['id'], array($representation));
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

