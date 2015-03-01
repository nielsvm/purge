<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgerAddFormTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerAddForm.
 *
 * @group purge
 */
class PurgerAddFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_add_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_noqueuer_test', 'purge_purger_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route);
    }
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
  }

  /**
   * Tests that clicking the add button, adds a purger and closes the screen.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd() {
    $this->initializePurgersService();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw(t('Add'));
    $this->assertEqual(['null' => 'null'], $this->purgePurgers->getPluginsEnabled());
    $json = $this->drupalPostAjaxForm($this->route, ['plugin_id' => 'purger_c'], ['op' => t('Add')]);
    $this->assertEqual('closeDialog', $json[0]['command']);
    $this->assertEqual('redirect', $json[1]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(in_array('purger_c', $this->purgePurgers->getPluginsEnabled()));
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancel() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw(t('Cancel'));
    $json = $this->drupalPostAjaxForm($this->route, [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[0]['command']);
    $this->assertEqual(1, count($json));
  }

  /**
   * Tests that the 'plugin_id' field shows no purgers when all non multi-
   * instantiable purgers are in use.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   */
  public function testNoAvailablePurgers() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute('purge_ui.config_form'));
    $this->assertRaw(t('Add purger'));
    $this->initializePurgersService(
      [
        'id1' => 'purger_a',
        'id2' => 'purger_b',
        'id3' => 'purger_c',
        'id4' => 'purger_withform',
      ]
    );
    $this->drupalGet($this->route);
    $this->assertNoFieldByName('plugin_id');
    $this->assertFieldByName('op', t('Cancel'));
    $this->assertNoFieldByName('op', t('Add'));
    $this->drupalGet(Url::fromRoute('purge_ui.config_form'));
    $this->assertNoRaw(t('Add purger'));
  }

  /**
   * Tests the 'plugin_id' form element for listing only available purgers.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   */
  public function testTwoAvailablePurgers() {
    $this->initializePurgersService(['id3' => 'purger_c', 'id4' => 'purger_withform']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertFieldByName('plugin_id');
    $this->assertText('Purger A');
    $this->assertText('Purger B');
    $this->assertNoText('Configurable purger');
    $this->assertFieldByName('op', t('Cancel'));
    $this->assertFieldByName('op', t('Add'));
  }

}
