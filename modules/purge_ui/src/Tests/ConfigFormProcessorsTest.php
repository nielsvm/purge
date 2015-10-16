<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormProcessorsTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\ConfigFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm - processors section.
 *
 * @group purge
 */
class ConfigFormProcessorsTest extends ConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_processor_test'];

  /**
   * Test the processors section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormProcessors
   */
  public function testFormProcessorsSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('Processors queue items in the queue upon certain events.');
    $this->assertRaw('Add processor');
    $this->assertRaw('purge_processor_test.a');
    $this->assertRaw('Processor A');
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/purge_processor_test.a/disable"');
    $this->assertRaw('purge_processor_test.b');
    $this->assertRaw('Processor B');
    $this->assertRaw("A processor that doesn't process but still is one!");
    $this->assertRaw('href="/admin/config/development/performance/purge/processor/purge_processor_test.b/disable"');
    $this->assertNoRaw('Processor C');
    $this->assertNoRaw('Processor D');
  }

}
