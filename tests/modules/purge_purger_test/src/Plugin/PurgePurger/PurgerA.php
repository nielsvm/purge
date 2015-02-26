<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerA.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

/**
 * Test purger A.
 *
 * @PurgePurger(
 *   id = "purger_a",
 *   label = @Translation("Purger A"),
 *   description = @Translation("Test purger A."),
 * )
 */
class PurgerA extends Null {

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
