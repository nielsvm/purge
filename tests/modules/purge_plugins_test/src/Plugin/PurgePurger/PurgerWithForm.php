<?php

/**
 * @file
 * Contains \Drupal\purge_plugins_test\Plugin\PurgePurger\PurgerWithForm.
 */

namespace Drupal\purge_plugins_test\Plugin\PurgePurger;

use Drupal\purge\Plugin\PurgePurger\Null;

/**
 * Test PurgerWithForm.
 *
 * @PurgePurger(
 *   id = "purger_withform",
 *   label = @Translation("Configurable purger"),
 *   description = @Translation("Test purger with a form attached."),
 *   configform = "\Drupal\purge_plugins_test\Form\PurgerConfigForm",
 * )
 */
class PurgerWithForm extends Null {}
