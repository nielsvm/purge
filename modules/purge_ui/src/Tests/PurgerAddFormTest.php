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
 * @group purge_ui
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
    $this->assertTrue(is_array($this->purgePurgers->getPluginsEnabled()));
    $this->assertTrue(empty($this->purgePurgers->getPluginsEnabled()));
    $json = $this->drupalPostAjaxForm($this->route->toString(), ['plugin_id' => 'c'], ['op' => t('Add')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(in_array('c', $this->purgePurgers->getPluginsEnabled()));
    $this->assertEqual(3, count($json));
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
    $json = $this->drupalPostAjaxForm($this->route->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that the 'plugin_id' field shows no purgers when all non multi-
   * instantiable purgers are in use.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   */
  public function testNoAvailablePurgers() {
    $this->drupalLogin($this->admin_user);
    $this->initializePurgersService(['id1' => 'a', 'id2' => 'b', 'id3' => 'c', 'id4' => 'withform', 'id5' => 'good']);
    $this->drupalGet($this->route);
    $this->assertNoFieldByName('plugin_id');
    $this->assertFieldByName('op', t('Cancel'));
    $this->assertNoFieldByName('op', t('Add'));
  }

  /**
   * Tests the 'plugin_id' form element for listing only available purgers.
   *
   * @see \Drupal\purge_ui\Form\PurgerConfigFormBase::buildForm
   */
  public function testTwoAvailablePurgers() {
    $this->initializePurgersService(['id3' => 'c', 'id4' => 'withform']);
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
