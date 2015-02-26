<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\DiagnosticCheck\ServiceSmokeAndFireTest.
 */

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\DiagnosticCheck\ServiceInterface;

/**
 * Tests \Drupal\purge\DiagnosticCheck\Service.
 *
 * @group purge
 * @see \Drupal\purge\DiagnosticCheck\Service
 * @see \Drupal\purge\DiagnosticCheck\ServiceInterface
 */
class ServiceSmokeAndFireTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.diagnostics';
  public static $modules = ['purge_purger_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want it to initialize the service yet.
    KernelTestBase::setUp();
    $this->initializePurgersService(['purger_a']);
    $this->initializeService();
  }

  /**
   * Tests \Drupal\purge\DiagnosticCheck\Service::isSystemOnFire.
   */
  public function testIsSystemOnFire() {
    $this->assertFalse($this->service->isSystemOnFire());
  }

  /**
   * Tests \Drupal\purge\DiagnosticCheck\Service::isSystemShowingSmoke.
   */
  public function testIsSystemShowingSmoke() {
    $this->assertFalse($this->service->isSystemShowingSmoke());
  }

}
