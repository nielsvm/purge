<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Tests the drop-in configuration form for queuers (modal dialog).
 *
 * @group purge_ui
 * @see \Drupal\purge_ui\Controller\DashboardController
 * @see \Drupal\purge_ui\Controller\QueuerFormController
 * @see \Drupal\purge_ui\Form\QueuerConfigFormBase
 */
class QueuerConfigFormTest extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Name of the queuer plugin that does have a form configured.
   *
   * @var string
   */
  protected $queuer = 'withform';

  /**
   * The route to a queuers configuration form (takes argument 'id').
   *
   * @var string
   */
  protected $route = 'purge_ui.queuer_config_form';

  /**
   * The route to a queuers configuration form (takes argument 'id') - dialog.
   *
   * @var string
   */
  protected $routeDialog = 'purge_ui.queuer_config_dialog_form';

  /**
   * The URL object constructed from $this->route.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValid = NULL;

  /**
   * The URL object constructed from $this->routeDialog.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValidDialog = NULL;

  /**
   * The URL object constructed from $this->route - invalid argument.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlInvalid = NULL;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_queuer_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService(['c', $this->queuer]);
    $this->urlValid = Url::fromRoute($this->route, ['id' => $this->queuer]);
    $this->urlValidDialog = Url::fromRoute($this->routeDialog, ['id' => $this->queuer]);
    $this->urlInvalid = Url::fromRoute($this->route, ['id' => 'c']);
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the controller and form responses.
   */
  public function testForm(): void {
    $this->drupalGet($this->urlValid);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->urlInvalid);
    $this->assertSession()->statusCodeEquals(404);
    // Test the plain version of the form.
    $this->drupalGet($this->urlValid);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Save configuration');
    $this->assertSession()->responseNotContains('Cancel');
    $this->assertSession()->fieldExists('textfield');
    // Test the modal dialog version of the form.
    $this->drupalGet($this->urlValidDialog);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Save configuration');
    $this->assertSession()->responseContains('Cancel');
    $this->assertSession()->fieldExists('textfield');
    // Test the AJAX response of the modal dialog version.
    $json = $this->drupalPostForm($this->urlValidDialog->toString(), [], 'Cancel');
    $this->assertEquals('closeDialog', $json[1]['command']);
    $this->assertEquals(2, count($json));
  }

}
