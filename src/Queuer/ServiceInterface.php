<?php

/**
 * @file
 * Contains \Drupal\purge\Queuer\ServiceInterface.
 */

namespace Drupal\purge\Queuer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\purge\ServiceInterface as PurgeServiceInterface;

/**
 * Describes a service that provides access to the container registered queuers.
 */
interface ServiceInterface extends PurgeServiceInterface, ContainerAwareInterface, \Iterator {

  /**
   * Get the requested queuer object.
   *
   * @param string $id
   *   The container id of the queuer to retrieve.
   *
   * @return \Drupal\purge\Queuer\QueuerInterface|null
   */
  public function get($id);

  /**
   * Get the enabled queuers.
   *
   * @return \Drupal\purge\Queuer\QueuerInterface[]
   */
  public function getEnabled();

}
