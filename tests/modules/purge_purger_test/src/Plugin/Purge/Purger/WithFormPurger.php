<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\Purge\Purger\WithFormPurger.
 */

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test PurgerWithForm.
 *
 * @PurgePurger(
 *   id = "withform",
 *   label = @Translation("Configurable purger"),
 *   description = @Translation("Test purger with a form attached."),
 *   configform = "\Drupal\purge_purger_test\Form\PurgerConfigForm",
 *   types = {"path"},
 * )
 */
class WithFormPurger extends NullPurgerBase {}
