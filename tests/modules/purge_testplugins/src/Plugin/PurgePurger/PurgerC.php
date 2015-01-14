<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgePurger\PurgerC.
 */

namespace Drupal\purge_testplugins\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

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
class PurgerC extends Null {}
