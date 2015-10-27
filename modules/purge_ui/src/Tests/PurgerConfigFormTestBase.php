<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgerConfigFormTestBase.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Testbase for \Drupal\purge_ui\Form\PurgerConfigFormBase derivatives.
 */
abstract class PurgerConfigFormTestBase extends WebTestBase {

  /**
   * User account with suitable permission to access the form.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui'];

  /**
   * The route to a purgers configuration form (takes argument 'purger').
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_config_form';

  /**
   * The route to a purgers configuration form (takes argument 'purger').
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = 'purge_ui.purger_config_dialog_form';

  /**
   * The plugin ID of the purger this form is for, set by derivative tests.
   *
   * @var string
   */
  protected $purger;

  /**
   * The fake instance ID of the purger about to be tested.
   *
   * @var string
   */
  protected $purgerId = 'id123';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass;

  /**
   * Form arguments.
   *
   * @var array
   */
  protected $formArgs = ['id' => NULL, 'dialog' => FALSE];

  /**
   * Form arguments.
   *
   * @var array
   */
  protected $formArgsDialog = ['id' => NULL, 'dialog' => TRUE];

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);

    // Initialize the purger instance, form arguments and the form builder.
    $this->formArgs['id'] = $this->formArgsDialog['id'] = $this->purgerId;
    $this->initializePurgersService([$this->purgerId => $this->purger]);
    $this->formBuilder = $this->container->get('form_builder');

    // Instantiate the routes.
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route, ['id' => $this->purgerId]);
      $this->route->setAbsolute(FALSE);
    }
    if (is_string($this->routeDialog)) {
      $this->routeDialog = Url::fromRoute($this->routeDialog, ['id' => $this->purgerId]);
      $this->routeDialog->setAbsolute(FALSE);
    }
  }

  /**
   * Return a new instance of the form being tested.
   *
   * @return \Drupal\purge_ui\Form\PurgerConfigFormBase
   *   \Drupal\purge_ui\Form\PurgerConfigFormBase derivative form instance.
   */
  protected function getFormInstance() {
    $class = $this->formClass;
    return $class::create($this->container);
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormArray() {
    // Test the form - without dialog switch - on basic shared characteristics.
    $form = $this->formBuilder->getForm($this->formClass, $this->formArgs);
    $this->assertFalse(isset($form['#attached']['library'][0]));
    $this->assertFalse(isset($form['#prefix']));
    $this->assertFalse(isset($form['#suffix']));
    $this->assertFalse(isset($form['actions']['submit']['#ajax']['callback']));
    // Test the dialog version, which should have all of these fields.
    $form = $this->formBuilder->getForm($this->formClass, $this->formArgsDialog);
    $this->assertTrue(isset($form['#attached']['library'][0]));
    $this->assertTrue(isset($form['#prefix']));
    $this->assertTrue(isset($form['#suffix']));
    $this->assertTrue(isset($form['actions']['submit']['#ajax']['callback']));
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
    $label = $this->purgePurgers->getLabels()[$this->purgerId];
    $this->assertTitle("Configure $label | Drupal");
    $this->assertNoField('edit-cancel');
    $this->assertField('edit-submit');
    $this->assertRaw('Save configuration');
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormAccessDialog() {
    $this->drupalGet($this->routeDialog);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->routeDialog);
    $this->assertResponse(200);
    $label = $this->purgePurgers->getLabels()[$this->purgerId];
    $this->assertTitle("Configure $label | Drupal");
    $this->assertField('edit-cancel');
    $this->assertRaw('Cancel');
    $this->assertField('edit-submit');
    $this->assertRaw('Save configuration');
  }

}
