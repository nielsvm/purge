<?php

namespace Drupal\Tests\purge\Kernel\DiagnosticCheck;

use Drupal\Tests\purge\Kernel\KernelTestBase;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group purge
 */
class HookRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * Tests that purge_requirements() passes on our diagnostic checks.
   */
  public function testHookRequirements(): void {
    $module_handler = \Drupal::service('module_handler');
    $module_handler->loadInclude('purge', 'install');
    $req = $module_handler->invoke('purge', 'requirements', ['runtime']);
    // Assert presence of all DiagnosticCheck plugins we know off.
    $this->assertSame(TRUE, isset($req["capacity"]));
    $this->assertSame(TRUE, isset($req["maxage"]));
    $this->assertSame(TRUE, isset($req["memoryqueuewarning"]));
    $this->assertSame(TRUE, isset($req["processorsavailable"]));
    $this->assertSame(TRUE, isset($req["purgersavailable"]));
    $this->assertSame(TRUE, isset($req["queuersavailable"]));
    $this->assertSame(TRUE, isset($req["alwayserror"]));
    $this->assertSame(TRUE, isset($req["alwayswarning"]));
    $this->assertSame(FALSE, isset($req["alwaysinfo"]));
    $this->assertSame(FALSE, isset($req["alwaysok"]));
    $this->assertSame(FALSE, isset($req["purgerwarning"]));
    $this->assertSame(FALSE, isset($req["queuewarning"]));
    // Assert check titles.
    $this->assertSame('Purge: Always a warning', $req['alwayswarning']['title']);
    $this->assertSame('Purge: Always an error', $req['alwayserror']['title']);
    // Assert that the descriptions come through.
    $this->assertSame('This is a warning for testing.', $req['alwayswarning']['description']);
    $this->assertSame('This is an error for testing.', $req['alwayserror']['description']);
    // Assert that the severities come through properly.
    $this->assertSame(1, $req['alwayswarning']['severity']);
    $this->assertSame(2, $req['alwayserror']['severity']);
    // Assert that the severity statuses come through properly.
    $this->assertSame('warning', $req['alwayswarning']['severity_status']);
    $this->assertSame('error', $req['alwayserror']['severity_status']);
    // Assert that the values come through properly.
    $this->assertSame(TRUE, is_string($req['capacity']['value']));
    $this->assertSame("0", $req['capacity']['value']);
  }

}
