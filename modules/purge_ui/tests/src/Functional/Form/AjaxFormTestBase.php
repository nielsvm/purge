<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Testbase for Ajax-based purge_ui forms.
 */
abstract class AjaxFormTestBase extends FormTestBase {

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   * @param string $command
   *   The name of the command to assert.
   * @param string[] $parameters
   *   Expected parameters present in the command array.
   */
  protected function assertAjaxCommand(AjaxResponse $ajax, $command, array $parameters = []): void {
    $commands = $ajax->getCommands();
    $commandsString = var_export($commands, TRUE);
    $match = array_search($command, array_column($commands, 'command'));
    $this->assertSame(
      TRUE,
      is_int($match),
      "Ajax command $command not found:\n " . $commandsString
    );
    $this->assertSame(TRUE, isset($commands[$match]));
    foreach ($parameters as $parameter => $value) {
      $this->assertSame(
        TRUE,
        isset($commands[$match][$parameter]),
        "Ajax parameter '$parameter' = '$value' not found:\n " . $commandsString
      );
      $this->assertSame(
        $commands[$match][$parameter],
        $value,
        "Ajax parameter '$parameter' = '$value' not equal:\n " . $commandsString
      );
    }
  }

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   * @param int $expected
   *   The total number of expected commands.
   */
  protected function assertAjaxCommandsTotal(AjaxResponse $ajax, int $expected): void {
    $commands = $ajax->getCommands();
    $this->assertSame($expected, count($commands), var_export($commands, TRUE));
  }

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   */
  protected function assertAjaxCommandCloseModalDialog(AjaxResponse $ajax): void {
    $this->assertAjaxCommand(
      $ajax,
      'closeDialog',
      ['selector' => '#drupal-modal']
    );
  }

  /**
   * Assert that a \Drupal\purge_ui\Form\ReloadConfigFormCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   */
  protected function assertAjaxCommandReloadConfigForm(AjaxResponse $ajax): void {
    $this->assertAjaxCommand($ajax, 'redirect');
  }

  /**
   * Assert that the given form array loaded the Ajax dialog library.
   *
   * @param array $form
   *   The form array.
   */
  protected function assertAjaxDialog(array $form): void {
    $this->assertSame(TRUE, isset($form['#attached']['library'][0]));
    $this->assertSame('core/drupal.dialog.ajax', $form['#attached']['library'][0]);
  }

  /**
   * Assert that the given form array has not loaded the Ajax dialog library.
   *
   * @param array $form
   *   The form array.
   */
  protected function assertNoAjaxDialog(array $form): void {
    $this->assertSame(FALSE, isset($form['#attached']['library'][0]));
  }

  /**
   * Assert that the given action exists.
   *
   * For some unknown reason, WebAssert::fieldExists() doesn't work on Ajax
   * modal forms, is doesn't detect form fields while they do exist in the
   * raw HTML response. This temporary assertion aids aims to solve this.
   *
   * @param string $id
   *   The id of the action field.
   * @param string $value
   *   The expected value of the action field.
   */
  protected function assertActionExists($id, $value): void {
    $this
      ->assertSession()
      ->responseContains(sprintf('type="submit" id="%s"', $id));
    $this
      ->assertSession()
      ->responseContains(sprintf('value="%s"', $value));
  }

  /**
   * Assert that the given action does not exist.
   *
   * For some unknown reason, WebAssert::fieldExists() doesn't work on Ajax
   * modal forms, is doesn't detect form fields while they do exist in the
   * raw HTML response. This temporary assertion aids aims to solve this.
   *
   * @param string $id
   *   The id of the action field.
   * @param string $value
   *   The expected value of the action field.
   */
  protected function assertActionNotExists($id, $value): void {
    $this
      ->assertSession()
      ->responseNotContains(sprintf('type="submit" id="%s"', $id));
    $this
      ->assertSession()
      ->responseNotContains(sprintf('value="%s"', $value));
  }

  /**
   * Submits a ajax form through http_kernel.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated. A checkbox can be set to TRUE
   *   to be checked and should be set to FALSE to be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   'Save'. The processing of the request depends on this value. For example,
   *   a form may have one button with the value 'Save' and another button with
   *   the value 'Delete', and execute different code depending on which one is
   *   clicked.
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The undecoded AjaxResponse object returned by the http_kernel.
   *
   * @see \Drupal\Tests\UiHelperTrait::submitForm()
   */
  protected function postAjaxForm(array $edit, $submit, array $route_parameters = []): AjaxResponse {
    $form_builder = $this->formBuilder();
    $form_state = $this->getFormStateInstance();
    $form = $this->getFormInstance();

    // Get a path appended with ?ajax_form=1&_wrapper_format=drupal_ajax.
    $this->propagateRouteParameters($route_parameters);
    $route_parameters[FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
    $route_parameters[MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $path = $this->getPath($route_parameters);

    // Instantiate a request which looks as if it is browser-initiated.
    $req = Request::create($path, 'POST');
    $req->headers->set('X-Requested-With', 'XMLHttpRequest');
    $req->headers->set('Accept', 'application/vnd.api+json');
    $edit['form_id'] = $form_builder->getFormId($form, $form_state);
    $edit['op'] = $submit;
    $req->request->add($edit);

    // Fetch the response from http_kernel and assert its sane.
    $response = $this
      ->container
      ->get('http_kernel')
      ->handle($req, HttpKernelInterface::SUB_REQUEST);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
    $this->assertInstanceOf(AjaxResponse::class, $response);
    return $response;
  }

  /**
   * Determine whether the test should return Ajax properties or not.
   *
   * @return bool
   *   Whether the test should return Ajax properties or not.
   */
  protected function shouldReturnAjaxProperties(): bool {
    return TRUE;
  }

  /**
   * Tests that forms have the Ajax dialog library loaded.
   */
  public function testAjaxDialog(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $form = $this->getBuiltForm();
    if ($this->shouldReturnAjaxProperties()) {
      $this->assertAjaxDialog($form);
    }
    else {
      $this->assertNoAjaxDialog($form);
    }
  }

}
