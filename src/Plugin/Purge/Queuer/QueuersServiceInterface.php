<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface.
 */

namespace Drupal\purge\Plugin\Purge\Queuer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\purge\ServiceInterface;

/**
 * Describes a service that provides access to the container registered queuers.
 */
interface QueuersServiceInterface extends ServiceInterface, ContainerAwareInterface, \Iterator {

  /**
   * Get the requested queuer object.
   *
   * @param string $id
   *   The container id of the queuer to retrieve.
   *
   * @return \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface|null
   */
  public function get($id);

  /**
   * Get the disabled queuers.
   *
   * @return \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface[]
   */
  public function getDisabled();

  /**
   * Get the enabled queuers.
   *
   * @return \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface[]
   */
  public function getEnabled();

}
