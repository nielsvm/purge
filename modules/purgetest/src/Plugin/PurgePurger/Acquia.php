<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Acquia.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * A purger that purges Acquia Cloud.
 *
 * @PurgePurger(
 *   id = "acquia",
 *   label = @Translation("Acquia Purger"),
 *   description = @Translation("A purger that purges Acquia Cloud."),
 *   service_dependencies = {}
 * )
 */
class Acquia extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function purge(Purgeable $purgeable) {
    $purgeable->setState(Purgeable::STATE_PURGING);
    return FALSE;
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
  public function getClaimTimeHint() {
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
