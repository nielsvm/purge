<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\RuntimeTestServiceInterface.
 */

namespace Drupal\purge\RuntimeTest;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceInterface;
use Drupal\purge\Purger\PurgerServiceInterface;
use Drupal\purge\Queue\QueueServiceInterface;

/**
 * Describes a service that interacts with runtime tests.
 */
interface RuntimeTestServiceInterface extends ServiceInterface, \Iterator, \Countable {

  /**
   * Instantiate the purger service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purger
   *   The purge executive service, which wipes content from external caches.
   * @param \Drupal\purge\Queue\QueueServiceInterface $purge_queue
   *   The queue in which to store, claim and release purgeable objects from.
   */
  function __construct(PluginManagerInterface $pluginManager, ContainerInterface $service_container, PurgerServiceInterface $purge_purger, QueueServiceInterface $purge_queue);

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
  public function getHookRequirementsArray();
}