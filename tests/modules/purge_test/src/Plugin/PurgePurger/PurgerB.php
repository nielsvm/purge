<?php

/**
 * @file
 * Contains \Drupal\purge_test\Plugin\PurgePurger\PurgerB.
 */

namespace Drupal\purge_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Dummy;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "purger_b",
 *   label = @Translation("Purger B"),
 *   description = @Translation("Test purger B."),
 *   service_dependencies = {}
 * )
 */
class PurgerB extends Dummy {}
