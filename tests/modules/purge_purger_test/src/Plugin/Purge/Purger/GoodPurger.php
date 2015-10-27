<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\Purge\Purger\GoodPurger.
 */

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * A purger that always succeeds.
 *
 * @PurgePurger(
 *   id = "good",
 *   label = @Translation("Good Purger"),
 *   description = @Translation("A purger that always succeeds."),
 *   configform = "",
 *   types = {"tag", "path", "domain"},
 * )
 */
class GoodPurger extends NullPurgerBase {

  /**
   * {@inheritdoc}
   */
  public function invalidate(InvalidationInterface $invalidation) {
    $invalidation->setState(InvalidationInterface::STATE_PURGED);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::STATE_PURGED);
    }
  }

}
