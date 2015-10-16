<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Tests\ConfigurationFormTest;
 */

namespace Drupal\purge_purger_http\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_purger_http\Form\ConfigurationForm.
 *
 * @group purge
 */
class ConfigurationFormTest extends WebTestBase {

  /**
   * User account with suitable permission to access the form.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_noqueuer_test', 'purge_ui', 'purge_purger_http'];

  /**
   * The route to a purgers configuration form (takes argument 'purger').
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->initializePurgersService(['testid' => 'http']);
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route, ['id' => 'testid']);
      $this->route->setAbsolute(FALSE);
    }
  }

  /**
   * Test the HTTP purger settings form.
   */
  public function testHttpPurgerSettings() {
    $this->drupalLogin($this->admin_user);

    // Verify if we can successfully access the HTTP purger form.
    $this->drupalGet($this->route);
    $this->assertResponse(200);
    $this->assertTitle(t('Configure HTTP Purger | Drupal'));
    $this->assertField('edit-invalidationtype');

    // Verify every HTTP settings field exists.
    $this->assertField('edit-hostname');
    $this->assertField('edit-port');
    $this->assertField('edit-path');
    $this->assertField('edit-request-method');

    // Validate HTTP settings form values.
    $this->assertFieldById('edit-hostname', 'localhost');
    $this->assertFieldById('edit-port', 80);
    $this->assertFieldById('edit-path', '');
    $this->assertOptionSelected('edit-request-method', 0);

    // Verify every performance field exists.
    $this->assertField('edit-timeout');
    $this->assertField('edit-connect-timeout');
    $this->assertField('edit-max-requests');
    $this->assertField('edit-execution-time-consumption');

    // Validate performance form values.
    $this->assertFieldById('edit-timeout', 3);
    $this->assertFieldById('edit-connect-timeout', 1.5);
    $this->assertFieldById('edit-max-requests', 50);
    $this->assertFieldById('edit-execution-time-consumption', 0.75);

    // Verify that there's no access bypass.
    $this->drupalLogout();
    $this->drupalGet($this->route);
    $this->assertResponse(403);
  }

  /**
   * Test posting data to the HTTP Purger settings form.
   */
  public function testHttpPurgerSettingsPost() {
    $this->drupalLogin($this->admin_user);

    // Post form with new values.
    $edit = [
      'invalidationtype' => 'wildcardurl',
      'hostname' => 'example.com',
      'port' => 8080,
      'path' => 'node/1',
      'request_method' => 1,
      'timeout' => 6,
      'connect_timeout' => 0.5,
      'max_requests' => 25,
      'execution_time_consumption' => 0.25,
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));

    // Load settings form page and test for new values.
    $this->drupalGet($this->route);
    $this->assertFieldById('edit-invalidationtype', $edit['invalidationtype']);

    // HTTP settings
    $this->assertFieldById('edit-hostname', $edit['hostname']);
    $this->assertFieldById('edit-port', $edit['port']);
    $this->assertFieldById('edit-path', $edit['path']);
    $this->assertFieldById('edit-request-method', $edit['request_method']);

    // Performance
    $this->assertFieldById('edit-timeout', $edit['timeout']);
    $this->assertFieldById('edit-connect-timeout', $edit['connect_timeout']);
    $this->assertFieldById('edit-max-requests', $edit['max_requests']);
    $this->assertFieldById('edit-execution-time-consumption', $edit['execution_time_consumption']);
  }

}
