<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\Purge\Purger\CPurger.
 */

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test purger C.
 *
 * @PurgePurger(
 *   id = "c",
 *   label = @Translation("Purger C"),
 *   configform = "",
 *   description = @Translation("Test purger C."),
 *   multi_instance = FALSE,
 *   types = {"wildcardpath", "wildcardurl"},
 * )
 */
class CPurger extends NullPurgerBase {}
