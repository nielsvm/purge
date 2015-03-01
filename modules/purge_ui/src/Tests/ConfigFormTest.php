<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\ConfigFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ConfigForm.
 *
 * @group purge
 */
class ConfigFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * Route providing the main configuration form of the purge module.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.config_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_noqueuer_test',
    'purge_purger_test',
    'purge_queue_test',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
    'purge_ui'
  ];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route);
    }
  }

  /**
   * Asserts local tasks in the page output.
   *
   * @warning
   * This helper is copied from its original and needs maintenance.
   *
   * @see \Drupal\system\Tests\Menu\LocalTasksTest.
   */
  protected function assertLocalTasks(array $routes, $level = 0) {
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(
      ':class' => $level == 0 ? 'tabs primary' : 'tabs secondary',
    ));
    $this->assertTrue(count($elements), 'Local tasks found.');
    foreach ($routes as $index => $route_info) {
      list($route_name, $route_parameters) = $route_info;
      $expected = Url::fromRoute($route_name, $route_parameters)->toString();
      $method = ($elements[$index]['href'] == $expected ? 'pass' : 'fail');
      $this->{$method}(format_string('Task @number href @value equals @expected.', array(
        '@number' => $index + 1,
        '@value' => (string) $elements[$index]['href'],
        '@expected' => $expected,
      )));
    }
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
    $this->assertLocalTasks([['system.performance_settings', []], [$this->route->getRouteName(), []]]);
  }

  /**
   * Test the visual status report on the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormDiagnosticReport
   */
  public function testFormDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('edit-diagnostics');
    $this->assertRaw('system-status-report');
    $this->assertRaw('open="open"');
    $this->assertText('Status');
    $this->assertText('Capacity');
    $this->assertText('Tags queuer');
    $this->assertText('Always a warning');
    $this->assertText('Always informational');
    $this->assertText('Always ok');
    $this->assertText('Always an error');
  }

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormQueue
   * @see \Drupal\purge_ui\Form\ConfigForm::validateFormQueue
   * @see \Drupal\purge_ui\Form\ConfigForm::submitFormQueue
   */
  public function testFormQueueSection() {
    $this->initializeQueueService();
    $this->drupalLogin($this->admin_user);
    // Assert that the configured queue is selected on page load.
    $this->drupalGet($this->route);
    $this->assertFieldChecked('edit-queue-plugin-database');
    // Test that just submitting the form, results in the exact same config.
    $this->drupalPostForm($this->route, [], t('Save configuration'));
    $this->purgeQueue->reload();
    $this->assertEqual(['database'], $this->purgeQueue->getPluginsEnabled());
    // Test that changing the queue plugin, gets reflected in the config.
    $edit = ['queue_plugin' => 'queue_b'];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
    $this->purgeQueue->reload();
    $this->assertEqual(['queue_b'], $this->purgeQueue->getPluginsEnabled());
  }

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormPurgers
   */
  public function testFormPurgersSection() {
    $this->drupalLogin($this->admin_user);
    // Assert that without any enabled purgers, the form stays empty.
    $this->initializePurgersService([]);
    $this->drupalGet($this->route);
    $this->assertNoRaw('<td>Purger A</td>');
    $this->assertNoRaw('<td>Purger B</td>');
    $this->assertNoRaw('<td>Purger C</td>');
    $this->assertNoRaw('<td>Configurable purger</td>');
    // Assert that enabled purgers show up and have the right buttons attached.
    $this->initializePurgersService(['id1' => 'purger_a', 'id2' => 'purger_withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('<td>Purger A</td>');
    $this->assertRaw('<td class="priority-low">id1</td>');
    $this->assertRaw('<td class="priority-low">Test purger A.</td>');
    $this->assertNoRaw('href="/admin/config/development/performance/purge/purger/id1/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id1/delete"');
    $this->assertRaw('<td>Configurable purger</td>');
    $this->assertRaw('<td class="priority-low">id2</td>');
    $this->assertRaw('<td class="priority-low">Test purger with a form attached.</td>');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id2/dialog"');
    $this->assertRaw('href="/admin/config/development/performance/purge/purger/id2/delete"');
    // Assert that the 'Add purger' button only shows up when it actually can.
    $this->assertRaw(t('Add purger'));
    $this->initializePurgersService(['id1' => 'purger_a', 'id2' => 'purger_b', 'id3' => 'purger_c', 'id4' => 'purger_withform']);
    $this->drupalGet($this->route);
    $this->assertNoRaw(t('Add purger'));
  }

}
