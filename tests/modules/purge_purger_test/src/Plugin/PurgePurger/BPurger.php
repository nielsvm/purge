<?php

/**
 * @file
 * Contains \Drupal\purge_purger_test\Plugin\PurgePurger\BPurger.
 */

namespace Drupal\purge_purger_test\Plugin\PurgePurger;

use Drupal\purge_purger_test\Plugin\PurgePurger\NullPurgerBase;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "b",
 *   label = @Translation("Purger B"),
 *   description = @Translation("Test purger B."),
 *   types = {"regex", "url"},
 * )
 */
class BPurger extends NullPurgerBase {}
