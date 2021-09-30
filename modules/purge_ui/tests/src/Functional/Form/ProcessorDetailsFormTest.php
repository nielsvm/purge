<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\PluginDetailsForm;

/**
 * Tests \Drupal\purge_ui\Form\PluginDetailsForm (for processors).
 *
 * @group purge
 */
class ProcessorDetailsFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_processor_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = PluginDetailsForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_ui.plugin_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.processor_detail_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'a'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Processor A';

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeProcessorsService(['a']);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Processor A');
    $this->assertSession()->responseContains('Test processor A.');
    $this->assertSession()->responseContains('Close');
    $ajax = $this->postAjaxForm([], 'Close');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}
