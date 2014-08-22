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
   * Current iterator position.
   *
   * @ingroup iterator
   */
  private $position = 0;

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

    // Set $this->position to 0, as this object is iterable.
    $this->position = 0;

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
      $this->tests[] = $reflector->newInstanceArgs($arguments);
    }
  }

  /**
   * Generates a hook_requirements() compatible array.
   *
   * @warning
   *   Although it shares the same name, this method doesn't return a individual
   *   item array as RuntimeTestInterface::getHookRequirementsArray() does. It
   *   returns a full array (as hook_requirements() expects) for all tests.
   *
   * @return array
   *   An associative array where the keys are arbitrary but unique (test id)
   *   and the values themselves are associative arrays with these elements:
   *   - title: The name of this test.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the test.
   *   - severity: The test's result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $requirements = array();
    foreach ($this as $test) {
      $requirements[$test->getPluginId()] = $test->getHookRequirementsArray();
    }
    return $requirements;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  function rewind() {
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  function current() {
    return $this->tests[$this->position];
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  function key() {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  function next() {
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  function valid() {
    return isset($this->tests[$this->position]);
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    return count($this->tests);
  }
}