<?php

namespace Drupal\purge\Tests\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\WildcardUrlInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class WildcardUrlInvalidationTest extends PluginTestBase {
  protected $pluginId = 'wildcardurl';
  protected $expressions = ['http://www.test.com/*', 'https://domain/path/*'];
  protected $expressionsInvalid = [
    NULL,
    '',
    'http:// /aa',
    'http://www.test.com',
    'https://domain/path',
  ];

}
