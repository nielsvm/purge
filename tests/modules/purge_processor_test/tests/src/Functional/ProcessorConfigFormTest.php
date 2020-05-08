<?php

namespace Drupal\Tests\purge_processor_test\Functional;

use Drupal\Tests\purge_ui\Functional\ProcessorConfigFormTestBase;

/**
 * Tests \Drupal\purge_processor_test\Form\ProcessorConfigForm.
 *
 * @group purge_processor_test
 */
class ProcessorConfigFormTest extends ProcessorConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_processor_test'];

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'withform';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_processor_test\Form\ProcessorConfigForm';

  /**
   * Verify that the form contains all fields we require.
   */
  public function testFieldExistence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->fieldExists('edit-textfield');
    $this->assertSession()->pageTextContains("Test");
  }

  /**
   * Test validating the data.
   */
  public function testFormValidation(): void {
    // Assert that no validation errors occur in the testing form.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEquals(0, count($errors));
  }

  /**
   * Test posting data to the form.
   */
  public function testFormSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ];
    $this->drupalPostForm($this->route, $edit, 'Save configuration');
  }

}
