<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\Tests\purge\Kernel\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService.
 *
 * @group purge
 */
class ServiceTest extends KernelServiceTestBase {

  /**
   * The name of the service as defined in services.yml.
   *
   * @var string
   */
  protected $serviceId = 'purge.queue';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'purge_queuer_test',
    'purge_purger_test',
  ];

  /**
   * The queuer plugin.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService();
    $this->queuer = $this->purgeQueuers->get('a');
    $this->service->emptyQueue();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::getPlugins.
   */
  public function testGetPlugins(): void {
    $this->assertTrue(is_array($this->service->getPlugins()));
    $this->assertTrue(isset($this->service->getPlugins()['file']));
    $this->assertTrue(isset($this->service->getPlugins()['memory']));
    $this->assertTrue(isset($this->service->getPlugins()['database']));
    $this->assertFalse(isset($this->service->getPlugins()['null']));
  }

  /**
   * Tests ::getPluginsEnabled, ::setPluginsEnabled and ::reload.
   */
  public function testSettingAndGettingPlugins(): void {
    $this->service->setPluginsEnabled(['file']);
    $this->assertTrue(in_array('file', $this->service->getPluginsEnabled()));
    $this->service->setPluginsEnabled(['memory']);
    $this->assertTrue(in_array('memory', $this->service->getPluginsEnabled()));
    $thrown = FALSE;
    try {
      $this->service->setPluginsEnabled(['DOESNOTEXIST']);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
    $thrown = FALSE;
    try {
      $this->service->setPluginsEnabled([]);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
  }

  /**
   * Tests QueueService::add and QueueService::claim.
   */
  public function testAddClaim(): void {
    $this->assertTrue(empty($this->service->claim(10, 10)));
    $i = $this->getInvalidations(1);
    $this->assertNull($this->service->add($this->queuer, [$i]));
    $claims = $this->service->claim(100, 10);
    $this->assertTrue(is_array($claims));
    $this->assertEquals(1, count($claims));
    $this->assertTrue($claims[0] instanceof InvalidationInterface);
    $this->assertTrue($claims[0]->getId() === $i->getId());
    $this->assertEquals($claims[0]->getState(), InvalidationInterface::FRESH);
    // Now test with more objects.
    $this->service->emptyQueue();
    $this->service->add($this->queuer, $this->getInvalidations(50));
    $this->assertEquals(50, $this->service->numberOfItems());
    $this->assertTrue(37 === count($this->service->claim(37, 10)));
    $this->assertTrue(13 === count($this->service->claim(15, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::emptyQueue.
   */
  public function testEmptyQueue(): void {
    $this->service->add($this->queuer, $this->getInvalidations(10));
    $this->service->emptyQueue();
    $this->assertTrue(empty($this->service->claim(10, 10)));
    $this->assertTrue(is_int($this->service->numberOfItems()));
    $this->assertEquals(0, $this->service->numberOfItems());
  }

  /**
   * Tests ::reload, ::reload, ::commit and ::claim.
   */
  public function testStateConsistency(): void {
    $this->service->setPluginsEnabled(['database']);
    // Add eight objects to the queue. reload it, and verify they're the same.
    $invalidations = $this->getInvalidations(8);
    foreach ($invalidations as $invalidation) {
      $invalidation->setStateContext('purger2');
    }
    $invalidations[0]->setState(InvalidationInterface::SUCCEEDED);
    $invalidations[1]->setState(InvalidationInterface::NOT_SUPPORTED);
    $invalidations[1]->setProperty('false', FALSE);
    $invalidations[2]->setState(InvalidationInterface::SUCCEEDED);
    $invalidations[3]->setState(InvalidationInterface::PROCESSING);
    $invalidations[3]->setProperty('secret_key', 0.123);
    $invalidations[4]->setState(InvalidationInterface::FAILED);
    $invalidations[5]->setState(InvalidationInterface::PROCESSING);
    $invalidations[5]->setProperty('some_null_value', NULL);
    $invalidations[6]->setState(InvalidationInterface::FAILED);
    $invalidations[7]->setState(InvalidationInterface::NOT_SUPPORTED);
    foreach ($invalidations as $invalidation) {
      $invalidation->setStateContext(NULL);
    }
    $this->service->add($this->queuer, $invalidations);
    // Now claim items and verify that we're getting exactly the same states.
    $claims = $this->service->claim(8, 1);
    $this->assertTrue($claims[0]->getState() === InvalidationInterface::SUCCEEDED);
    $this->assertTrue($claims[1]->getState() === InvalidationInterface::NOT_SUPPORTED);
    $this->assertTrue($claims[2]->getState() === InvalidationInterface::SUCCEEDED);
    $this->assertTrue($claims[3]->getState() === InvalidationInterface::PROCESSING);
    $this->assertTrue($claims[4]->getState() === InvalidationInterface::FAILED);
    $this->assertTrue($claims[5]->getState() === InvalidationInterface::PROCESSING);
    $this->assertTrue($claims[6]->getState() === InvalidationInterface::FAILED);
    $this->assertTrue($claims[7]->getState() === InvalidationInterface::NOT_SUPPORTED);
    // Switch to the context that created the properties, then verify equality.
    foreach ($claims as $claim) {
      $claim->setStateContext('purger2');
    }
    $this->assertEquals($claims[0]->getProperty('_imaginary'), NULL);
    $this->assertEquals($claims[1]->getProperty('false'), FALSE);
    $this->assertEquals($claims[2]->getProperty('_imaginary'), NULL);
    $this->assertEquals($claims[3]->getProperty('secret_key'), 0.123);
    $this->assertEquals($claims[4]->getProperty('_imaginary'), NULL);
    $this->assertEquals($claims[5]->getProperty('some_null_value'), NULL);
    $this->assertEquals($claims[6]->getProperty('_imaginary'), NULL);
    $this->assertEquals($claims[7]->getProperty('_imaginary'), NULL);

  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::release.
   */
  public function testRelease(): void {
    $this->assertTrue(empty($this->service->claim(10, 10)));
    $this->service->add($this->queuer, $this->getInvalidations(4));
    $claims = $this->service->claim(4, 10);
    $this->assertTrue(empty($this->service->claim(10, 10)));
    $this->service->release([$claims[0]]);
    $this->assertTrue(1 === count($this->service->claim(4, 1)));
    $this->service->release([$claims[1], $claims[2], $claims[3]]);
    $this->assertTrue(3 === count($this->service->claim(4, 1)));

    // Assert that the claims become available again after our 1*4=4s expired.
    sleep(5);
    $this->assertTrue(4 === count($this->service->claim(10, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::delete.
   */
  public function testDelete(): void {
    $this->assertTrue(empty($this->service->claim(10, 10)));
    $this->service->add($this->queuer, $this->getInvalidations(3));
    $claims = $this->service->claim(3, 1);
    $this->service->delete([array_pop($claims)]);
    sleep(4);
    $claims = $this->service->claim(3, 1);
    $this->assertTrue(2 === count($claims));
    $this->service->delete($claims);
    sleep(4);
    $this->assertTrue(empty($this->service->claim(10, 10)));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::handleResults.
   */
  public function testHandleResults(): void {
    $this->service->add($this->queuer, $this->getInvalidations(5));

    // Claim for 1s, mark as purged and assert it gets deleted.
    $claims = $this->service->claim(1, 10);
    $claims[0]->setStateContext('purger1');
    $claims[0]->setState(InvalidationInterface::SUCCEEDED);
    $this->service->handleResults($claims);
    sleep(3);

    // Claim for 2s, mark all as not-successfull and assert releases.
    $claims = $this->service->claim(10, 2);
    $this->assertTrue(4 === count($claims));
    foreach ($claims as $claim) {
      $claim->setStateContext('purger1');
    }
    $claims[0]->setState(InvalidationInterface::SUCCEEDED);
    $claims[1]->setState(InvalidationInterface::PROCESSING);
    $claims[2]->setState(InvalidationInterface::FAILED);
    $claims[3]->setState(InvalidationInterface::NOT_SUPPORTED);
    $this->service->handleResults($claims);
    sleep(4);
    $this->assertTrue(3 === count($this->service->claim(10, 10)));
  }

}
