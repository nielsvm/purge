<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgerConfigFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests the drop-in configuration form for purgers (modal dialog).
 *
 * @group purge
 * @see \Drupal\purge_ui\Form\PurgeConfigForm
 * @see \Drupal\purge_ui\Controller\PurgerFormController
 * @see \Drupal\purge_ui\Form\PurgerConfigFormBase
 */
class PurgerConfigFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * Name of the purger plugin that does have a form configured.
   *
   * @var string
   */
  protected $purger = 'purger_withform';

  /**
   * The route to a purgers configuration form (takes argument 'purger').
   *
   * @var string
   */
  protected $route = 'purge_ui.purger_form';

  /**
   * The URL object constructed from $this->configRoute - plain version.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValid = NULL;

  /**
   * The URL object constructed from $this->configRoute - dialog version.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValidDialog = NULL;

  /**
   * The URL object constructed from $this->configRoute -invalid argument.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlInvalid = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_noqueuer_test', 'purge_plugins_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->urlValid = Url::fromRoute($this->route, ['purger' => $this->purger]);
    $this->urlValidDialog = Url::fromRoute($this->route, ['purger' => $this->purger], ['query' => ['dialog' => '1']]);
    $this->urlInvalid = Url::fromRoute($this->route, ['purger' => 'nonexistent']);
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testFormController() {
    $this->drupalGet($this->urlValid);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->urlInvalid);
    $this->assertResponse(404);
    $this->drupalGet($this->urlValid);
    $this->assertResponse(200);
    $this->drupalGet($this->urlValidDialog);
    $this->assertResponse(200);
  }

  /**
   * Test the plain version of the form.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::isDialog
   */
  public function testValidForm() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->urlValid);
    $this->assertNoFieldById('edit-cancel');
    $this->assertFieldById('edit-submit');
    $this->assertFieldByName('textfield');
  }

  /**
   * Test the modal dialog version of the form.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::isDialog
   */
  public function testValidDialogForm() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->urlValidDialog);
    $this->assertFieldById('edit-cancel');
    $this->assertFieldById('edit-submit');
    $this->assertFieldByName('textfield');
  }

}
