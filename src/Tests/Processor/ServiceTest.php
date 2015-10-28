<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Processor\ServiceTest.
 */

namespace Drupal\purge\Tests\Processor;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService
 * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.processors';
  public static $modules = ['purge_processor_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests the \Iterator implementation, ::getEnabled and ::getDisabled.
   */
  public function testWholeService() {
    $this->initializeService();
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::current
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::key
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::next
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::rewind
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::valid
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $id => $processor) {
      $this->assertTrue($processor instanceof ProcessorInterface);
      $this->assertTrue(in_array($id, [
        'a',
        'b',
        'c',
        'd']));
      $items++;
    }
    $this->assertEqual(4, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('a', $this->service->current()->getId());
    $this->assertNull($this->service->next());
    $this->assertEqual('b', $this->service->current()->getId());
    $this->assertTrue($this->service->valid());
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::getEnabled.
    $this->assertEqual(2, count($this->service->getEnabled()));
    foreach ($this->service->getEnabled() as $id => $processor) {
      $this->assertTrue(in_array($id, ['a', 'b']), $id);
    }
    // Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface::getDisabled
    $this->assertEqual(2, count($this->service->getDisabled()));
    foreach ($this->service->getDisabled() as $id => $processor) {
      $this->assertTrue(in_array($id, ['c', 'd']), $id);
    }
  }

}
