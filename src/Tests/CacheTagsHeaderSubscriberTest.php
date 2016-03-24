<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\CacheTagsHeaderSubscriberTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests \Drupal\purge\EventSubscriber\CacheTagsHeaderSubscriber.
 *
 * @group purge
 */
class CacheTagsHeaderSubscriberTest extends KernelTestBase {

  /**
   * The name of the cache tags header exported.
   *
   * @var string
   */
  const HEADER = 'X-Cache-Tags';

  /**
   * The name of the subscribing service.
   *
   * @var string
   */
  const SERVICE = 'purge.cache_tags_header_subscriber';

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
    $header = $response->headers->get(SELF::HEADER);
    $this->assertNotNull($header);
    $this->assertTrue(is_string($header));
    $this->assertTrue(strpos($header, 'config:user.role.anonymous') !== FALSE);
    $this->assertTrue(strpos($header, 'rendered') !== FALSE);
  }

  /**
   * Test service presence.
   */
  public function testServicePresence() {
    $this->assertTrue($this->container->has(SELF::SERVICE));
    $this->assertTrue($this->container->getDefinition(SELF::SERVICE)->hasTag('event_subscriber'));
  }

}
