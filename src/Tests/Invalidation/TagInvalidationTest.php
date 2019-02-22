<?php

namespace Drupal\purge\Tests\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class TagInvalidationTest extends PluginTestBase {
  protected $pluginId = 'tag';
  protected $expressions = [
    'tag',
    'user:1',
    'menu:footer',
  ];
  protected $expressionsInvalid = [
    NULL,
    '',
    ['node', '1'],
    'wildtag:*',
  ];

}
