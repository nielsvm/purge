<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourceTracking\PersistentCounterInterface.
 */

namespace Drupal\purge\Plugin\Purge\Purger\ResourceTracking;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\ResourceTracking\CounterInterface;

/**
 * Describes a numeric counter stored in state storage.
 */
interface PersistentCounterInterface extends CounterInterface {

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
