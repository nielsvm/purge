<?php

namespace Drupal\Tests\purge\Kernel;

use Drupal\Tests\purge\Traits\ServiceTestTrait;

/**
 * Thin and generic KernelTestBase for testing public Purge DIC services.
 *
 * @see \Drupal\Tests\purge\Kernel\KernelTestBase
 * @see \Drupal\Tests\purge\Traits\ServiceTestTrait
 */
abstract class KernelServiceTestBase extends KernelTestBase {
  use ServiceTestTrait;

}
