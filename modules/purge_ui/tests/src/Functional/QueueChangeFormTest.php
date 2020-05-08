<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueueChangeForm.
 *
 * @group purge_ui
 */
class QueueChangeFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queue_change_form';

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
   * Tests that the close button works and that changing queue works.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testChangeForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route, []));
    // Assert some of the page presentation.
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->responseContains('The queue engine is the underlying plugin which stores');
    $this->assertSession()->responseContains('when you change the queue, it will be emptied as well');
    $this->assertSession()->responseContains('Description');
    $this->assertSession()->responseContains('Cancel');
    $this->assertSession()->responseContains('Change');
    // Assert that 'memory' is selected queue.
    $this->assertSession()->checkboxChecked('edit-plugin-id-memory');
    // Assert that submitting a different queue changes it.
    $json = $this->drupalPostForm(Url::fromRoute($this->route, [])->toString(), ['plugin_id' => 'b'], 'Change');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals('redirect', $json[2]['command']);
    $this->assertEquals(3, count($json));
    $this->drupalPostForm(Url::fromRoute($this->route, []), ['plugin_id' => 'b'], 'Change');
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertSession()->checkboxChecked('edit-plugin-id-b');
    // // Assert that closing the dialog functions as expected.
    $json = $this->drupalPostForm(Url::fromRoute($this->route, [])->toString(), [], 'Cancel');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

}
