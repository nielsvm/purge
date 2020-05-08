<?php

namespace Drupal\Tests\purge_ui\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\purge_ui\Form\PluginConfigFormBase;
use Drupal\Tests\purge\Functional\BrowserTestBase;

/**
 * Testbase for \Drupal\purge_ui\Form\PluginConfigFormBase derivatives.
 */
abstract class PluginConfigFormTestBase extends BrowserTestBase {

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_ui'];

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = '';

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog = '';

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = '';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = '';

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
   * Assert that the title is present.
   */
  protected function assertFormTitle(): void {
    throw new \Exception("Derivatives need to implement ::assertFormTitle().");
  }

  /**
   * Initialize the plugin instance required to render the form.
   */
  protected function initializePlugin(): void {
    throw new \Exception("Derivatives need to implement ::initializePlugin().");
  }

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);

    // Initialize the plugin, form arguments and the form builder.
    $this->formArgs['id'] = $this->formArgsDialog['id'] = $this->getId();
    $this->formBuilder = $this->container->get('form_builder');
    $this->initializePlugin();

    // Instantiate the routes.
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route, ['id' => $this->getId()]);
      $this->route->setAbsolute(FALSE);
    }
    if (is_string($this->routeDialog)) {
      $this->routeDialog = Url::fromRoute($this->routeDialog, ['id' => $this->getId()]);
      $this->routeDialog->setAbsolute(FALSE);
    }
  }

  /**
   * Return a new instance of the form being tested.
   *
   * @return \Drupal\purge_ui\Form\PluginConfigFormBase
   *   The form instance.
   */
  protected function getFormInstance(): PluginConfigFormBase {
    $class = $this->formClass;
    return $class::create($this->container);
  }

  /**
   * Retrieve a new formstate instance.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state instance.
   */
  protected function getFormStateInstance(): FormState {
    return new FormState();
  }

  /**
   * Return the ID argument given to the form.
   */
  protected function getId(): string {
    return $this->plugin;
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormArray(): void {
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
  public function testFormAccess(): void {
    $this->drupalGet($this->route);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFormTitle();
    $this->assertSession()->fieldNotExists('edit-cancel');
    $this->assertSession()->fieldExists('edit-submit');
    $this->assertSession()->responseContains('Save configuration');
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormAccessDialog(): void {
    $this->drupalGet($this->routeDialog);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->routeDialog);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFormTitle();
    $this->assertSession()->fieldExists('edit-cancel');
    $this->assertSession()->responseContains('Cancel');
    $this->assertSession()->fieldExists('edit-submit');
    $this->assertSession()->responseContains('Save configuration');
  }

}
