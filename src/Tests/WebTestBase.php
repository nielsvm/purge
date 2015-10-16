<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\WebTestBase.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\TestTrait;
use Drupal\simpletest\WebTestBase as RealWebTestBase;

/**
 * Thin and generic WTB for purge tests.
 *
 * @see \Drupal\simpletest\WebTestBase
 * @see \Drupal\purge\Tests\TestTrait
 */
abstract class WebTestBase extends RealWebTestBase {
  use TestTrait;

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
    $this->configFactory = $this->container->get('config.factory');
    $this->initializeQueueService('memory');
  }

}
