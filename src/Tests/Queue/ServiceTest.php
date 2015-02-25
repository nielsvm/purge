<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queue\ServiceTest.
 */

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\ServiceTestBase;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;

/**
 * Tests the Queue service API ('purge.queue').
 *
 * @group purge
 * @see \Drupal\purge\Queue\Service
 * @see \Drupal\purge\Queue\ServiceInterface
 */
class ServiceTest extends ServiceTestBase {
  protected $serviceId = 'purge.queue';

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->initializeQueueService('memory');
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Tests \Drupal\purge\Queue\Service::getPlugins
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
   *   - \Drupal\purge\Queue\Service::getPluginsEnabled
   *   - \Drupal\purge\Queue\Service::reload
   */
  public function testGetPluginsEnabled() {
    $this->initializeQueueService('file');
    $this->assertEqual(['file'], $this->purgeQueue->getPluginsEnabled());
    $this->initializeQueueService('memory');
    $this->assertEqual(['memory'], $this->purgeQueue->getPluginsEnabled());
    $this->initializeQueueService('DOESNOTEXIST');
    $this->assertEqual(['null'], $this->purgeQueue->getPluginsEnabled());
    $this->initializeQueueService('memory');
  }

  /**
   * Tests \Drupal\purge\Queue\Service::add, \Drupal\purge\Queue\Service::claim
   */
  public function testAddClaim() {
    $i = $this->getInvalidations(1);
    $this->assertFalse($this->purgeQueue->claim());
    $this->purgeQueue->add($i);
    $c = $this->purgeQueue->claim();
    $this->assertTrue($c instanceof Invalidation);
    $this->assertEqual($i->getId(), $c->getId());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Queue\Service::addMultiple
   *   - \Drupal\purge\Queue\Service::claimMultiple
   */
  public function testAddMultipleClaimMultiple() {
    $i = $this->getInvalidations(50);
    $this->assertTrue(empty($this->purgeQueue->claimMultiple()));
    $this->purgeQueue->addMultiple($i);
    $c = $this->purgeQueue->claim();
    $this->assertTrue($c instanceof Invalidation);
    $this->assertEqual(49, count($this->purgeQueue->claimMultiple(49)));
    $this->assertEqual(0, count($this->purgeQueue->claimMultiple(49)));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Queue\Service::release
   *   - \Drupal\purge\Queue\Service::releaseMultiple
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
    $this->assertEqual(1, count($this->purgeQueue->claimMultiple(4, 1)));
    $this->purgeQueue->releaseMultiple([$claim2, $claim3, $claim4]);
    $this->assertEqual(3, count($this->purgeQueue->claimMultiple(4, 1)));

    // Assert that the claims become available again after our 1*4=4s expired.
    sleep(5);
    $this->assertEqual(4, count($this->purgeQueue->claimMultiple()));
  }

  /**
   * Tests:
   *   - \Drupal\purge\Queue\Service::delete
   *   - \Drupal\purge\Queue\Service::deleteMultiple
   */
  public function testDeleteDeleteMultiple() {
    $this->assertFalse($this->purgeQueue->claim());
    $this->purgeQueue->addMultiple($this->getInvalidations(3));
    $claims = $this->purgeQueue->claimMultiple(3, 1);
    $this->purgeQueue->delete(array_pop($claims));
    sleep(4);
    $claims = $this->purgeQueue->claimMultiple(3, 1);
    $this->assertEqual(2, count($claims));
    $this->purgeQueue->deleteMultiple($claims);
    sleep(4);
    $this->assertFalse($this->purgeQueue->claim());
  }

  /**
   * Tests:
   *   - \Drupal\purge\Queue\Service::deleteOrRelease
   *   - \Drupal\purge\Queue\Service::deleteOrReleaseMultiple
   */
  public function testsDeleteOrReleaseDeleteOrReleaseMultiple() {
    $this->purgeQueue->addMultiple($this->getInvalidations(5));

    // Claim for 1s, mark as purged and assert it gets deleted.
    $claim = $this->purgeQueue->claim(1);
    $claim->setState(Invalidation::STATE_PURGED);
    $this->purgeQueue->deleteOrRelease($claim);
    sleep(3);

    // Claim for 2s, mark all as not-successfull and assert releases.
    $claims = $this->purgeQueue->claimMultiple(10, 2);
    $this->assertEqual(4, count($claims));
    $claims[0]->setState(Invalidation::STATE_NEW);
    $claims[1]->setState(Invalidation::STATE_PURGING);
    $claims[2]->setState(Invalidation::STATE_FAILED);
    $claims[3]->setState(Invalidation::STATE_UNSUPPORTED);
    $this->purgeQueue->deleteOrReleaseMultiple($claims);
    sleep(4);
    $this->assertEqual(4, count($this->purgeQueue->claimMultiple()));
  }

}
