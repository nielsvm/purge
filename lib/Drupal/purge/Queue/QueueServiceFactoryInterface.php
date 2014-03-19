<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueServiceFactoryInterface.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Describes the factory that creates the QueueService (holding queue plugin).
 */
interface QueueServiceFactoryInterface {

  /**
   * Instantiate the PurgeQueue factory.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   */
  public function __construct(ContainerInterface $service_container);

  /**
   * Returns the QueueServiceInterface compliant instance holding the queue plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container.
   *
   * @return \Drupal\purge\Queue\PurgeQueueInterface
   */
  static public function getServiceInstance(ContainerInterface $service_container);
}
