<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\Purge\Purger\APurger.
 */

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test purger A.
 *
 * @PurgePurger(
 *   id = "a",
 *   label = @Translation("Purger A"),
 *   description = @Translation("Test purger A."),
 *   types = {"everything"},
 * )
 */
class APurger extends NullPurgerBase {}
