<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgePurger\PurgerA.
 */

namespace Drupal\purge_testplugins\Plugin\PurgePurger;

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
class PurgerA extends Null {}
