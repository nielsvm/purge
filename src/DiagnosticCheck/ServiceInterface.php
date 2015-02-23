<?php

/**
 * @file
 * Contains \Drupal\purge\DiagnosticCheck\ServiceInterface.
 */

namespace Drupal\purge\DiagnosticCheck;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceInterface as PurgeServiceInterface;
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

/**
 * Describes a service that interacts with diagnostic checks.
 */
interface ServiceInterface extends PurgeServiceInterface, \Iterator, \Countable {

  /**
   * Instantiate the purger service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The queue in which to store, claim and release invalidation objects from.
   */
  function __construct(PluginManagerInterface $pluginManager, PurgerServiceInterface $purge_purgers, QueueServiceInterface $purge_queue);

  /**
   * Generates a hook_requirements() compatible array.
   *
   * @warning
   *   Although it shares the same name, this method doesn't return a individual
   *   item array as \Drupal\purge\DiagnosticCheck\PluginInterface::
   *     getHookRequirementsArray() does. It returns a full array (as
   *   hook_requirements() expects) for all tests.
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
  public function getHookRequirementsArray();

  /**
   * Checks whether one of the diagnostic tests reports full failure.
   *
   * This method provides a simple - boolean evaluable - way to determine if
   * a \Drupal\purge\DiagnosticCheck\PluginInterface::SEVERITY_ERROR severity
   * was reported by one of the tests. If SEVERITY_ERROR was reported, purging
   * cannot continue and should happen once all problems are resolved.
   *
   * @return FALSE or \Drupal\purge\DiagnosticCheck\PluginInterface.
   *   If everything is fine, this returns FALSE. But, if a blocking problem
   *   exists, the first failing test object is returned holding a UI applicable
   *   recommendation message.
   */
  public function isSystemOnFire();
}
