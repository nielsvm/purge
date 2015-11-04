<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Tests\ConfigurationFormTest.
 */

namespace Drupal\purge_purger_http\Tests;

use Drupal\Core\Form\FormState;
use Drupal\purge_ui\Tests\PurgerConfigFormTestBase;

/**
 * Tests \Drupal\purge_purger_http\Form\ConfigurationForm.
 *
 * @group purge
 */
class ConfigurationFormTest extends PurgerConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_http'];

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'http';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_purger_http\Form\ConfigurationForm';

  /**
   * Verify that the form contains all fields we require.
   */
  public function testFieldExistence() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
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
    // Validate performance form values.
    $this->assertFieldById('edit-timeout', 0.5);
    $this->assertFieldById('edit-connect-timeout', 0.2);
    $this->assertFieldById('edit-max-requests', 100);
  }

  /**
   * Test validating the data.
   */
  public function testFormValidation() {
    // Assert that valid timeout values don't cause validation errors.
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
        'connect_timeout' => 0.3,
        'timeout' => 0.1
      ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $this->assertEqual(0, count($form_state->getErrors()));
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
        'connect_timeout' => 2.3,
        'timeout' => 7.7
      ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $this->assertEqual(0, count($form_state->getErrors()));
    // Submit timeout values that are too low and confirm the validation error.
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
        'connect_timeout' => 0.0,
        'timeout' => 0.0
      ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEqual(2, count($errors));
    $this->assertTrue(isset($errors['timeout']));
    $this->assertTrue(isset($errors['connect_timeout']));
    // Submit timeout values that are too high and confirm the validation error.
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
        'connect_timeout' => 2.4,
        'timeout' => 7.7
      ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEqual(2, count($errors));
    $this->assertTrue(isset($errors['timeout']));
    $this->assertTrue(isset($errors['connect_timeout']));
  }

  /**
   * Test posting data to the HTTP Purger settings form.
   */
  public function testFormSubmit() {
    $this->drupalLogin($this->admin_user);
    $edit = [
      'invalidationtype' => 'wildcardurl',
      'hostname' => 'example.com',
      'port' => 8080,
      'path' => 'node/1',
      'request_method' => 1,
      'timeout' => 6,
      'connect_timeout' => 0.5,
      'max_requests' => 25,
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
    $this->drupalGet($this->route);
    // Load settings form page and test for new values.
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
  }

}
