<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\PurgersService.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\Tracker;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Provides the service that distributes access to one or more purgers.
 */
class PurgersService extends ServiceBase implements PurgersServiceInterface {

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
   * @var null|string[]
   */
  protected $labels = NULL;

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[]
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
    InvalidationInterface::FRESH,
    InvalidationInterface::PROCESSING,
    InvalidationInterface::FAILED,
    InvalidationInterface::NOT_SUPPORTED,
  ];

  /**
   * Valid Invalidation object states that return from purger plugins.
   *
   * @var int[]
   */
  protected $states_outbound = [
    InvalidationInterface::SUCCEEDED,
    InvalidationInterface::PROCESSING,
    InvalidationInterface::FAILED
  ];

  /**
   * The list of supported invalidation types across all purgers.
   *
   * @var null|string[]
   */
  protected $types = NULL;

  /**
   * The list of supported invalidation types per purger plugin.
   *
   * @var null|array[]
   */
  protected $types_by_purger = NULL;

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
  public function getLabels() {
    if (is_null($this->labels)) {
      $this->labels = [];
      foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
        $this->labels[$id] = $this->purgers[$id]->getLabel();
      }
    }
    return $this->labels;
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   Associative array with enabled purgers: id => plugin_id.
   */
  public function getPluginsEnabled() {
    if (is_null($this->plugins_enabled)) {
      $setting = $this->configFactory->get('purge.plugins')->get('purgers');
      $plugin_ids = array_keys($this->getPlugins());
      $this->plugins_enabled = [];
      foreach ($setting as $inst) {
        if (!in_array($inst['plugin_id'], $plugin_ids)) {
          // When a third-party provided purger was configured and its module
          // got uninstalled, the configuration renders invalid. Instead of
          // rewriting config or breaking hard, we ignore this silently.
          continue;
        }
        else {
          $this->plugins_enabled[$inst['instance_id']] = $inst['plugin_id'];
        }
      }
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   *
   * This method takes into account that purger plugins that are not
   * multi-instantiable, can only be loaded once and are no longer available if
   * they are already available. Plugins that are multi-instantiable, will
   * always be listed.
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
    if (is_null($this->types)) {
      $this->types = [];
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
    if (is_null($this->types_by_purger)) {
      $this->types_by_purger = [];
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
    $setting = [];
    foreach ($plugin_ids as $instance_id => $plugin_id) {
      if (!is_string($instance_id) || empty($instance_id)) {
        throw new \LogicException('Invalid instance ID (key).');
      }
      if (!isset($this->pluginManager->getDefinitions()[$plugin_id])) {
        throw new \LogicException('Invalid plugin_id.');
      }
      $setting[] = [
        'instance_id' => $instance_id,
        'plugin_id' => $plugin_id
      ];
    }
    $this->configFactory
      ->getEditable('purge.plugins')
      ->set('purgers', $setting)
      ->save();
    $this->reload();
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    // Without this, the tests will throw "failed to instantiate user-supplied
    // statement class: CREATE TABLE {cache_config}".
    $this->configFactory = \Drupal::configFactory();
    $this->purgers = NULL;
    $this->labels = NULL;
    $this->types = NULL;
    $this->types_by_purger = NULL;
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
    $this->purgers = [];
    foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
      $this->purgers[$id] = $this->pluginManager->createInstance($plugin_id, ['id' => $id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(InvalidationInterface $invalidation) {
    $invalidation_type = $invalidation->getPluginId();
    $types_by_purger = $this->getTypesByPurger();
    $types = $this->getTypes();
    $results = [];

    // Stop any attempt when there is no available capacity.
    if (!$this->capacityTracker()->getLimit()) {
      throw new CapacityException('No capacity available or resource limits exceeded.');
    }

    // Test $invalidation's inbound object state.
    $initialstate = $invalidation->getState();
    if (!in_array($initialstate, $this->states_inbound)) {
      throw new BadPluginBehaviorException("Only FRESH, PROCESSING, FAILED and NOT_SUPPORTED are valid inbound states.");
    }

    // Iterate the purger instances and only execute for supported types.
    foreach ($this->purgers as $id => $purger) {
      if (in_array($invalidation_type, $types_by_purger[$id])) {

        // Reset the initial state object state, execute the invalidation.
        $invalidation->setState($initialstate);
        $purger->invalidate($invalidation);
        if (!in_array($invalidation->getState(), $this->states_outbound)) {
          throw new BadPluginBehaviorException("Only SUCCEEDED, PROCESSING and FAILED are valid return states.");
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

    // Throw exceptions for various unsupported conditions.
    if (empty($invalidations)) {
      throw new BadBehaviorException('Given $invalidations array is empty.');
    }
    if (!$this->capacityTracker()->getLimit()) {
      throw new CapacityException('No capacity available or resource limits exceeded.');
    }
    if (count($invalidations) > $this->capacityTracker()->getLimit()) {
      throw new CapacityException('Given $invalidations has more items than the capacity limit allows.');
    }

    // Test each invalidation object to see if its in a valid inbound state.
    foreach ($invalidations as $i => $invalidation) {
      $invalidation_types[$i] = $invalidation->getPluginId();
      $initialstates[$i] = $invalidation->getState();
      if (!in_array($initialstates[$i], $this->states_inbound)) {
        throw new BadPluginBehaviorException("Only FRESH, PROCESSING, FAILED and NOT_SUPPORTED are valid inbound states.");
      }
    }

    // Prepopulate empty result sets and list supported types. Empty result sets
    // will lead to NOT_SUPPORTED in ::resolveInvalidationState().
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
          throw new BadPluginBehaviorException("Only SUCCEEDED, PROCESSING and FAILED are valid return states.");
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
  public function resolveInvalidationState(InvalidationInterface $invalidation, array $states) {
    // No results indicate no purgers touched it, so it is not supported.
    if (empty($states)) {
      $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
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
      if (in_array(InvalidationInterface::NOT_SUPPORTED, $states)) {
        $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
      }
      elseif (in_array(InvalidationInterface::FAILED, $states)) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
      elseif (in_array(InvalidationInterface::PROCESSING, $states)) {
        $invalidation->setState(InvalidationInterface::PROCESSING);
      }
      elseif (in_array(InvalidationInterface::FRESH, $states)) {
        $invalidation->setState(InvalidationInterface::FRESH);
      }

      // Only really succeed when no other scenario exists.
      else {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
  }

}
