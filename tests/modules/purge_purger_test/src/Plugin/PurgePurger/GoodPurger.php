<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\GoodPurger.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * A purger that always succeeds.
 *
 * @PurgePurger(
 *   id = "goodpurger",
 *   label = @Translation("Good Purger"),
 *   description = @Translation("A purger that always succeeds."),
 *   types = {"tag, path, domain"},
 * )
 */
class GoodPurger extends Null {

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $this->numberPurged += 1;
    $invalidation->setState(Invalidation::STATE_PURGED);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $this->numberPurged += 1;
      $invalidation->setState(Invalidation::STATE_PURGED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    return 1;
  }

}
