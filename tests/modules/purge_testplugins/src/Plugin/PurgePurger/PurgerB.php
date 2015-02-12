<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgePurger\PurgerB.
 */

namespace Drupal\purge_testplugins\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "purger_b",
 *   label = @Translation("Purger B"),
 *   description = @Translation("Test purger B."),
 * )
 */
class PurgerB extends Null {}
