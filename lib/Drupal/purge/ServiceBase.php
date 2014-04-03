<?php

/**
 * @file
 * Contains \Drupal\purge\ServiceBase.
 */

namespace Drupal\purge;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\purge\ServiceInterface;

/**
 * Provides a generic service for all DIC-registered service classes by Purge.
 */
abstract class ServiceBase extends ServiceProviderBase implements ServiceInterface {

  /**
   * An object containing the namespaces to look for plugin implementations.
   *
   * @var \Traversable
   */
  protected $containerNamespaces;

  /**
   * The discovery object that tells us which plugins are available.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The object factory that can instantiate plugins whenever we need them.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $factory;

  /**
   * Initialize plugin discovery and a factory.
   *
   * @param \Traversable $container_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param string $plugin_type
   *   The type of plugin being required by the service, for which a annotation
   *   type exists under \Drupal\purge\Annotation\ and which will be discovered
   *   under the namespace 'Plugin/$plugin_type'.
   *
   * @param NULL
   *    This method does not return anything but it does make $this->discovery
   *    and $this->factory available.
   */
  protected function initializePluginDiscovery(\Traversable $container_namespaces, $plugin_type) {
    $this->containerNamespaces = $container_namespaces;

    // Setup annotated plugin discovery with its dedicated annotation type.
    $this->discovery = new AnnotatedClassDiscovery(
      'Plugin/' . $plugin_type,
      $this->containerNamespaces,
      'Drupal\purge\Annotation\\' . $plugin_type
    );

    // Overload the discoverer with the CacheDecorator to reduce IO reads.
    $cache_key = 'purge_' . strtolower($plugin_type) . '_types';
    $this->discovery = new CacheDecorator($this->discovery, $cache_key);

    // Setup the factory which can instantiate these objects at will.
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (!$simple) {
      return $this->discovery->getDefinitions();
    }
    $plugins = array();
    foreach ($this->discovery->getDefinitions() as $plugin) {
      $plugins[$plugin['id']] = $plugin['label'];
    }
    return $plugins;
  }
}