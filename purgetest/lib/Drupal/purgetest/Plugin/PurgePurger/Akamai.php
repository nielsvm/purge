<?php

/**
 * @file
 * Contains \Drupal\purgetest\Plugin\PurgePurger\Akamai.
 */

namespace Drupal\purgetest\Plugin\PurgePurger;

use Drupal\purge\Purger\PurgerBase;
use Drupal\purge\Purgeable\PurgeableInterface;

/**
 * A purger that lets the Akamai CDN purge.
 *
 * @PurgePurger(
 *   id = "akamai",
 *   label = @Translation("Akamai"),
 *   description = @Translation("A purger that lets the Akamai CDN purge."),
 *   service_dependencies = {}
 * )
 */
class Akamai extends PurgerBase {

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