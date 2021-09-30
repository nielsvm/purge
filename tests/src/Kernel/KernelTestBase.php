<?php

namespace Drupal\Tests\purge\Kernel;

use Drupal\KernelTests\KernelTestBase as RealKernelTestBase;
use Drupal\Tests\purge\Traits\TestTrait;

/**
 * Thin and generic KernelTestBase for purge tests.
 *
 * @see \Drupal\KernelTests\KernelTestBase
 * @see \Drupal\Tests\purge\Traits\TestTrait
 */
abstract class KernelTestBase extends RealKernelTestBase {
  use TestTrait;

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
    $this->installConfig(['purge']);

    // The default 'database' queue backend gives issues, switch to 'memory'.
    if ($switch_to_memory_queue) {
      $this->setMemoryQueue();
    }
  }

}
