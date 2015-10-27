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
 * @group purge_ui
 * @see \Drupal\purge_ui\Form\ConfigForm
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
  protected $purger = 'withform';

  /**
   * The route to a purgers configuration form (takes argument 'id').
   *
   * @var string
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * The route to a purgers configuration form (takes argument 'id') - dialog.
   *
   * @var string
   */
  protected $route_dialog = 'purge_ui.purger_config_dialog_form';

  /**
   * The URL object constructed from $this->route.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValid = NULL;

  /**
   * The URL object constructed from $this->route_dialog.
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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->initializePurgersService(['bad' => 'c', 'good' => $this->purger]);
    $this->urlValid = Url::fromRoute($this->route, ['id' => 'good']);
    $this->urlValidDialog = Url::fromRoute($this->route_dialog, ['id' => 'good']);
    $this->urlInvalid = Url::fromRoute($this->route, ['id' => 'bad']);
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
    $this->assertRaw(t('Save configuration'));
    $this->assertNoRaw(t('Cancel'));
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
    $this->assertRaw(t('Save configuration'));
    $this->assertRaw(t('Cancel'));
    $this->assertFieldByName('textfield');
    $json = $this->drupalPostAjaxForm($this->urlValidDialog->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

}
