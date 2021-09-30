<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\ProcessorAddForm;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorAddForm.
 *
 * @group purge
 */
class ProcessorAddFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_processor_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = ProcessorAddForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.processor_add_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Which processor would you like to add?';

  /**
   * Tests that the form route is only accessible under the right conditions.
   */
  public function testRouteConditionalAccess(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeProcessorsService(['a', 'b', 'c']);
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeProcessorsService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_processor',
      ]
    );
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the right processors are listed on the form.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->initializeProcessorsService(['a', 'b']);
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Processor A');
    $this->assertSession()->responseNotContains('Processor B');
    $this->assertSession()->responseContains('Processor C');
    $this->assertSession()->responseContains('Processor with form');
    $this->assertSame(TRUE, count($this->purgeProcessors->getPluginsEnabled()) === 2);
    $this->assertSame(TRUE, in_array('a', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertSame(TRUE, in_array('b', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertSame(FALSE, in_array('c', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertSame(FALSE, in_array('withform', $this->purgeProcessors->getPluginsEnabled()));
  }

  /**
   * Tests form submission results in the redirect command.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializeProcessorsService(['a', 'b']);
    $ajax = $this->postAjaxForm(['id' => 'c'], 'Add');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->purgeProcessors->reload();
    $this->assertSame(TRUE, in_array('c', $this->purgeProcessors->getPluginsEnabled()));
  }

  /**
   * Tests that the cancel button is present.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertActionExists('edit-cancel', 'Cancel');
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $ajax = $this->postAjaxForm([], 'Cancel');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

}
