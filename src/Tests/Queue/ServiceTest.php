<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queue\ServiceTest.
 */

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueService
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.queue';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', ['queue']);
    $this->initializeQueueService();
    $this->purgeQueue->emptyQueue();
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::getPlugins
   */
  public function testGetPlugins() {
    $this->assertTrue(is_array($this->purgeQueue->getPlugins()));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['file']));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['memory']));
    $this->assertTrue(isset($this->purgeQueue->getPlugins()['database']));
    $this->assertFalse(isset($this->purgeQueue->getPlugins()['null']));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::getPluginsEnabled
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::setPluginsEnabled
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::reload
   */
  public function testSettingAndGettingPlugins() {
    $this->purgeQueue->setPluginsEnabled(['file']);
    $this->assertTrue(in_array('file', $this->purgeQueue->getPluginsEnabled()));
    $this->purgeQueue->setPluginsEnabled(['memory']);
    $this->assertTrue(in_array('memory', $this->purgeQueue->getPluginsEnabled()));
    $thrown = FALSE;
    try {
      $this->purgeQueue->setPluginsEnabled(['DOESNOTEXIST']);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
    $thrown = FALSE;
    try {
      $this->purgeQueue->setPluginsEnabled([]);
    }
    catch (\LogicException $e) {
      $thrown = $e instanceof \LogicException;
    }
    $this->assertTrue($thrown);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::add, \Drupal\purge\Plugin\Purge\Queue\QueueService::claim
   */
  public function testAddClaim() {
    $this->assertFalse($this->purgeQueue->claim());
    $i = $this->getInvalidations(1);
    $this->purgeQueue->add($i);
    $c = $this->purgeQueue->claim();
    $this->assertTrue($c instanceof InvalidationInterface);
    $this->assertTrue($i->getId() === $c->getId());
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\QueueService::emptyQueue
   */
  public function testEmptyQueue() {
    $this->purgeQueue->addMultiple($this->getInvalidations(10));
    $this->purgeQueue->emptyQueue();
    $this->assertFalse($this->purgeQueue->claim());
    $this->assertTrue(empty($this->purgeQueue->claimMultiple()));
    $this->assertTrue(is_int($this->purgeQueue->numberOfItems()));
    $this->assertEqual(0, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::addMultiple
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::claimMultiple
   */
  public function testAddMultipleClaimMultiple() {
    $i = $this->getInvalidations(50);
    $this->assertTrue(empty($this->purgeQueue->claimMultiple()));
    $this->purgeQueue->addMultiple($i);
    $c = $this->purgeQueue->claim();
    $this->assertTrue($c instanceof InvalidationInterface);
    $this->assertTrue(49 === count($this->purgeQueue->claimMultiple(49)));
    $this->assertTrue(0 === count($this->purgeQueue->claimMultiple(49)));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::reload
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::commit
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::claimMultiple
   */
  public function testStateConsistency() {
    $this->purgeQueue->setPluginsEnabled(['database']);
    // Add four objects to the queue. reload it, and verify they're the same.
    $i = $this->getInvalidations(4);
    $i[0]->setState(InvalidationInterface::STATE_NEW);
    $i[1]->setState(InvalidationInterface::STATE_PURGING);
    $i[2]->setState(InvalidationInterface::STATE_FAILED);
    $i[3]->setState(InvalidationInterface::STATE_UNSUPPORTED);
    $this->purgeQueue->addMultiple($i);
    // Reload so that \Drupal\purge\Plugin\Purge\Queue\QueueService::$buffer gets cleaned too.
    $this->purgeQueue->reload();
    // Now it has to refetch all objects, assure their states.
    $claims = $this->purgeQueue->claimMultiple(3, 1);
    $this->assertTrue(InvalidationInterface::STATE_NEW === $claims[0]->getState());
    $this->assertTrue(InvalidationInterface::STATE_PURGING === $claims[1]->getState());
    $this->assertTrue(InvalidationInterface::STATE_FAILED === $claims[2]->getState());
    $this->assertTrue(InvalidationInterface::STATE_UNSUPPORTED === $this->purgeQueue->claim()->getState());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::release
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::releaseMultiple
   */
  public function testReleaseReleaseMultiple() {
    $this->assertFalse($this->purgeQueue->claim());
    $this->purgeQueue->addMultiple($this->getInvalidations(4));
    $claim1 = $this->purgeQueue->claim();
    $claim2 = $this->purgeQueue->claim();
    $claim3 = $this->purgeQueue->claim();
    $claim4 = $this->purgeQueue->claim();
    $this->assertFalse($this->purgeQueue->claim());
    $this->purgeQueue->release($claim1);
    $this->assertTrue(1 === count($this->purgeQueue->claimMultiple(4, 1)));
    $this->purgeQueue->releaseMultiple([$claim2, $claim3, $claim4]);
    $this->assertTrue(3 === count($this->purgeQueue->claimMultiple(4, 1)));

    // Assert that the claims become available again after our 1*4=4s expired.
    sleep(5);
    $this->assertTrue(4 === count($this->purgeQueue->claimMultiple()));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::delete
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::deleteMultiple
   */
  public function testDeleteDeleteMultiple() {
    $this->assertFalse($this->purgeQueue->claim());
    $this->purgeQueue->addMultiple($this->getInvalidations(3));
    $claims = $this->purgeQueue->claimMultiple(3, 1);
    $this->purgeQueue->delete(array_pop($claims));
    sleep(4);
    $claims = $this->purgeQueue->claimMultiple(3, 1);
    $this->assertTrue(2 === count($claims));
    $this->purgeQueue->deleteMultiple($claims);
    sleep(4);
    $this->assertFalse($this->purgeQueue->claim());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::deleteOrRelease
   *   - \Drupal\purge\Plugin\Purge\Queue\QueueService::deleteOrReleaseMultiple
   */
  public function testsDeleteOrReleaseDeleteOrReleaseMultiple() {
    $this->purgeQueue->addMultiple($this->getInvalidations(5));

    // Claim for 1s, mark as purged and assert it gets deleted.
    $claim = $this->purgeQueue->claim(1);
    $claim->setState(InvalidationInterface::STATE_PURGED);
    $this->purgeQueue->deleteOrRelease($claim);
    sleep(3);

    // Claim for 2s, mark all as not-successfull and assert releases.
    $claims = $this->purgeQueue->claimMultiple(10, 2);
    $this->assertTrue(4 === count($claims));
    $claims[0]->setState(InvalidationInterface::STATE_NEW);
    $claims[1]->setState(InvalidationInterface::STATE_PURGING);
    $claims[2]->setState(InvalidationInterface::STATE_FAILED);
    $claims[3]->setState(InvalidationInterface::STATE_UNSUPPORTED);
    $this->purgeQueue->deleteOrReleaseMultiple($claims);
    sleep(4);
    $this->assertTrue(4 === count($this->purgeQueue->claimMultiple()));
  }

}
