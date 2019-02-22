<?php

namespace Drupal\purge\Tests\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class EverythingInvalidationTest extends PluginTestBase {
  protected $pluginId = 'everything';
  protected $expressions = [NULL];
  protected $expressionsInvalid = ['', 'foobar'];

}
