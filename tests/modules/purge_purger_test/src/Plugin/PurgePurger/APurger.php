<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\APurger.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Plugin\PurgePurger\NullPurgerBase;

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
