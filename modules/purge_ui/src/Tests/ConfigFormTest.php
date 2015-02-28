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
   * The route providing purge's configuration form.
   *
   * @var string
   */
  protected $configRoute = 'purge_ui.config_form';

  /**
   * The URL object constructed from $this->configRoute.
   *
   * @var \Drupal\Core\Url
   */
  protected $configUrl = NULL;

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
    $this->configUrl = Url::fromRoute($this->configRoute);
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
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
    $this->drupalGet($this->configUrl);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->configUrl);
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
    $this->assertLocalTasks([
      ['system.performance_settings', []],
      [$this->configRoute, []],
    ]);
  }

  /**
   * Test the visual status report on the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormDiagnosticReport
   */
  public function testFormDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->configUrl);
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
    $this->drupalGet($this->configUrl);
    $this->assertFieldChecked('edit-queue-plugin-database');

    // Test that just submitting the form, results in the exact same config.
    $this->drupalPostForm($this->configUrl, [], t('Save configuration'));
    $this->purgeQueue->reload();
    $this->assertEqual(['database'], $this->purgeQueue->getPluginsEnabled());

    // Test that changing the queue plugin, gets reflected in the config.
    $edit = ['queue_plugin' => 'queue_b'];
    $this->drupalPostForm($this->configUrl, $edit, t('Save configuration'));
    $this->purgeQueue->reload();
    $this->assertEqual(['queue_b'], $this->purgeQueue->getPluginsEnabled());

    // @todo test \Drupal\purge_ui\Form\ConfigForm::validateFormQueue.
  }

  /**
   * Test the queue section of the configuration form.
   *
   * @see \Drupal\purge_ui\Form\ConfigForm::buildFormPurgers
   */
  public function testFormPurgersSection() {
    $this->initializePurgersService();
    $this->drupalLogin($this->admin_user);

    // Assert that by default, none of the available test purgers are selected.
    $this->drupalGet($this->configUrl);
    $this->assertNoFieldChecked('edit-purger-plugins-purger-a');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-b');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-c');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-withform');

    // Assert configuration buttons for purgers that defined a config form.
    $this->assertNoLinkByHref('performance/purge/purger_a?dialog=1');
    $this->assertNoLinkByHref('performance/purge/purger_b?dialog=1');
    $this->assertNoLinkByHref('performance/purge/purger_c?dialog=1');
    $this->assertLinkByHref('performance/purge/purger_withform?dialog=1');

    // Test that just submitting the form, results in the exact same config.
    $this->drupalPostForm($this->configUrl, [], t('Save configuration'));
    $this->purgePurgers->reload();
    $this->assertEqual(['null'], $this->purgePurgers->getPluginsEnabled());

    // Test that configuring two purgers, results in correct config.
    $edit = [
      'purger_plugins[purger_a]' => TRUE,
      'purger_plugins[purger_b]' => TRUE,
      'purger_plugins[purger_c]' => FALSE,
      'purger_plugins[purger_withform]' => FALSE,
    ];
    $this->drupalPostForm($this->configUrl, $edit, t('Save configuration'));
    $this->purgePurgers->reload();
    $this->assertEqual(['purger_a', 'purger_b'], $this->purgePurgers->getPluginsEnabled());

    // Test that the configured purgers, are also selected on the form.
    $this->drupalGet($this->configUrl);
    $this->assertFieldChecked('edit-purger-plugins-purger-a');
    $this->assertFieldChecked('edit-purger-plugins-purger-b');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-c');
    $this->assertNoFieldChecked('edit-purger-plugins-purger-withform');
  }

}
