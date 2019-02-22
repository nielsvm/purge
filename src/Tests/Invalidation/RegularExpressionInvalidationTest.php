<?php

namespace Drupal\purge\Tests\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\RegularExpressionInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class RegularExpressionInvalidationTest extends PluginTestBase {
  protected $pluginId = 'regex';
  protected $expressions = ['\.(jpg|jpeg|css|js)$'];
  protected $expressionsInvalid = [NULL, ''];

}
