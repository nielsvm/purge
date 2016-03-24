<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\CacheableResponseSubscriberTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\purge\EventSubscriber\CacheableResponseSubscriber;

/**
 * Tests \Drupal\purge\EventSubscriber\CacheableResponseSubscriber.
 *
 * @group purge
 */
class CacheableResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test header presence.
   */
  public function testHeaderPresence() {
    $request = Request::create('/system/401');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEqual(200, $response->getStatusCode());
    $header = $response->headers->get(CacheableResponseSubscriber::HEADER);
    $this->assertNotNull($header);
    $this->assertTrue(is_string($header));
    $this->assertTrue(strpos($header, 'config:user.role.anonymous') !== FALSE);
    $this->assertTrue(strpos($header, 'rendered') !== FALSE);
  }

}
