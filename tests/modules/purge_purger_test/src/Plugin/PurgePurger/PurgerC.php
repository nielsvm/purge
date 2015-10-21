<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerC.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Null;

/**
 * Test purger C.
 *
 * @PurgePurger(
 *   id = "purger_c",
 *   label = @Translation("Purger C"),
 *   description = @Translation("Test purger C."),
 *   types = {"wildcardpath", "wildcardurl"},
 * )
 */
class PurgerC extends Null {}
