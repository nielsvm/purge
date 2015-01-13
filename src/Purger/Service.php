<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\Service.
 */

namespace Drupal\purge\Purger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\Purger\ServiceInterface;
use Drupal\purge\Purger\Exception\InvalidPurgerBehaviorException;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * Provides the service that allows transparent access to one or more purgers.
 */
class Service extends ServiceBase implements ServiceInterface {

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
   * The plugin ID of the fallback backend.
   */
  const FALLBACK_PLUGIN = 'null';

  /**
   * Instantiate the purger service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $pluginManager;
    $this->serviceContainer = $service_container;
    $this->configFactory = $config_factory;

    // Instantiate all the purgers and let them configure themselves.
    $this->initializePurgers();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($simple = FALSE) {
    if (empty($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
      unset($this->plugins[SELF::FALLBACK_PLUGIN]);
    }
    if (!$simple) {
      return $this->plugins;
    }
    $plugins = array();
    foreach ($this->plugins as $plugin) {
      $plugins[$plugin['id']] = sprintf('%s: %s', $plugin['label'], $plugin['description']);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (empty($this->plugins_enabled)) {
      $conf_plugins = $this->configFactory->get('purge.purger')->get('plugins');
      $plugin_ids = array_keys($this->getPlugins());

      // By default all available purgers are enabled when the 'plugins' setting
      // in 'purge.purger.yml' is set to 'automatic_detection', else those
      // plugins therein specified are enabled.
      if ($conf_plugins == 'automatic_detection') {
        foreach ($plugin_ids as $plugin_id) {
          $this->plugins_enabled[] = $plugin_id;
        }
      }

      // Now a comma separated string with plugin ID's is expected.
      else {
        foreach (explode(',', $conf_plugins) as $plugin_id) {
          $plugin_id = trim($plugin_id);
          if ($plugin_id === SELF::FALLBACK_PLUGIN) {
            continue;
          }
          elseif (!in_array($plugin_id, $plugin_ids)) {
            // When a third-party provided purger was configured and its module
            // got uninstalled, the configuration renders invalid. Instead of
            // rewriting config or breaking hard, we fall back gracefully. The
            // runtime tests take care of getting this visual to the user.
            continue;
          }
          else {
            $this->plugins_enabled[] = $plugin_id;
          }
        }
      }

      // To guard trustworthyness as API, there always has to be a purger that
      // behaves like one, therefore we utilize a NULL backend.
      if (empty($this->plugins_enabled)) {
        $this->plugins_enabled[] = SELF::FALLBACK_PLUGIN;
      }
    }

    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->purgers = NULL;
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
    foreach ($this->getPluginsEnabled() as $plugin_id) {
      $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
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
  public function purge(Purgeable $purgeable) {

    // When there is just one enabled purger, we can directly call \Drupal\purge
    // \Purger\PluginInterface::purge(). As both Service and the enabled plugin
    // are sharing the same interface, the behavior has to be exactly identical.
    if (count($this->purgers) === 1) {
      if (current($this->purgers)->purge($purgeable)) {
        if ($purgeable->getState() !== Purgeable::STATE_PURGED) {
          throw new InvalidPurgerBehaviorException(
            "The purger '" . key($this->purgers) . "' returned TRUE without setting state to STATE_PURGED.");
        }
        return TRUE;
      }
      return FALSE;
    }

    // When multiple purgers are loaded, the situation becomes complexer. One
    // purger can fail (or require a two-step call), while the other succeeds
    // when processing a purgeable. For that reason, a single failing purger
    // will cause this call to return FALSE even if that means that - when
    // being reattempted - the good purger will purge something twice. This
    // approach might cause double purging, but prevents unnecessary complexity.
    foreach ($this->purgers as $plugin_id => $purger) {

      // For every purger that attempts to purge this purgeable, reset its state.
      $purgeable->setState(Purgeable::STATE_CLAIMED);

      // Let this purger; purge the given purgeable and collect the result.
      if ($results[] = $purger->purge($purgeable)) {
        if ($purgeable->getState() !== Purgeable::STATE_PURGED) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' returned TRUE without setting state to STATE_PURGED.");
        }
      }
      else {
        $state = $purgeable->getState();
        if (!(($state === Purgeable::STATE_PURGEFAILED) || ($state === Purgeable::STATE_PURGING))) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' returned FALSE without setting state to PURGING or PURGEFAILED.");
        }

        // Since this purger claims that it failed, we assume the entire set of
        // purgers to have failed. Working purgers might have to repeat purger
        // this object for no reason, but at least we can assure that this
        // purgeable was not entirely purged.
        $purgeable->setState(Purgeable::STATE_PURGEFAILED);
        return FALSE;
      }
    }

    // No purgers failed and therefore the state certainly is STATE_PURGED, as
    // the logic above would have caused exceptions to be thrown otherwise.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {

    foreach ($this->purgers as $plugin_id => $purger) {

      // Set the state of each purgeable object to STATE_CLAIMED.
      foreach ($purgeables as $purgeable) {
        $purgeable->setState(Purgeable::STATE_CLAIMED);
      }

      // Attempt to purge all purgeable objects with this purger.
      if ($purger->purgeMultiple($purgeables)) {

        // As it succeeded, assure all purgeable objects to be in STATE_PURGED.
        foreach ($purgeables as $purgeable) {
          if ($purgeable->getState() !== Purgeable::STATE_PURGED) {
            throw new InvalidPurgerBehaviorException(
              "The purger '$plugin_id' returned TRUE without setting state to STATE_PURGED.");
          }
        }
      }

      // Handle failure and assume the full call to have failed.
      else {

        // When the call failed, check if all purgeables have the right state.
        foreach ($purgeables as $purgeable) {
          $state = $purgeable->getState();
          if (!(($state === Purgeable::STATE_PURGEFAILED) || ($state === Purgeable::STATE_PURGING))) {
            throw new InvalidPurgerBehaviorException(
              "The purger '$plugin_id' returned FALSE without setting state to PURGING or PURGEFAILED.");
          }
        }

        // Since this purger failed, the entire call fails and returns FALSE.
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    static $limit;

    // As the limit gets cached during this request, calculate it only once.
    if (is_null($limit)) {
      $limit = 0;

      // Ask each to report how many purgeable's it thinks it can purge.
      foreach ($this->purgers as $purger) {
        $purger_limit = $purger->getCapacityLimit();
        if (!is_int($purger_limit)) {
          throw new InvalidPurgerBehaviorException(
            "The purger '$plugin_id' did not return an integer on getCapacityLimit().");
        }
        $limit += $purger_limit;
      }

      // When multiple purgers are active, we lower the capacity limit.
      if (count($this->purgers) !== 1) {
        $limit = (int)floor($limit / count($this->purgers));
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
