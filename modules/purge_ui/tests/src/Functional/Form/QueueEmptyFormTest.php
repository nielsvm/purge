<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_ui\Form\QueueEmptyForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueEmptyForm.
 *
 * @group purge
 */
class QueueEmptyFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'purge_ui',
    'purge_queuer_test',
    'purge_purger_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueueEmptyForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queue_empty_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Are you sure you want to empty the queue?';

  /**
   * The queuer plugin.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService();
    $this->queuer = $this->purgeQueuers->get('a');
  }

  /**
   * Tests basic expectations of the form.
   */
  public function testPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->titleEquals("Are you sure you want to empty the queue? | Drupal");
    $this->assertSession()->pageTextContains("This action cannot be undone.");
    $this->assertSession()->pageTextContains('Yes, throw everything away!');
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('No');
    $ajax = $this->postAjaxForm([], 'No');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
  }

  /**
   * Tests that the confirm button clears the queue.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testConfirm(): void {
    // Add seven objects to the queue and assert that these get deleted.
    $this->initializeQueueService('file');
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(7, 'tag', 'test'));
    // Assert that - after reloading/committing the queue - we still have these.
    $this->purgeQueue->reload();
    $this->assertSame(7, $this->purgeQueue->numberOfItems());
    // Call the confirm form and assert the AJAX responses.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $ajax = $this->postAjaxForm([], 'Yes, throw everything away!');
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 1);
    $this->assertSame(0, $this->purgeQueue->numberOfItems());
  }

}
