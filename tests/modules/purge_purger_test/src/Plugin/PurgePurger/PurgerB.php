<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerB.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "purger_b",
 *   label = @Translation("Purger B"),
 *   description = @Translation("Test purger B."),
 * )
 */
class PurgerB extends Null {

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
