<?php

namespace Drupal\Tests\purge\Kernel;

use Drupal\Tests\purge\Traits\PluginManagerTestTrait;

/**
 * Thin and generic KernelTestBase for testing DIC plugin manager derivatives.
 *
 * @see \Drupal\Tests\purge\Kernel\KernelTestBase
 * @see \Drupal\Tests\purge\Traits\PluginManagerTestTrait
 */
abstract class KernelPluginManagerTestBase extends KernelTestBase {
  use PluginManagerTestTrait;

}
