<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Processor\ServiceTest.
 */

namespace Drupal\purge\Tests\Processor;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Processor\ServiceInterface;
use Drupal\purge\Processor\ProcessorInterface;

/**
 * Tests \Drupal\purge\Processor\Service.
 *
 * @group purge
 * @see \Drupal\purge\Processor\Service
 * @see \Drupal\purge\Processor\ServiceInterface
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
    // Tests \Drupal\purge\Processor\ServiceInterface::current
    // Tests \Drupal\purge\Processor\ServiceInterface::key
    // Tests \Drupal\purge\Processor\ServiceInterface::next
    // Tests \Drupal\purge\Processor\ServiceInterface::rewind
    // Tests \Drupal\purge\Processor\ServiceInterface::valid
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $id => $processor) {
      $this->assertTrue($processor instanceof ProcessorInterface);
      $this->assertTrue(in_array($id, [
        'purge_processor_test.a',
        'purge_processor_test.b',
        'purge_processor_test.c',
        'purge_processor_test.d']));
      $items++;
    }
    $this->assertEqual(4, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('purge_processor_test.a', $this->service->current()->getId());
    $this->assertNull($this->service->next());
    $this->assertEqual('purge_processor_test.b', $this->service->current()->getId());
    $this->assertTrue($this->service->valid());
    // Tests \Drupal\purge\Processor\ServiceInterface::getEnabled.
    $this->assertEqual(2, count($this->service->getEnabled()));
    foreach ($this->service->getEnabled() as $id => $processor) {
      $this->assertTrue(in_array($id, ['purge_processor_test.a', 'purge_processor_test.b']), $id);
    }
    // Tests \Drupal\purge\Processor\ServiceInterface::getDisabled
    $this->assertEqual(2, count($this->service->getDisabled()));
    foreach ($this->service->getDisabled() as $id => $processor) {
      $this->assertTrue(in_array($id, ['purge_processor_test.c', 'purge_processor_test.d']), $id);
    }
  }

}
