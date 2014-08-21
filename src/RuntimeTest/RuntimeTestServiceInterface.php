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
interface RuntimeTestServiceInterface extends ServiceInterface {

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

}
