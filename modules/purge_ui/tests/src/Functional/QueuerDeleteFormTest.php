<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueuerDeleteForm.
 *
 * @group purge_ui
 */
class QueuerDeleteFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queuer_delete_form';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_ui', 'purge_queuer_test'];

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
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'c']));
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertSession()->responseContains('No');
    $json = $this->drupalPostForm(Url::fromRoute($this->route, ['id' => 'a'])->toString(), [], 'No');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

  /**
   * Tests that 'Yes, delete..', deletes the queuer and closes the window.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDeleteQueuer(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertSession()->responseContains('Yes, delete this queuer!');
    $json = $this->drupalPostForm(Url::fromRoute($this->route, ['id' => 'a'])->toString(), [], 'Yes, delete this queuer!');
    $this->assertEquals('redirect', $json[1]['command']);
    $this->assertEquals('closeDialog', $json[2]['command']);
    $this->assertEquals(3, count($json));
  }

}
