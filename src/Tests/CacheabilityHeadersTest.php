<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\CacheabilityHeadersTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\WebTestBase;

/**
 * Tests purge.services.yml enabling the X-Drupal-Cache-Tags response header.
 *
 * @group purge
 */
class CacheabilityHeadersTest extends WebTestBase {
  public function testHeaderPresence() {
    $this->assertTrue(TRUE);
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(is_string($header), 'X-Drupal-Cache-Tags present and a string.');
    $this->assertFalse(empty($header), 'X-Drupal-Cache-Tags is not empty.');
  }

}
