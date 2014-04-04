<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PurgerService.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purger\PurgerServiceInterface;
use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * Provides the service that allows transparent access to one or more purgers.
 */
class PurgerService extends ServiceBase implements PurgerServiceInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $serviceContainer;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Holds all loaded external cache purgers.
   *
   * @var array
   */
  protected $purgers;

  /**
   * Instantiate the purger service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Traversable $container_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  function __construct(ContainerInterface $service_container, ConfigFactoryInterface $config_factory, \Traversable $container_namespaces) {
    $this->serviceContainer = $service_container;
    $this->configFactory = $config_factory;

    // Initialize the plugin discovery, factory and set container_namespaces.
    $this->initializePluginDiscovery($container_namespaces, 'PurgePurger');

    // Instantiate all the purgers and let them configure themselves.
    $this->initializePurgers();
  }

  /**
   * Retrieve a list of all available plugins providing the service.
   *
   * @param bool $simple
   *   When provided TRUE the returned values should provide plugin name strings.
   *
   * @return array
   *   Associative array with the machine names as key and the additional plugin
   *   metadata as another associative array in the value.
   */
  public function getPlugins($simple = FALSE) {
    static $definitions;
    if (is_null($definitions)) {
      $definitions = $this->discovery->getDefinitions();
      unset($definitions['dummy']);
    }
    if (!$simple) {
      return $definitions;
    }
    $plugins = array();
    foreach ($definitions as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['label'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsLoaded() {
    static $plugin_ids;
    if (is_null($plugin_ids)) {
      $plugins = $this->configFactory->get('purge.purger')->get('plugins');
      $plugin_ids = array();

      // By default all available purgers are loaded when the 'plugins' setting
      // in 'purge.purger.yml' is set to 'automatic_detection', else those
      // plugins therein specified are loaded.
      if ($plugins == 'automatic_detection') {
        foreach (array_keys($this->discovery->getDefinitions()) as $plugin_id) {
          if ($plugin_id !== 'dummy') {
            $plugin_ids[] = $plugin_id;
          }
        }
      }
      else {
        foreach (explode(',', $plugins) as $plugin_id) {
          $plugin_id = trim($plugin_id);
          if ($plugin_id === 'dummy') {
            continue;
          }
          elseif (!is_null($this->discovery->getDefinition($plugin_id))) {
            $plugin_ids[] = $plugin_id;
          }
        }
      }

      // When no purgers exist the 'dummy' purger will be loaded instead.
      if (empty($plugin_ids)) {
        $plugin_ids[] = 'dummy';
      }
    }
    return $plugin_ids;
  }

  /**
   * Load the configured purgers and gather them in $this->purgers.
   */
  protected function initializePurgers() {
    if (!is_null($this->purgers)) {
      return;
    }

    // Iterate each purger plugin we should load and instantiate them.
    foreach ($this->getPluginsLoaded() as $plugin_id) {
      $plugin_definition = $this->discovery->getDefinition($plugin_id);
      $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

      // Prepare the requested service arguments.
      $arguments = array();
      foreach ($plugin_definition['service_dependencies'] as $service) {
        $arguments[] = $this->serviceContainer->get($service);
      }

      // Use the Reflection API to instantiate our plugin.
      $reflector = new \ReflectionClass($plugin_class);
      $this->purgers[$plugin_id] = $reflector->newInstanceArgs($arguments);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function purge(PurgeableInterface $purgeable) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception('Not yet implemented');
  }
}
