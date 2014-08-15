<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgePurger\PurgerA.
 */

namespace Drupal\purge_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Dummy;

/**
 * Test purger A.
 *
 * @PurgePurger(
 *   id = "purger_a",
 *   label = @Translation("Purger A"),
 *   description = @Translation("Test purger A."),
 *   service_dependencies = {}
 * )
 */
class PurgerA extends Dummy {}
