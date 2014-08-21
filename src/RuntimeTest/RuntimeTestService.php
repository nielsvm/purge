<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\RuntimeTestService.
 */

namespace Drupal\purge\RuntimeTest;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purger\PurgerServiceInterface;
use Drupal\purge\Queue\QueueServiceInterface;
use Drupal\purge\RuntimeTest\RuntimeTestServiceInterface;

/**
 * Provides a service that interacts with runtime tests.
 */
class RuntimeTestService extends ServiceBase implements RuntimeTestServiceInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $serviceContainer;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\PurgerServiceInterface
   */
  protected $purgePurger;

  /**
   * The queue in which to store, claim and release purgeable objects from.
   *
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * Keeps all instantiated tests.
   *
   * @var array
   */
  protected $tests;

  /**
   * {@inheritdoc}
   */
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, PurgerServiceInterface $purge_purger, QueueServiceInterface $purge_queue) {
    $this->pluginManager = $pluginManager;
    $this->serviceContainer = $service_container;
    $this->purgePurger = $purge_purger;
    $this->purgeQueue = $purge_queue;

    // Instantiate all tests, but we are not calling run() on them yet.
    $this->initializeTests();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (!$simple) {
      return $this->pluginManager->getDefinitions();
    }
    $plugins = array();
    foreach ($this->pluginManager->getDefinitions() as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['title'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsLoaded() {
    static $plugin_ids;
    if (is_null($plugin_ids)) {
      $loaded_queues = $this->purgeQueue->getPluginsLoaded();
      $loaded_purgers = $this->purgePurger->getPluginsLoaded();
      $plugin_ids = array();

      // Define a lambda that tests whether a plugin should be loaded.
      $load = function($needles, $haystack) {
        if (empty($needles)) return TRUE;
        foreach ($needles as $needle) {
          if (in_array($needle, $haystack)) {
            return TRUE;
          }
        }
        return FALSE;
      };

      // Determine for each test if it should be loaded.
      foreach ($this->pluginManager->getDefinitions() as $plugin) {
        if (!$load($plugin['dependent_queue_plugins'], $loaded_queues)) {
          continue;
        }
        if (!$load($plugin['dependent_purger_plugins'], $loaded_purgers)) {
          continue;
        }
        $plugin_ids[] = $plugin['id'];
      }
    }
    return $plugin_ids;
  }

  /**
   * Load all the tests that should run and gather them in $this->tests.
   */
  protected function initializeTests() {
    if (!is_null($this->tests)) {
      return;
    }

    // Iterate each test that we should load and instantiate.
    foreach ($this->getPluginsLoaded() as $plugin_id) {
      $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
      $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

      // Prepare the arguments that we pass onto the test constructor.
      $arguments = array();
      $arguments[] = array();
      $arguments[] = $plugin_id;
      $arguments[] = $plugin_definition;
      foreach ($plugin_definition['service_dependencies'] as $service) {
        $arguments[] = $this->serviceContainer->get($service);
      }

      // Use the Reflection API to instantiate our test.
      $reflector = new \ReflectionClass($plugin_class);
      $this->tests[$plugin_id] = $reflector->newInstanceArgs($arguments);
    }
  }

}
