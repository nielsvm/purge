<?php

namespace Drupal\Tests\purge\Functional;

use Drupal\Tests\BrowserTestBase as RealBrowserTestBase;
use Drupal\Tests\purge\Traits\TestTrait;

/**
 * Thin and generic WTB for purge tests.
 *
 * @see \Drupal\Tests\BrowserTestBase
 * @see \Drupal\Tests\purge\Traits\TestTrait
 */
abstract class BrowserTestBase extends RealBrowserTestBase {
  use TestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge'];

  /**
   * Set up the test object.
   *
   * @param bool $switch_to_memory_queue
   *   Whether to switch the default queue to the memory backend or not.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp();

    // The default 'database' queue backend gives issues, switch to 'memory'.
    if ($switch_to_memory_queue) {
      $this->setMemoryQueue();
    }
  }

}
