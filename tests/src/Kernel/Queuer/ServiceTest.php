<?php

namespace Drupal\Tests\purge\Kernel\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\Tests\purge\Kernel\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersService.
 *
 * @group purge
 */
class ServiceTest extends KernelServiceTestBase {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $serviceId = 'purge.queuers';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queuer_test'];

  /**
   * Set up the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->installConfig(['purge_queuer_test']);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersService::count.
   */
  public function testCount(): void {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEquals(2, count($this->service));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Processor\QueuersService::get.
   */
  public function testGet(): void {
    $this->initializeService();
    $this->assertTrue($this->service->get('a') instanceof QueuerInterface);
    $this->assertTrue($this->service->get('b') instanceof QueuerInterface);
    $this->assertFalse($this->service->get('c'));
    $this->service->setPluginsEnabled(['c']);
    $this->assertTrue($this->service->get('c') instanceof QueuerInterface);
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::current
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::key
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::next
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::rewind
   * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService::valid
   */
  public function testIteration(): void {
    $this->initializeService();
    $this->assertIterator(
      ['a', 'b'],
      '\Drupal\purge\Plugin\Purge\Queuer\QueuerInterface'
    );
  }

}
