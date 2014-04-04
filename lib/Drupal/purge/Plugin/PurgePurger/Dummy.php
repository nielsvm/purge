<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurger\Dummy.
 */

namespace Drupal\purge\Plugin\PurgePurger;

use Drupal\purge\Purger\PurgerBase;
use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * A \Drupal\purge\Purger\PurgerInterface compliant dummy purger. This purger is
 * only loaded when no other purgers exist and serves as fall back plugin.
 *
 * @PurgePurger(
 *   id = "dummy",
 *   label = @Translation("Dummy"),
 *   description = @Translation("A purger that does exactly nothing."),
 *   service_dependencies = {}
 * )
 */
class Dummy extends PurgerBase {

  /**
   * Instantiate the dummy purger.
   */
  function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function purge(PurgeableInterface $purgeable) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    throw new \Exception('Not yet implemented');
  }
}
