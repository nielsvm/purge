<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests the queuer details form.
 *
 * The following classes are covered:
 *   - \Drupal\purge_ui\Form\PluginDetailsForm.
 *   - \Drupal\purge_ui\Controller\QueuerFormController::detailForm().
 *   - \Drupal\purge_ui\Controller\QueuerFormController::detailFormTitle().
 *
 * @group purge_ui
 */
class QueuerDetailsFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queuer_detail_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_queuer_test', 'purge_ui'];

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
    $args = ['id' => 'a'];
    $this->initializeQueuersService(['a']);
    $this->drupalGet(Url::fromRoute($this->route, $args));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, $args));
    $this->assertSession()->statusCodeEquals(200);
    $args = ['id' => 'doesnotexist'];
    $this->drupalGet(Url::fromRoute($this->route, $args));
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\QueuerDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm(): void {
    $args = ['id' => 'a'];
    $this->initializeQueuersService(['a']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, $args));
    $this->assertSession()->responseContains('Queuer A');
    $this->assertSession()->responseContains('Test queuer A.');
    $this->assertSession()->responseContains('Close');
    $json = $this->drupalPostForm(Url::fromRoute($this->route, $args)->toString(), [], 'Close');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

}
