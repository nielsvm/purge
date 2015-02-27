<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\InvalidationRegularExpressionTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\RegularExpression.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class InvalidationRegularExpressionTest extends PluginTestBase {
  protected $plugin_id = 'regex';
  protected $expressions = ['\.(jpg|jpeg|css|js)$'];
  protected $expressionsInvalid = [NULL, ''];
  
}
