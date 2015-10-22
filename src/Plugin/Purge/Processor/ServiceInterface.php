<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Processor\ServiceInterface.
 */

namespace Drupal\purge\Plugin\Purge\Processor;

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
   * @return \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface|null
   */
  public function get($id);

  /**
   * Get the disabled processing policies available .
   *
   * @return \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface[]
   */
  public function getDisabled();

  /**
   * Get the enabled processing policies object.
   *
   * @return \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface[]
   */
  public function getEnabled();

}
