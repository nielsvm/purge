<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class EverythingInvalidationTest extends PluginTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId = 'everything';

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = [NULL];

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid = ['', 'foobar'];

}
