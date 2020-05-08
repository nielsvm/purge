<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests the queue details form.
 *
 * The following classes are covered:
 *   - \Drupal\purge_ui\Form\PluginDetailsForm.
 *   - \Drupal\purge_ui\Controller\QueueFormController::detailForm().
 *   - \Drupal\purge_ui\Controller\QueueFormController::detailFormTitle().
 *
 * @group purge_ui
 */
class QueueDetailsFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queue_detail_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_queue_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess(): void {
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertSession()->responseContains('Memory');
    $this->assertSession()->responseContains('A non-persistent, per-request memory queue (not useful on production systems).');
    $this->assertSession()->responseContains('Close');
    $json = $this->drupalPostForm(Url::fromRoute($this->route, [])->toString(), [], 'Close');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

}
