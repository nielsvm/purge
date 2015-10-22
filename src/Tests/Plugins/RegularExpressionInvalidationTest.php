<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\RegularExpressionInvalidationTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeInvalidation\RegularExpressionInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Invalidation\PluginInterface
 */
class RegularExpressionInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'regex';
  protected $expressions = ['\.(jpg|jpeg|css|js)$'];
  protected $expressionsInvalid = [NULL, ''];

}
