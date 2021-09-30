<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\ProcessorDeleteForm;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorDeleteForm.
 *
 * @group purge
 */
class ProcessorDeleteFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_processor_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = ProcessorDeleteForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.processor_delete_form';

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
  protected $routeTitle = 'Are you sure you want to delete the Processor A processor?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeProcessorsService(['a']);
  }

  /**
   * Tests that the "No" cancel button is present.
   */
  public function testNoPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('No');
  }

  /**
   * Tests "No" cancel button form submit.
   */
  public function testNoSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $ajax = $this->postAjaxForm([], 'No');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

  /**
   * Tests that 'Yes, delete..', deletes the processor and closes the window.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::disableProcessor
   */
  public function testDeleteProcessor(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Yes, delete this processor!');
    $this->assertSame(['a'], $this->purgeProcessors->getPluginsEnabled());
    $ajax = $this->postAjaxForm([], 'Yes, delete this processor!');
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->purgeProcessors->reload();
    $this->assertSame(TRUE, is_array($this->purgeProcessors->getPluginsEnabled()));
    $this->assertEmpty($this->purgeProcessors->getPluginsEnabled());
  }

}
