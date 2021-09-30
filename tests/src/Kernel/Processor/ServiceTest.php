<?php

namespace Drupal\Tests\purge\Kernel\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\Tests\purge\Kernel\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
 *
 * @group purge
 */
class ServiceTest extends KernelServiceTestBase {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $serviceId = 'purge.processors';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_processor_test'];

  /**
   * Set up the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::count.
   */
  public function testCount(): void {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEquals(2, count($this->service));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::get.
   */
  public function testGet(): void {
    $this->initializeService();
    $this->assertTrue($this->service->get('a') instanceof ProcessorInterface);
    $this->assertTrue($this->service->get('b') instanceof ProcessorInterface);
    $this->assertFalse($this->service->get('c'));
    $this->service->setPluginsEnabled(['c']);
    $this->assertTrue($this->service->get('c') instanceof ProcessorInterface);
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::current
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::key
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::next
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::rewind
   * @see \Drupal\purge\Plugin\Purge\Processor\ProcessorsService::valid
   */
  public function testIteration(): void {
    $this->initializeService();
    $this->assertIterator(
      ['a', 'b'],
      '\Drupal\purge\Plugin\Purge\Processor\ProcessorInterface'
    );
  }

}
