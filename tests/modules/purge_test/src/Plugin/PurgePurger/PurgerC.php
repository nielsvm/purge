<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgePurger\PurgerC.
 */

namespace Drupal\purge_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Dummy;

/**
 * Test purger C.
 *
 * @PurgePurger(
 *   id = "purger_c",
 *   label = @Translation("Purger C"),
 *   description = @Translation("Test purger C."),
 *   service_dependencies = {}
 * )
 */
class PurgerC extends Dummy {}
