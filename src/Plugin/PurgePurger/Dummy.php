<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurger\Dummy.
 */

namespace Drupal\purge\Plugin\PurgePurger;

use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface as Purger;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * A \Drupal\purge\Purger\PluginInterface compliant dummy purger. This purger is
 * only loaded when no other purgers exist and serves as fall back plugin.
 *
 * @PurgePurger(
 *   id = "dummy",
 *   label = @Translation("Dummy"),
 *   description = @Translation("A purger that does exactly nothing."),
 *   service_dependencies = {}
 * )
 */
class Dummy extends PluginBase implements Purger {

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
  public function purge(Purgeable $purgeable) {
    $this->failures += 1;
    $purgeable->setState(Purgeable::STATE_PURGEFAILED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    foreach ($purgeables as $purgeable) {
      $this->failures += 1;
      $purgeable->setState(Purgeable::STATE_PURGEFAILED);
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
