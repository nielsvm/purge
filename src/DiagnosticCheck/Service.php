<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\Service.
 */

namespace Drupal\purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\DiagnosticCheck\ServiceInterface;
use Drupal\purge\DiagnosticCheck\PluginInterface as Check;

/**
 * Provides a service that interacts with diagnostic checks.
 */
class Service extends ServiceBase implements ServiceInterface {
  use ContainerAwareTrait;

  /**
   * Current iterator position.
   *
   * @var int
   * @ingroup iterator
   */
  protected $position = 0;

  /**
   * Keeps all instantiated checks.
   *
   * @var \Drupal\purge\DiagnosticCheck\PluginInterface[]
   */
  protected $checks = [];

  /**
   * The plugin manager for checks.
   *
   * @var \Drupal\purge\DiagnosticCheck\PluginManager
   */
  protected $pluginManager;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * Do not access this property directly, use ::getPurgers.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  private $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * Do not access this property directly, use ::getQueue.
   *
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  private $purgeQueue;

  /**
   * Construct \Drupal\purge\DiagnosticCheck\Service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * Load all the plugins that should run and gather them in $this->checks.
   *
   * @return void
   */
  protected function initializeChecks() {
    if (!empty($this->checks)) {
      return;
    }

    // Iterate each check that we should load and instantiate.
    foreach ($this->getPluginsEnabled() as $plugin_id) {
      $this->checks[] = $this->pluginManager->createInstance($plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    $this->initializeChecks();
    return count($this->checks);
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function current() {
    $this->initializeChecks();
    if ($this->valid()) {
      return $this->checks[$this->position];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $this->initializeChecks();
    $requirements = [];
    foreach ($this as $check) {
      $requirements[$check->getPluginId()] = $check->getHookRequirementsArray();
    }
    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (!is_null($this->plugins_enabled)) {
      return $this->plugins_enabled;
    }

    // We blindly load all diagnostic check plugins that we discovered, but not
    // when plugins put dependencies on either a queue or purger plugin. When
    // plugins do depend, we load 'purge.purgers' and/or 'purge.queue' and
    // carefully check if we should load them or not.
    $load = function($needles, $haystack) {
      if (empty($needles)) return TRUE;
      foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
          return TRUE;
        }
      }
      return FALSE;
    };
    foreach ($this->getPlugins() as $plugin) {
      if (!empty($plugin['dependent_queue_plugins'])) {
        if (!$load($plugin['dependent_queue_plugins'], $this->getQueue()->getPluginsEnabled())) {
          continue;
        }
      }
      if (!empty($plugin['dependent_purger_plugins'])) {
        if (!$load($plugin['dependent_purger_plugins'], $this->getPurgers()->getPluginsEnabled())) {
          continue;
        }
      }
      $this->plugins_enabled[] = $plugin['id'];
    }
    return $this->plugins_enabled;
  }

  /**
   * Retrieve the 'purge.purgers' service - lazy loaded.
   *
   * @return \Drupal\purge\Purger\ServiceInterface
   */
  protected function getPurgers() {
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    return $this->purgePurgers;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirementsArray() {
    $this->initializeChecks();
    $requirements = [];
    foreach ($this as $check) {
      $requirements[$check->getPluginId()] = $check->getRequirementsArray();
    }
    return $requirements;
  }

  /**
   * Retrieve the 'purge.queue' service - lazy loaded.
   *
   * @return \Drupal\purge\Queue\ServiceInterface
   */
  protected function getQueue() {
    if (is_null($this->purgeQueue)) {
      $this->purgeQueue = $this->container->get('purge.queue');
    }
    return $this->purgeQueue;
  }

  /**
   * {@inheritdoc}
   */
  public function isSystemOnFire() {
    $this->initializeChecks();
    foreach ($this as $check) {
      if ($check->getSeverity() === Check::SEVERITY_ERROR) {
        return $check;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSystemShowingSmoke() {
    $this->initializeChecks();
    foreach ($this as $check) {
      if ($check->getSeverity() === Check::SEVERITY_WARNING) {
        return $check;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function key() {
    $this->initializeChecks();
    return $this->position;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function next() {
    $this->initializeChecks();
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->purgePurgers = NULL;
    $this->purgeQueue = NULL;
    $this->position = 0;
    $this->checks = [];
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function rewind() {
    $this->initializeChecks();
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   * @ingroup iterator
   */
  public function valid() {
    $this->initializeChecks();
    return isset($this->checks[$this->position]);
  }

}
