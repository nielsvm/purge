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
 *   types = {"everything"},
 * )
 */
class PurgerA extends Null {

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    return 1;
  }

}
