<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\QueueChangeForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueChangeForm.
 *
 * @group purge
 */
class QueueChangeFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queue_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueueChangeForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queue_change_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Change queue engine';

  /**
   * Tests that the selection form looks as expected.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testChangeForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    // Assert some of the page presentation.
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->responseContains('The queue engine is the underlying plugin which stores');
    $this->assertSession()->responseContains('when you change the queue, it will be emptied as well');
    $this->assertSession()->responseContains('Description');
    $this->assertActionExists('edit-cancel', 'Cancel');
    $this->assertActionExists('edit-submit', 'Change');
    // Assert that 'memory' is selected queue.
    $this->assertSession()->checkboxChecked('edit-plugin-id-memory');
  }

  /**
   * Tests that changing the form works as expected.
   */
  public function testChangeFormSubmit(): void {
    $this->drupalLogin($this->adminUser);
    // We're avoiding the use of postAjaxForm() in this instance, as else we're
    // mysteriously logged out of Drupal.
    $form = $this->formInstance()->buildForm([], $this->getFormStateInstance());
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('plugin_id', 'b');
    $ajax = $this->formInstance()->changeQueue($form, $submitted);
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->checkboxChecked('edit-plugin-id-b');
    // Here we can use postAjaxForm: assert that cancellation  works.
    $ajax = $this->postAjaxForm([], 'Cancel');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}
