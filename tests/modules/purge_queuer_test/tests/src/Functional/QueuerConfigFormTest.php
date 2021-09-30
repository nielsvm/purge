<?php

namespace Drupal\Tests\purge_queuer_test\Functional;

use Drupal\purge_queuer_test\Form\QueuerConfigForm;
use Drupal\Tests\purge_ui\Functional\Form\Config\QueuerConfigFormTestBase;

/**
 * Tests \Drupal\purge_queuer_test\Form\QueuerConfigForm.
 *
 * @group purge
 */
class QueuerConfigFormTest extends QueuerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queuer_test'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'withform';

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_queuer_test.configform';

  /**
   * Verify that the form contains all fields we require.
   */
  public function testFieldExistence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->fieldExists('edit-textfield');
    $this->assertSession()->pageTextContains("Test");
  }

  /**
   * Test validating the data.
   */
  public function testFormValidation(): void {
    // Assert that no validation errors occur in the testing form.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValues([
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ]);
    $form = $this->getFormInstance();
    $this->formBuilder()->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEquals(0, count($errors));
  }

  /**
   * {@inheritdoc}
   */
  public function testSaveConfigurationSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $edit = [
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ];
    $this->submitForm($edit, 'Save configuration');
  }

}
