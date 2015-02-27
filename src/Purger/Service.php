<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\Service.
 */

namespace Drupal\purge\Purger;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purger\ServiceInterface;
use Drupal\purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Provides the service that distributes access to one or more purgers.
 */
class Service extends ServiceBase implements ServiceInterface {

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
   * The plugin ID of the fallback backend.
   */
  const FALLBACK_PLUGIN = 'null';

  /**
   * Valid Invalidation object states that can be fed to the purger service.
   *
   * @var int[]
   */
  protected $states_inbound = [
    Invalidation::STATE_NEW,
    Invalidation::STATE_PURGING,
    Invalidation::STATE_FAILED
  ];

  /**
   * Valid Invalidation object states that return from purger plugins.
   *
   * @var int[]
   */
  protected $states_outbound = [
    Invalidation::STATE_PURGED,
    Invalidation::STATE_PURGING,
    Invalidation::STATE_FAILED
  ];

  /**
   * Instantiate the purgers service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;

    // Instantiate all the purgers and let them configure themselves.
    $this->initializePurgers();
  }

  /**
   * {@inheritdoc}
   */
  public function createId() {
    return strtoupper(substr(sha1(microtime()), 0, 10));
  }

  /**
   * {@inheritdoc}
   */
  public function deletePluginsEnabled(array $ids) {
    if (empty($ids)) {
      throw new \LogicException('Empty $ids in ::deletePluginsEnabled().');
    }
    $enabled = $this->getPluginsEnabled();
    foreach ($ids as $id) {
      if (!isset($enabled[$id])) {
        throw new \LogicException('Invalid id in ::deletePluginsEnabled().');
      }
      unset($enabled[$id]);
    }
    $this->purgers[$id]->delete();
    $this->setPluginsEnabled($enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    if (empty($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
      unset($this->plugins[SELF::FALLBACK_PLUGIN]);
    }
    return $this->plugins;
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   Associative array with enabled purgers: id => plugin_id.
   */
  public function getPluginsEnabled() {
    if (empty($this->plugins_enabled)) {
      $enabled = $this->configFactory->get('purge.plugins')->get('purgers');
      $plugin_ids = array_keys($this->getPlugins());

      foreach ($enabled as $id => $plugin_id) {
        if ($plugin_id === SELF::FALLBACK_PLUGIN) {
          continue;
        }
        elseif (!in_array($plugin_id, $plugin_ids)) {
          // When a third-party provided purger was configured and its module
          // got uninstalled, the configuration renders invalid. Instead of
          // rewriting config or breaking hard, we ignore silently. The
          // diagnostic checks take care of getting this visualized to the user.
          continue;
        }
        else {
          $this->plugins_enabled[$id] = $plugin_id;
        }
      }

      // The public API always has to be reliable and always requires a purger
      // backend. Therefore we load the 'null' backend in unfunctional setups.
      if (empty($this->plugins_enabled)) {
        $this->plugins_enabled[SELF::FALLBACK_PLUGIN] = SELF::FALLBACK_PLUGIN;
      }
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginsEnabled(array $plugin_ids) {
    static::setPluginsStatic($plugin_ids, $this->pluginManager, $this->configFactory);
    $this->reload();
  }

  /**
   * {@inheritdoc}
   */
  public static function setPluginsStatic(array $plugin_ids, PluginManagerInterface $plugin_manager = NULL, ConfigFactoryInterface $config_factory = NULL) {
    if (is_null($plugin_manager)) {
      $plugin_manager = \Drupal::service('plugin.manager.purge.purgers');
    }
    if (is_null($config_factory)) {
      $config_factory = \Drupal::configFactory();
    }
    foreach ($plugin_ids as $plugin_id) {
      if (!isset($plugin_manager->getDefinitions()[$plugin_id])) {
        throw new \LogicException('Invalid plugin_id in ::setPluginsStatic().');
      }
    }
    $config_factory->getEditable('purge.plugins')->set('purgers', $plugin_ids)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->purgers = NULL;
    $this->configFactory = \Drupal::configFactory();
    $this->initializePurgers();
  }

  /**
   * Load the configured purgers and gather them in $this->purgers.
   */
  protected function initializePurgers() {
    if (!is_null($this->purgers)) {
      return;
    }

    // Iterate each purger plugin we should load and instantiate them.
    foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
      $this->purgers[] = $this->pluginManager->createInstance($plugin_id, ['id' => $id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $results = [];

    // Test $invalidation's inbound object state.
    $initialstate = $invalidation->getState();
    if (!in_array($initialstate, $this->states_inbound)) {
      throw new InvalidStateException('Inbound state of $invalidation does not make any sense.');
    }

    // Request each purger to execute the invalidation.
    foreach ($this->purgers as $plugin_id => $purger) {
      $invalidation->setState($initialstate);
      $purger->purge($invalidation);

      // Test the returning state of the object we just gave to the purger.
      if (!in_array($invalidation->getState(), $this->states_outbound)) {
        throw new InvalidStateException("$plugin_id left \$invalidation in a state that does not make any sense.");
      }

      $results[] = $invalidation->getState();
    }

    // When multiple purgers processed $invalidation, we have more then one
    // conclusion which has to become just one conclusion. This is because the
    // Purge queue API and other public API consumers outside of this service,
    // have no concept of 'multiple purgers'. This has the small cost that one
    // failure will lead to all purgers redoing the invalidation next time, or
    // that a multistep invalidation can be fed to the wrong purger next time (@todo).
    if (!count($results) == 1) {
      if (in_array(Invalidation::STATE_FAILED, $results)) {
        $invalidation->setState(Invalidation::STATE_FAILED);
      }
      elseif (in_array(Invalidation::STATE_PURGING, $results)) {
        $invalidation->setState(Invalidation::STATE_PURGING);
      }
      else {
        $invalidation->setState(Invalidation::STATE_PURGED);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    $initialstates = [];
    $results = [];

    // Test each invalidation object to see if its in a valid inbound state.
    foreach ($invalidations as $i => $invalidation) {
      $initialstates[$i] = $invalidation->getState();
      if (!in_array($initialstates[$i], $this->states_inbound)) {
        throw new InvalidStateException("Inbound state of \$invalidations[$i] does not make any sense.");
      }
    }

    // Request each purger to execute the list of invalidations.
    foreach ($this->purgers as $plugin_id => $purger) {
      foreach ($invalidations as $i => $invalidation) {
        $invalidation->setState($initialstates[$i]);
      }
      $purger->invalidateMultiple($invalidations);

      // Test all touched objects to see if any of them as an invalid state.
      foreach ($invalidations as $i => $invalidation) {
        $results[$i] = $invalidation->getState();
        if (!in_array($results[$i], $this->states_outbound)) {
          throw new InvalidStateException("$plugin_id left \$invalidations[$id] in a state that does not make any sense.");
        }
      }
    }

    // When multiple purgers processed $invalidations, we have more then one
    // conclusion per invalidation objects, which has to become just one
    // each time. This is because the Purge queue API and other public API
    // consumers outside of this service, have no concept of 'multiple
    // purgers'. This has the small cost that one failure will lead to all
    // purgers redoing the invalidation next time, or that a multistep purge can
    // be fed to the wrong purger next time (@todo).
    if (count($this->purgers) > 1) {
      foreach ($invalidations as $i => $invalidation) {
        if (in_array(Invalidation::STATE_FAILED, $results[$i])) {
          $invalidation->setState(Invalidation::STATE_FAILED);
        }
        elseif (in_array(Invalidation::STATE_PURGING, $results[$i])) {
          $invalidation->setState(Invalidation::STATE_PURGING);
        }
        else {
          $invalidation->setState(Invalidation::STATE_PURGED);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    static $limit;

    // As the limit gets cached during this request, calculate it only once.
    if (is_null($limit)) {
      $purgers = count($this->purgers);
      $limits = [];

      // Ask all purgers to estimate how many invalidations they can process.
      foreach ($this->purgers as $purger) {
        if (!is_int($limits[] = $purger->getCapacityLimit())) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' did not return an integer on getCapacityLimit().");
        }
      }

      // Directly use its limit for just one loaded purger, lower it otherwise.
      if ($purgers === 1) {
        $limit = current($limits);
      }
      else {
        $limit = (int) floor(array_sum($limits) / $purgers / $purgers);
        if ($limit < 1) {
          $limit = 1;
        }
      }
    }

    return $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    static $seconds;

    // We are caching the hint value so that it gets calculated just once.
    if (is_null($seconds)) {
      $seconds = 0;

      foreach ($this->purgers as $purger) {
        $purger_seconds = $purger->getClaimTimeHint();
        if (!is_int($purger_seconds)) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' did not return an integer on getClaimTimeHint().");
        }
        elseif ($purger_seconds === 0) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' cannot report a 0 seconds claim time on getClaimTimeHint().");
        }
        $seconds += $purger_seconds;
      }
    }

    return $seconds;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    $successes = 0;
    foreach ($this->purgers as $purger) {
      $purger_successes = $purger->getNumberPurged();
      if (!is_int($purger_successes)) {
        throw new InvalidPurgerBehaviorException(
          "The purger '$plugin_id' did not return an integer on getNumberPurged().");
      }
      $successes += $purger_successes;
    }
    return $successes;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    $failures = 0;
    foreach ($this->purgers as $purger) {
      $purger_failures = $purger->getNumberFailed();
      if (!is_int($purger_failures)) {
        throw new InvalidPurgerBehaviorException(
          "The purger '$plugin_id' did not return an integer on getNumberFailed().");
      }
      $failures += $purger_failures;
    }
    return $failures;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    $purging = 0;
    foreach ($this->purgers as $purger) {
      $purger_purging = $purger->getNumberPurging();
      if (!is_int($purger_purging)) {
        throw new InvalidPurgerBehaviorException(
          "The purger '$plugin_id' did not return an integer on getNumberPurging().");
      }
      $purging += $purger_purging;
    }
    return $purging;
  }

}
