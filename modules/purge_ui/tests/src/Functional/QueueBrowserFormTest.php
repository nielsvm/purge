<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests \Drupal\purge_ui\Form\QueueBrowserForm.
 *
 * @group purge_ui
 */
class QueueBrowserFormTest extends BrowserTestBase {

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
  protected $route = 'purge_ui.queue_browser_form';

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
   * Tests access to the form and empty conditions.
   */
  public function testAccess(): void {
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("Purge queue browser | Drupal");
    $this->assertSession()->pageTextContains("Your queue is empty.");
    $this->assertSession()->fieldNotExists('edit-1');
  }

  /**
   * Tests that the close button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueueBrowserForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testClose(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->pageTextContains("Close");
    $json = $this->drupalPostForm(Url::fromRoute($this->route)->toString(), [], 'Close');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

  /**
   * Tests that data is shown accordingly.
   *
   * @see \Drupal\purge_ui\Form\QueueBrowserForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testData(): void {
    $this->initializeInvalidationFactoryService();
    $this->initializePurgersService(['id' => 'good']);
    $this->initializeQueueService('file');
    // Add 30 tags to the queue and collect the strings we're adding.
    $tags = $needles = [];
    for ($i = 1; $i <= 30; $i++) {
      $needles[$i] = "node:$i";
      $tags[] = $this->purgeInvalidationFactory->get('tag', $needles[$i]);
    }
    $this->purgeQueue->add($this->queuer, $tags);
    // Assert that the pager works and returns our objects.
    $this->assertEquals(15, count($this->purgeQueue->selectPage()));
    $this->assertEquals(50, $this->purgeQueue->selectPageLimit(50));
    $this->assertEquals(30, count($this->purgeQueue->selectPage()));
    $this->purgeQueue->reload();
    // Render the interface and find the first 15 tags, the is on page 2.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->pageTextContains("Type");
    $this->assertSession()->pageTextContains("State");
    $this->assertSession()->pageTextContains("Expression");
    $this->assertSession()->pageTextContains("New");
    $this->assertSession()->fieldExists('edit-1');
    $this->assertSession()->fieldExists('edit-2');
    $this->assertSession()->fieldNotExists('edit-3');
    foreach ($needles as $i => $needle) {
      // @see \Drupal\purge_ui\Form\QueueBrowserForm::$numberOfItems.
      if ($i <= 15) {
        $this->assertSession()->responseContains($needle);
      }
      else {
        $this->assertSession()->responseNotContains($needle);
      }
    }
  }

}
