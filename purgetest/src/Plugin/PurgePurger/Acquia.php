<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Acquia.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Drupal\purge\Purger\PurgerBase;
use Drupal\purge\Purgeable\PurgeableInterface;

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
class Acquia extends PurgerBase {

  /**
   * {@inheritdoc}
   */
  public function purge(PurgeableInterface $purgeable) {
    $purgeable->setState(PurgeableInterface::STATE_PURGING);
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