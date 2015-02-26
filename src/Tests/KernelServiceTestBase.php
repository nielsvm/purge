<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\KernelServiceTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\ServiceTestTrait;

/**
 * Thin and generic KTB for testing \Drupal\purge\ServiceInterface derivatives.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\ServiceTestTrait
 */
abstract class KernelServiceTestBase extends KernelTestBase {
  use ServiceTestTrait;

}
