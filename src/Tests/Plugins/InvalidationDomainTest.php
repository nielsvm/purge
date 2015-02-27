<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationDomainTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\Domain.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationDomainTest extends PluginTestBase {
  protected $plugin_id = 'domain';
  protected $expressions = ['sitea.com', 'www.site.com'];
  protected $expressionsInvalid = [NULL, ''];
  
}
