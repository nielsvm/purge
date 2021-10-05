<?php

namespace Drupal\Tests\purge\Functional\TagsHeader;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests \Drupal\purge\EventSubscriber\CacheableResponseSubscriber.
 *
 * @group purge
 */
class CacheableResponseSubscriberTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'purge_tagsheader_test',
    'system_test',
    'early_rendering_controller_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Assert that a particular cache tags header is set.
   *
   * @param string $path
   *   The path of a route to test on.
   * @param string $header_name
   *   The name of the HTTP response header tested.
   */
  protected function assertCacheTagsHeader($path, $header_name): void {
    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet($path);
    $this->assertEquals($this->getSession()->getResponseHeader('X-Drupal-Cache'), 'HIT');

    $this->assertSession()->statusCodeEquals(200);

    $this->assertNotNull($this->getSession()->getResponseHeader($header_name), "$header_name header exists.");
    $this->assertTrue(strpos($this->getSession()->getResponseHeader('Cache-Control'), 'public') !== FALSE);
  }

  /**
   * Test header presence.
   */
  public function testHeaderPresence(): void {
    $path = '/early-rendering-controller-test/cacheable-response';
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Prefetch the page to get a cache miss.
    $this->drupalGet($path);
    $this->assertEquals($this->getSession()->getResponseHeader('X-Drupal-Cache'), 'MISS');

    $this->assertCacheTagsHeader($path, 'Header-A');
    $this->assertCacheTagsHeader($path, 'Header-B');
    $this->assertCacheTagsHeader($path, 'Header-C');
  }

}
