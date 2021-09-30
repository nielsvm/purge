<?php

namespace Drupal\Tests\purge_purger_test\Functional;

use Drupal\purge_purger_test\Form\PurgerConfigForm;
use Drupal\Tests\purge_ui\Functional\Form\Config\PurgerConfigFormTestBase;

/**
 * Tests \Drupal\purge_purger_test\Form\PurgerConfigForm.
 *
 * @group purge
 */
class PurgerConfigFormTest extends PurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'withform';

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_purger_test.purgerconfigform';

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
