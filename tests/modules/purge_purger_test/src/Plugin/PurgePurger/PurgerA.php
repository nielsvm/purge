<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerA.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Null;

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
class PurgerA extends Null {}
