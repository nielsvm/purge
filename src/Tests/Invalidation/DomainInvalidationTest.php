<?php

namespace Drupal\purge\Tests\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\DomainInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class DomainInvalidationTest extends PluginTestBase {
  protected $pluginId = 'domain';
  protected $expressions = ['sitea.com', 'www.site.com'];
  protected $expressionsInvalid = [NULL, ''];

}
