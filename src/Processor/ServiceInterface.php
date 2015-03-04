<?php

/**
 * @file
 * Contains \Drupal\purge\Processor\ServiceInterface.
 */

namespace Drupal\purge\Processor;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\purge\ServiceInterface as PurgeServiceInterface;

/**
 * Describes a service that provides access to registered processing policies.
 */
interface ServiceInterface extends PurgeServiceInterface, ContainerAwareInterface, \Iterator {

  /**
   * Get the requested processor object.
   *
   * @param string $id
   *   The container id of the processor to retrieve.
   *
   * @return \Drupal\purge\Processor\ProcessorInterface|null
   */
  public function get($id);

  /**
   * Get the disabled processing policies available .
   *
   * @return \Drupal\purge\Processor\ProcessorInterface[]
   */
  public function getDisabled();

  /**
   * Get the enabled processing policies object.
   *
   * @return \Drupal\purge\Processor\ProcessorInterface[]
   */
  public function getEnabled();

}
