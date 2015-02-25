<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\Service.
 */

namespace Drupal\purge\DiagnosticCheck;

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
   * Keeps all instantiated checks.
   *
   * @var array
   */
  protected $checks;

  /**
   * {@inheritdoc}
   */
  function __construct(PluginManagerInterface $pluginManager, PurgerServiceInterface $purge_purgers, QueueServiceInterface $purge_queue) {
    $this->pluginManager = $pluginManager;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueue = $purge_queue;

    // Set $this->position to 0, as this object is iterable.
    $this->position = 0;

    // Instantiate all checks, but we are not calling run() on them yet.
    $this->initializeChecks();
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

      // Determine for each check if it should be loaded.
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
    $this->checks = NULL;
    $this->initializeChecks();
  }

  /**
   * Load all the plugins that should run and gather them in $this->checks.
   */
  protected function initializeChecks() {
    if (!is_null($this->checks)) {
      return;
    }

    // Iterate each check that we should load and instantiate.
    foreach ($this->getPluginsEnabled() as $plugin_id) {
      $this->checks[] = $this->pluginManager->createInstance($plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $requirements = [];
    foreach ($this as $check) {
      $requirements[$check->getPluginId()] = $check->getHookRequirementsArray();
    }
    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function isSystemOnFire() {
    foreach ($this as $check) {
      if ($check->getSeverity() === PluginInterface::SEVERITY_ERROR) {
        return $check;
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
    return $this->checks[$this->position];
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
    return isset($this->checks[$this->position]);
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    return count($this->checks);
  }
}
