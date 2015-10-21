<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\PurgerB.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Null;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "purger_b",
 *   label = @Translation("Purger B"),
 *   description = @Translation("Test purger B."),
 *   types = {"regex", "url"},
 * )
 */
class PurgerB extends Null {}
