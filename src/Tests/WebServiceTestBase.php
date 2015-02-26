<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\WebServiceTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\WebTestBase;
use Drupal\purge\Tests\ServiceTestTrait;

/**
 * Thin and generic WTB for testing \Drupal\purge\ServiceInterface derivatives.
 *
 * @see \Drupal\purge\Tests\WebTestBase
 * @see \Drupal\purge\Tests\ServiceTestTrait
 */
abstract class WebServiceTestBase extends WebTestBase {
  use ServiceTestTrait;

}
