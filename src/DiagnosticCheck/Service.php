<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\Service.
 */

namespace Drupal\purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\DiagnosticCheck\ServiceInterface;
use Drupal\purge\DiagnosticCheck\PluginInterface;

/**
 * Provides a service that interacts with diagnostic checks.
 */
class Service extends ServiceBase implements ServiceInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $serviceContainer;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * @var \Drupal\purge\Queue\ServiceInterface
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
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, PurgerServiceInterface $purge_purgers, QueueServiceInterface $purge_queue) {
    $this->pluginManager = $pluginManager;
    $this->serviceContainer = $service_container;
    $this->purgePurgers = $purge_purgers;
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
    if (empty($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
    }
    if (!$simple) {
      return $this->plugins;
    }
    $plugins = [];
    foreach ($this->plugins as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['title'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (empty($this->plugins_enabled)) {
      $enabled_queues = $this->purgeQueue->getPluginsEnabled();
      $enabled_purgers = $this->purgePurgers->getPluginsEnabled();

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
      foreach ($this->getPlugins() as $plugin) {
        if (!$load($plugin['dependent_queue_plugins'], $enabled_queues)) {
          continue;
        }
        if (!$load($plugin['dependent_purger_plugins'], $enabled_purgers)) {
          continue;
        }
        $this->plugins_enabled[] = $plugin['id'];
      }
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->position = 0;
    $this->tests = NULL;
    $this->initializeTests();
  }

  /**
   * Load all the tests that should run and gather them in $this->tests.
   */
  protected function initializeTests() {
    if (!is_null($this->tests)) {
      return;
    }

    // Iterate each test that we should load and instantiate.
    foreach ($this->getPluginsEnabled() as $plugin_id) {
      $this->tests[] = $this->pluginManager->createInstance($plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $requirements = [];
    foreach ($this as $test) {
      $requirements[$test->getPluginId()] = $test->getHookRequirementsArray();
    }
    return $requirements;
  }

  /**
   * Checks whether one of the diagnostic tests reports full failure.
   *
   * This method provides a simple - boolean evaluable - way to determine if
   * a \Drupal\purge\DiagnosticCheck\PluginInterface::SEVERITY_ERROR severity
   * was reported by one of the tests. If SEVERITY_ERROR was reported, purging
   * cannot continue and should happen once all problems are resolved.
   *
   * @return FALSE or a \Drupal\purge\DiagnosticCheck\PluginInterface test object.
   *   If everything is fine, this returns FALSE. But, if a blocking problem
   *   exists, the first failing test object is returned holding a UI applicable
   *   recommendation message.
   */
  public function isSystemOnFire() {
    foreach ($this as $test) {
      if ($test->getSeverity() === PluginInterface::SEVERITY_ERROR) {
        return $test;
      }
    }
    return FALSE;
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
