<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\KernelTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\PurgeTestBaseTrait;
use Drupal\simpletest\KernelTestBase as RealKernelTestBase;

/**
 * Thin and generic KTB for purge tests.
 *
 * @see \Drupal\simpletest\KernelTestBase
 * @see \Drupal\purge\Tests\PurgeTestBaseTrait
 */
abstract class KernelTestBase extends RealKernelTestBase {
  use PurgeTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge'];

  /**
   * Set up the test object.
   */
  function setUp() {
    parent::setUp();
    $this->installConfig(['purge']);
    $this->configFactory = $this->container->get('config.factory');
  }

}
