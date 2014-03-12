<?php

/**
 * @file
 * Contains \Drupal\purge\Purgeable\PurgeableFactory.
 */

namespace Drupal\purge\Purgeable;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\purge\Purgeable\PurgeableFactoryInterface;

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
  }

  /**
   * {@inheritdoc}
   */
  public function fromQueueItemData($data) {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function matchFromUserInputLine($representation) {
    throw new \Exception(__FUNCTION__ . ' unimplemented.');
  }
}

