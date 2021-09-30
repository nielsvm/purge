<?php

namespace Drupal\Tests\purge\Kernel\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\Tests\purge\Kernel\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService.
 *
 * @group purge
 */
class ServiceSmokeAndFireTest extends KernelServiceTestBase {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $serviceId = 'purge.diagnostics';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test', 'purge_processor_test'];

  /**
   * Set up the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemOnFire()
   */
  public function testIsSystemOnFireReturnsFalse(): void {
    $this->initializePurgersService(['ida' => 'a']);
    $this->initializeService();
    $this->assertFalse(is_object($this->service->isSystemOnFire()));
    $this->assertEquals($this->service->isSystemOnFire(), FALSE);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemOnFire()
   */
  public function testIsSystemOnFireReturnsCheck(): void {
    $this->initializePurgersService([]);
    $this->initializeService();
    // ERROR level check is expected now because we didn't load any purgers.
    $fire = $this->service->isSystemOnFire();
    $this->assertTrue(is_object($fire));
    if (is_object($fire)) {
      $this->assertTrue($fire instanceof DiagnosticCheckInterface);
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemShowingSmoke()
   */
  public function testIsSystemShowingSmokeReturnsFalse(): void {
    $this->initializePurgersService(['idb' => 'b']);
    $this->initializeService();
    $smoke = $this->service->isSystemShowingSmoke();
    $this->assertTrue(is_object($smoke));
    if (is_object($smoke)) {
      $this->assertTrue($smoke instanceof DiagnosticCheckInterface);
    }
  }

}
