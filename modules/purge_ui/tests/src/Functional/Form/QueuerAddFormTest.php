<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\QueuerAddForm;

/**
 * Tests \Drupal\purge_ui\Form\QueuerAddForm.
 *
 * @group purge
 */
class QueuerAddFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queuer_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerAddForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queuer_add_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Which queuer would you like to add?';

  /**
   * Tests that the form route is only accessible under the right conditions.
   */
  public function testRouteConditionalAccess(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeQueuersService(['a', 'b', 'c']);
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->initializeQueuersService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_queuer',
      ]
    );
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the right queuers are listed on the form.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->initializeQueuersService(['a', 'b']);
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Queuer A');
    $this->assertSession()->responseNotContains('Queuer B');
    $this->assertSession()->responseContains('Queuer C');
    $this->assertSession()->responseContains('Queuer with form');
    $this->assertSame(TRUE, count($this->purgeQueuers->getPluginsEnabled()) === 2);
    $this->assertSame(TRUE, in_array('a', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertSame(TRUE, in_array('b', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertSame(FALSE, in_array('c', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertSame(FALSE, in_array('withform', $this->purgeQueuers->getPluginsEnabled()));
  }

  /**
   * Tests that the cancel button is present.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
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
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $ajax = $this->postAjaxForm([], 'Cancel');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

  /**
   * Tests form submission results in the redirect command.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializeQueuersService(['a', 'b']);
    $ajax = $this->postAjaxForm(['id' => 'c'], 'Add');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->purgeQueuers->reload();
    $this->assertSame(TRUE, in_array('c', $this->purgeQueuers->getPluginsEnabled()));
  }

}
