<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\Service.
 */

namespace Drupal\purge\Purger;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\Tracker;
use Drupal\purge\Purger\ServiceInterface;
use Drupal\purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Provides the service that distributes access to one or more purgers.
 */
class Service extends ServiceBase implements ServiceInterface {

  /**
   * The plugin ID of the fallback backend.
   */
  const FALLBACK_PLUGIN = 'null';

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerInterface
   */
  protected $capacityTracker;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Holds all generated user-readable purger labels per instance ID.
   *
   * @var string[]
   */
  protected $labels;

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Purger\PluginInterface[]
   */
  protected $purgers;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Valid Invalidation object states that can be fed to the purger service.
   *
   * @var int[]
   */
  protected $states_inbound = [
    Invalidation::STATE_NEW,
    Invalidation::STATE_PURGING,
    Invalidation::STATE_FAILED,
    Invalidation::STATE_UNSUPPORTED,
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
   * The list of supported invalidation types across all purgers.
   *
   * @var string[]
   */
  protected $types = [];

  /**
   * The list of supported invalidation types per purger plugin.
   *
   * @var array[]
   */
  protected $types_by_purger = [];

  /**
   * Instantiate the purgers service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
    $this->state = $state;

    // Instantiate all the purgers and let them configure themselves.
    $this->initializePurgers();
  }

  /**
   * {@inheritdoc}
   */
  public function capacityTracker() {
    if (is_null($this->capacityTracker)) {
      $this->capacityTracker = new Tracker($this->purgers, $this->state);
    }
    return $this->capacityTracker;
  }

  /**
   * {@inheritdoc}
   */
  public function createId() {
    return substr(sha1(microtime()), 0, 10);
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
  public function getLabels($include_fallback = TRUE) {
    if (empty($this->labels)) {
      foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
        $this->labels[$id] = $this->purgers[$id]->getLabel();
      }
    }
    if ($include_fallback) {
      return $this->labels;
    }
    else {
      $labels = [];
      foreach ($this->labels as $id => $plugin_id) {
        if ($id !== SELF::FALLBACK_PLUGIN) {
          $labels[$id] = $plugin_id;
        }
      }
      return $labels;
    }
    return $this->labels;
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
  public function getPluginsEnabled($include_fallback = TRUE) {
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
    if ($include_fallback) {
      return $this->plugins_enabled;
    }
    else {
      $plugins = [];
      foreach ($this->plugins_enabled as $id => $plugin_id) {
        if ($plugin_id !== SELF::FALLBACK_PLUGIN) {
          $plugins[$id] = $plugin_id;
        }
      }
      return $plugins;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsAvailable() {
    $enabled = $this->getPluginsEnabled();
    $available = [];
    foreach ($this->getPlugins() as $plugin_id => $definition) {
      if ($definition['multi_instance']) {
        $available[] = $plugin_id;
      }
      else {
        if (!in_array($plugin_id, $enabled)) {
          $available[] = $plugin_id;
        }
      }
    }
    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    if (empty($this->types)) {
      foreach ($this->purgers as $purger) {
        foreach ($purger->getTypes() as $type) {
          if (!in_array($type, $this->types)) {
            $this->types[] = $type;
          }
        }
      }
    }
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypesByPurger() {
    if (empty($this->types_by_purger)) {
      foreach ($this->getPluginsEnabled(FALSE) as $id => $plugin_id) {
        $this->types_by_purger[$id] = $this->purgers[$id]->getTypes();
      }
    }
    return $this->types_by_purger;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginsEnabled(array $plugin_ids) {
    unset($plugin_ids[SELF::FALLBACK_PLUGIN]);
    foreach ($plugin_ids as $id => $plugin_id) {
      if (!is_string($id) || empty($id)) {
        throw new \LogicException('Invalid instance ID (key).');
      }
      if (!isset($this->pluginManager->getDefinitions()[$plugin_id])) {
        throw new \LogicException('Invalid plugin_id.');
      }
    }
    $this->configFactory->getEditable('purge.plugins')->set('purgers', $plugin_ids)->save();
    $this->reload();
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->configFactory = \Drupal::configFactory();
    $this->purgers = NULL;
    $this->types = [];
    $this->types_by_purger = [];
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
      $this->purgers[$id] = $this->pluginManager->createInstance($plugin_id, ['id' => $id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $invalidation_type = $invalidation->getPluginId();
    $types_by_purger = $this->getTypesByPurger();
    $types = $this->getTypes();
    $results = [];

    // Test $invalidation's inbound object state.
    $initialstate = $invalidation->getState();
    if (!in_array($initialstate, $this->states_inbound)) {
      throw new BadPluginBehaviorException("Only STATE_NEW, STATE_PURGING, STATE_FAILED and STATE_UNSUPPORTED are valid inbound states.");
    }

    // Iterate the purger instances and only execute for supported types.
    foreach ($this->purgers as $id => $purger) {
      if (in_array($invalidation_type, $types_by_purger[$id])) {

        // Reset the initial state object state, execute the invalidation.
        $invalidation->setState($initialstate);
        $purger->invalidate($invalidation);
        if (!in_array($invalidation->getState(), $this->states_outbound)) {
          throw new BadPluginBehaviorException("Only STATE_PURGED, STATE_PURGING and STATE_FAILED are valid return states.");
        }
        $results[] = $invalidation->getState();
      }
    }

    // Resolve the multiple states into the final state.
    $this->resolveInvalidationState($invalidation, $results);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    $types_by_purger = $this->getTypesByPurger();
    $types = $this->getTypes();
    $invalidation_types = [];
    $initialstates = [];
    $results = [];

    // Fail early if no objects we're given.
    if (empty($invalidations)) {
      return;
    }

    // Test each invalidation object to see if its in a valid inbound state.
    foreach ($invalidations as $i => $invalidation) {
      $invalidation_types[$i] = $invalidation->getPluginId();
      $initialstates[$i] = $invalidation->getState();
      if (!in_array($initialstates[$i], $this->states_inbound)) {
        throw new BadPluginBehaviorException("Only STATE_NEW, STATE_PURGING, STATE_FAILED and STATE_UNSUPPORTED are valid inbound states.");
      }
    }

    // Prepopulate empty result sets and list supported types. Empty result sets
    // will lead to STATE_UNSUPPORTED in ::resolveInvalidationState().
    foreach ($invalidations as $i => $invalidation) {
      $results[$i] = [];
    }

    // Iterate the purgers, and match supported types to loaded purgers.
    foreach ($this->purgers as $id => $purger) {

      // Build a subset of invalidation objects, supported by this purger.
      $supported_invalidations = [];
      foreach ($invalidations as $i => $invalidation) {
        if (in_array($invalidation_types[$i], $types_by_purger[$id])) {
          $invalidation->setState($initialstates[$i]);
          $supported_invalidations[$i] = $invalidation;
        }
      }

      // Ask the purger plugin to execute the purges for the given subset.
      $purger->invalidateMultiple($supported_invalidations);

      // Gather results and pick up invalid outbound states.
      foreach ($supported_invalidations as $i => $invalidation) {
        $state = $invalidation->getState();
        $results[$i][] = $state;
        if (!in_array($state, $this->states_outbound)) {
          throw new BadPluginBehaviorException("Only STATE_PURGED, STATE_PURGING and STATE_FAILED are valid return states.");
        }
      }
    }

    // Resolve the multiple states into the final state for each object.
    foreach ($invalidations as $i => $invalidation) {
      $this->resolveInvalidationState($invalidation, $results[$i]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    // @todo
    $successes = 0;
    foreach ($this->purgers as $purger) {
      $purger_successes = $purger->getNumberPurged();
      if (!is_int($purger_successes)) {
        throw new BadPluginBehaviorException(
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
    // @todo
    $failures = 0;
    foreach ($this->purgers as $purger) {
      $purger_failures = $purger->getNumberFailed();
      if (!is_int($purger_failures)) {
        throw new BadPluginBehaviorException(
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
    // @todo
    $purging = 0;
    foreach ($this->purgers as $purger) {
      $purger_purging = $purger->getNumberPurging();
      if (!is_int($purger_purging)) {
        throw new BadPluginBehaviorException(
          "The purger '$plugin_id' did not return an integer on getNumberPurging().");
      }
      $purging += $purger_purging;
    }
    return $purging;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveInvalidationState(Invalidation $invalidation, array $states) {
    // No results indicate no purgers touched it, so it is not supported.
    if (empty($states)) {
      $invalidation->setState(Invalidation::STATE_UNSUPPORTED);
    }

    // When there is just one result, we take it as final state.
    elseif (count($states) === 1) {
      $single_resulting_state = current($states);
      if ($invalidation->getState() != $single_resulting_state) {
        $invalidation->setState($single_resulting_state);
      }
    }

    // With multiple results, determine what the final result will be.
    else {
      if (in_array(Invalidation::STATE_UNSUPPORTED, $states)) {
        $invalidation->setState(Invalidation::STATE_UNSUPPORTED);
      }
      elseif (in_array(Invalidation::STATE_FAILED, $states)) {
        $invalidation->setState(Invalidation::STATE_FAILED);
      }
      elseif (in_array(Invalidation::STATE_PURGING, $states)) {
        $invalidation->setState(Invalidation::STATE_PURGING);
      }
      elseif (in_array(Invalidation::STATE_NEW, $states)) {
        $invalidation->setState(Invalidation::STATE_NEW);
      }

      // Only really succeed when no other scenario exists.
      else {
        $invalidation->setState(Invalidation::STATE_PURGED);
      }
    }
  }

}
