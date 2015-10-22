<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\DiagnosticCheck\ServiceSmokeAndFireTest.
 */

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\purge\Tests\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\DiagnosticCheck\Service.
 *
 * @group purge
 * @see \Drupal\purge\DiagnosticCheck\Service
 * @see \Drupal\purge\DiagnosticCheck\ServiceInterface
 */
class ServiceSmokeAndFireTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.diagnostics';
  public static $modules = ['purge_purger_test', 'purge_processor_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelServiceTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests:
   *   - \Drupal\purge\DiagnosticCheck\Service::isSystemOnFire()
   *   - \Drupal\purge\DiagnosticCheck\Service::isSystemShowingSmoke()
   */
  public function testIsSystemOnFireOrShowingSmoke() {
    $this->initializePurgersService(['ida' => 'a']);
    $this->initializeService();
    $this->assertFalse(is_object($this->service->isSystemOnFire()));
    if ($this->assertTrue(is_bool($this->service->isSystemOnFire()))) {
      $this->assertFalse($this->service->isSystemOnFire());
    }
    $this->assertTrue(is_object($this->service->isSystemShowingSmoke()));
  }

}
