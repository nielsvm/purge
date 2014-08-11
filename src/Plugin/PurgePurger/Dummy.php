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
   * @var int
   */
  protected $failures;

  /**
   * Instantiate the dummy purger.
   */
  function __construct() {
    $this->failures = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function purge(PurgeableInterface $purgeable) {
    $this->failures += 1;
    $purgeable->setState(PurgeableInterface::STATE_PURGEFAILED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    foreach ($purgeables as $purgeable) {
      $this->failures += 1;
      $purgeable->setState(PurgeableInterface::STATE_PURGEFAILED);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurged() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFailed() {
    return $this->failures;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    return 0;
  }
}
