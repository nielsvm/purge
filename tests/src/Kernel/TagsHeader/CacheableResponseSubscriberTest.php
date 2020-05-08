<?php

namespace Drupal\Tests\purge\Kernel\TagsHeader;

use Drupal\Tests\purge\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\purge\EventSubscriber\CacheableResponseSubscriber.
 *
 * @group purge
 */
class CacheableResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'purge_tagsheader_test'];

  /**
   * Assert that a particular cache tags header is set.
   *
   * @param string $path
   *   The path of a route to test on.
   * @param string $header_name
   *   The name of the HTTP response header tested.
   */
  protected function assertCacheTagsHeader($path, $header_name): void {
    $request = Request::create($path);
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $header = $response->headers->get($header_name);
    $this->assertNotNull($header, "$header_name header exists.");
    $this->assertTrue(is_string($header));
    $this->assertTrue(strpos($header, 'config:user.role.anonymous') !== FALSE);
    $this->assertTrue(strpos($header, 'rendered') !== FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test header presence.
   */
  public function testHeaderPresence(): void {
    $this->assertCacheTagsHeader('/system/401', 'Header-A');
    $this->assertCacheTagsHeader('/system/401', 'Header-B');
    $this->assertCacheTagsHeader('/system/401', 'Header-C');
  }

}
