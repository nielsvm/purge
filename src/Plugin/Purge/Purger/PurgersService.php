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
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Capacity\Tracker;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
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
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

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
   * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   *   The diagnostics service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory, DiagnosticsServiceInterface $purge_diagnostics, StateInterface $state) {
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function capacityTracker() {
    if (is_null($this->capacityTracker)) {
      $this->initializePurgers();
      $this->capacityTracker = new Tracker($this->purgers, $this->state);
    }
    return $this->capacityTracker;
  }

  /**
   * Perform pre-flight checks.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. Usually these
   *   objects originate from the queue but direct invalidation is also
   *   possible, in either cases the behavior of your plugin stays the same.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate()
   *
   * @return void
   */
  protected function checksBeforeTakeoff(array $invalidations) {
    $capacity_limit = $this->capacityTracker()->getLimit();
    foreach ($invalidations as $i => $invalidation) {
      if (!$invalidation instanceof InvalidationInterface) {
        throw new BadBehaviorException("Item $i is not a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface derivative.");
      }
    }
    if ($fire = $this->purgeDiagnostics->isSystemOnFire()) {
      throw new DiagnosticsException($fire->getRecommendation());
    }
    if (!$capacity_limit) {
      throw new CapacityException('Capacity limits exceeded.');
    }
    if (($count = count($invalidations)) > $capacity_limit) {
      throw new CapacityException("Capacity limit allows $capacity_limit invalidations during this request, $count given.");
    }
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
    $this->initializePurgers();
    $enabled = $this->getPluginsEnabled();
    foreach ($ids as $id) {
      if (!isset($enabled[$id])) {
        throw new \LogicException('Invalid id in ::deletePluginsEnabled().');
      }
      $this->purgers[$id]->delete();
      unset($enabled[$id]);
    }
    $this->setPluginsEnabled($enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabels() {
    if (is_null($this->labels)) {
      $this->initializePurgers();
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
      $this->initializePurgers();
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
      $this->initializePurgers();
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
  }

  /**
   * Propagate $this->purgers by initializing the purgers.
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
  public function invalidate(array $invalidations) {
    $this->initializePurgers();

    // Stop when the incoming array is empty (no queue claims, DX improvement).
    if (empty($invalidations)) {
      return;
    }

    // Prepare before we start processing.
    $types_by_purger = $this->getTypesByPurger();
    $this->checksBeforeTakeoff($invalidations);

    // Discover types in need of processing and - just to be sure - reset state.
    $types = [];
    foreach ($invalidations as $i => $invalidation) {
      $types[$i] = $invalidation->getType();
      $invalidation->setStateContext(NULL);
    }

    // Iterate the purgers and start invalidating the items each one supports.
    foreach ($this->purgers as $id => $purger) {
      $supported = $groups = [];

      // Set context and presort the invalidations that this purger supports.
      foreach ($invalidations as $i => $invalidation) {
        $invalidation->setStateContext($id);
        if ($invalidation->getState() == InvalidationInterface::SUCCEEDED) {
          continue;
        }
        if (!in_array($types[$i], $types_by_purger[$id])) {
          $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
          continue;
        }
        $supported[$i] = $invalidation;
      }

      // Filter supported objects and group them by the right purger methods.
      foreach ($types_by_purger[$id] as $type) {
        $method = $purger->routeTypeToMethod($type);
        if (!isset($groups[$method])) {
          $groups[$method] = [];
        }
        foreach ($types as $i => $invalidation_type) {
          if ($invalidation_type === $type) {
            $groups[$method][$i] = $invalidations[$i];
          }
        }
      }

      // Invalidate objects by offering each group to its method on the purger.
      foreach ($groups as $method => $offers) {
        if (!count($offers)) {
          continue;
        }
        $purger->$method($offers);
      }
    }

    // As processing finished we have the obligation to reset context. A call to
    // getState() will now lead to evaluation of the outcome for each object.
    foreach ($invalidations as $i => $invalidation) {
      $invalidation->setStateContext(NULL);
    }
  }

}
