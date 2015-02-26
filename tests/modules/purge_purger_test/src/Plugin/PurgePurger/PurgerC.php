<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerC.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

/**
 * Test purger C.
 *
 * @PurgePurger(
 *   id = "purger_c",
 *   label = @Translation("Purger C"),
 *   description = @Translation("Test purger C."),
 * )
 */
class PurgerC extends Null {

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
