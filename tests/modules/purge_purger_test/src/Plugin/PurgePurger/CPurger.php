<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\CPurger.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Plugin\PurgePurger\NullPurgerBase;

/**
 * Test purger C.
 *
 * @PurgePurger(
 *   id = "c",
 *   label = @Translation("Purger C"),
 *   description = @Translation("Test purger C."),
 *   types = {"wildcardpath", "wildcardurl"},
 * )
 */
class CPurger extends NullPurgerBase {}
