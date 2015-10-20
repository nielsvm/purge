<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourcePersistingCounterInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\ResourceCounterInterface;

/**
 * Describes a numeric counter stored in state storage.
 */
interface ResourcePersistingCounterInterface extends ResourceCounterInterface {

  /**
   * Overwrite the counter value if the object already exists in state storage.
   */
  public function setFromState();

  /**
   * Inject the state API for storing the counter persistently.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function setState(StateInterface $state);

}
