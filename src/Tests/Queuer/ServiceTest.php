<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queuer\ServiceTest.
 */

namespace Drupal\purge\Tests\Queuer;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersService
 * @see \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.queuers';
  public static $modules = ['purge_queuer_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
    $this->installConfig(['purge_queuer_test']);
  }

  /**
   * Tests the \Iterator implementation, ::getEnabled and ::getDisabled.
   */
  public function testWholeService() {
    $this->initializeService();
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::current
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::key
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::next
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::rewind
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::valid
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $id => $queuer) {
      $this->assertTrue($queuer instanceof QueuerInterface);
      $this->assertTrue(in_array($id, [
        'purge.queuers.cache_tags',
        'a',
        'b',
        'c']));
      $items++;
    }
    $this->assertEqual(4, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('purge.queuers.cache_tags', $this->service->current()->getId());
    $this->assertNull($this->service->next());
    $this->assertEqual('a', $this->service->current()->getId());
    $this->assertTrue($this->service->valid());
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::getEnabled.
    $this->assertEqual(2, count($this->service->getEnabled()));
    foreach ($this->service->getEnabled() as $id => $queuer) {
      $this->assertTrue(in_array($id, ['purge.queuers.cache_tags', 'a']), $id);
    }
    // Tests \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface::getDisabled
    $this->assertEqual(2, count($this->service->getDisabled()));
    foreach ($this->service->getDisabled() as $id => $queuer) {
      $this->assertTrue(in_array($id, ['b', 'c']), $id);
    }
  }

}
