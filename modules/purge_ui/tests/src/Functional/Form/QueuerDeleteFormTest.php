<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\QueuerDeleteForm;

/**
 * Tests \Drupal\purge_ui\Form\QueuerDeleteForm.
 *
 * @group purge
 */
class QueuerDeleteFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queuer_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerDeleteForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queuer_delete_form';

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
  protected $routeTitle = 'Are you sure you want to delete the Queuer A queuer?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService(['a']);
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
   * Tests that 'Yes, delete..', deletes the queuer and closes the window.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDeleteQueuer(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Yes, delete this queuer!');
    $this->assertSame(['a'], $this->purgeQueuers->getPluginsEnabled());
    $ajax = $this->postAjaxForm([], 'Yes, delete this queuer!');
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->purgeQueuers->reload();
    $this->assertSame(TRUE, is_array($this->purgeQueuers->getPluginsEnabled()));
    $this->assertEmpty($this->purgeQueuers->getPluginsEnabled());
  }

}
