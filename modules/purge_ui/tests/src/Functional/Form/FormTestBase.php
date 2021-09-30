<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Tests\purge\Functional\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testbase for purge_ui forms.
 */
abstract class FormTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui'];

  /**
   * The Drupal user entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The form builder.
   *
   * @var null|\Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder = NULL;

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = '';

  /**
   * The form id, equal to the route name when left as NULL.
   *
   * @var null|\Drupal\Core\Form\FormBuilderInterface
   */
  protected $formId = NULL;

  /**
   * The tested form instance.
   *
   * @var null|\Drupal\Core\Form\FormBuilderInterface
   */
  protected $formInstance = NULL;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = '';

  /**
   * The default route parameters.
   *
   * @var mixed[]
   */
  protected $routeParameters = [];

  /**
   * Route parameters for requests which should yield a 404.
   *
   * @var null|mixed[]
   */
  protected $routeParametersInvalid = NULL;

  /**
   * The title of the form route.
   *
   * @var string
   */
  protected $routeTitle = 'Untitled';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->assertTestProperties();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
    if (is_null($this->formId)) {
      $this->formId = $this->route;
    }
  }

  /**
   * Assert that required class properties are set.
   */
  protected function assertTestProperties(): void {
    $this->assertNotSame('', $this->route, '$route not set!');
    $this->assertNotSame('', $this->formClass, '$formClass not set!');
  }

  /**
   * Return the same instance of a form.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form instance.
   */
  protected function formInstance(): FormInterface {
    if (is_null($this->formInstance)) {
      $this->formInstance = $this->getFormInstance();
    }
    return $this->formInstance;
  }

  /**
   * Return the form builder instance.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form instance.
   */
  protected function formBuilder(): FormBuilderInterface {
    if (is_null($this->formBuilder)) {
      $this->formBuilder = $this->container->get('form_builder');
    }
    return $this->formBuilder;
  }

  /**
   * Fetch a built form array fetched via its controller.
   *
   * This helper exists because several purge_ui forms rely route argument which
   * are hard to inject programmatically in a generic way from this
   * FormTestBase, so we're asking the controller to do it. This comes with the
   * extra benefit that we can test the integration of the controller as well.
   *
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   * @param array $options
   *   See \Drupal\Core\Url::fromUri() for details.
   *
   * @return array
   *   The form array.
   *
   * @see \Drupal\Core\Form\FormBuilderInterface::getForm()
   */
  protected function getBuiltForm(array $route_parameters = [], array $options = []): array {
    $path = $this->getPath($route_parameters, $options);
    $request = Request::create($path);
    $match = $this->container
      ->get('router')
      ->matchRequest($request);
    $controller = $this->container
      ->get('controller_resolver')
      ->getControllerFromDefinition($match['_controller']);
    $arguments = $this->container
      ->get('http_kernel.controller.argument_resolver')
      ->getArguments($request, $controller);
    return call_user_func_array($controller, $arguments);
  }

  /**
   * Return a new instance of the form being tested.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form instance.
   */
  protected function getFormInstance(): FormInterface {
    return $this->formClass::create($this->container);
  }

  /**
   * Retrieve a new formstate instance.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state instance.
   */
  protected function getFormStateInstance(): FormStateInterface {
    return new FormState();
  }

  /**
   * Get a path string for the form route.
   *
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   * @param array $options
   *   See \Drupal\Core\Url::fromUri() for details.
   *
   * @return string
   *   A new string created by Url::fromRoute()
   *
   * @see \Drupal\Core\Url::fromRoute()
   */
  protected function getPath(array $route_parameters = [], array $options = []): string {
    $this->propagateRouteParameters($route_parameters);
    return Url::fromRoute(
      $this->route,
      $route_parameters,
      $options
    )->toString();
  }

  /**
   * Add default route parameters.
   *
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   */
  protected function propagateRouteParameters(array &$route_parameters) {
    if (empty($route_parameters)) {
      $route_parameters = $this->routeParameters;
    }
  }

  /**
   * Tests that the form route isn't accessible anonymously.
   */
  public function testFormCodeContract(): void {
    $form = $this->getFormInstance();
    $this->assertInstanceOf(FormInterface::class, $form);
  }

  /**
   * Assert that basic form building succeeds without issues.
   */
  public function testFormBuilds(): void {
    $this->drupalLogin($this->adminUser);
    $form = $this->getBuiltForm();
    $this->assertSame(TRUE, isset($form['#type']));
    $this->assertSame(TRUE, isset($form['form_id']['#value']));
    $this->assertSame(TRUE, isset($form['#form_id']));
    $this->assertSame(TRUE, isset($form['#cache']['contexts'][0]));
    $this->assertSame('form', $form['#type']);
    $this->assertSame($this->formId, $form['form_id']['#value']);
    $this->assertSame($this->formId, $form['#form_id']);
    $this->assertSame(['user.roles:authenticated'], $form['#cache']['contexts']);
  }

  /**
   * Test that the form route isn't accessible anonymously or on bad routes.
   */
  public function testRouteAccess(): void {
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(403);
    // Only test for bad route input, when ::$routeParametersInvalid is set.
    if (!is_null($this->routeParametersInvalid)) {
      $path = $this->getPath($this->routeParametersInvalid);
      $this->drupalLogin($this->adminUser);
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(404);
    }
  }

  /**
   * Tests that the form route isn't accessible anonymously.
   */
  public function testRouteAccessGranted(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Assert that the title is present.
   */
  public function testRouteTitle(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSame(
      $this->routeTitle . ' | Drupal',
      $this->getSession()->getPage()->find('css', 'title')->getText()
    );
  }

}
