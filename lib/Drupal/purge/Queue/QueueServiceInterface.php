<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\QueueServiceInterface.
 */

namespace Drupal\purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\purge\Queue\QueueInterface;

/**
 * Describes the service that holds the underlying QueueInterface plugin.
 */
interface QueueServiceInterface extends ServiceProviderInterface, ServiceModifierInterface {

  /**
   * Instantiate the queue service.
   *
   * @param \Drupal\purge\Queue\QueueInterface $queue
   *   The queue plugin which the service interacts with.
   */
  function __construct(QueueInterface $queue);

  /**
   * Empty the entire queue and reset all statistics.
   */
  function emptyQueue();
}