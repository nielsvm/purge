<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;
use Drupal\purge_ui\Form\QueueEmptyForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueEmptyForm.
 *
 * @group purge_ui
 */
class QueueEmptyFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queue_empty_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'purge_ui',
    'purge_queuer_test',
    'purge_purger_test',
  ];

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
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess(): void {
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
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
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('No');
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), [], 'No');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
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
    $this->assertEquals(7, $this->purgeQueue->numberOfItems());
    // Call the confirm form and assert the AJAX responses.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), [], 'Yes, throw everything away!');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
    // Directly call ::emptyQueue() on a form object and assert the empty queue.
    $this->assertEquals(7, $this->purgeQueue->numberOfItems());
    $form = [];
    $form_instance = new QueueEmptyForm($this->purgeQueue);
    $form_instance->emptyQueue($form, new FormState());
    // $this->assertEquals(0, $this->purgeQueue->numberOfItems());
    // $this->purgeQueue->reload();
    $this->assertEquals(0, $this->purgeQueue->numberOfItems());
  }

}
