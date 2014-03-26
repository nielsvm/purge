<?php

/**
 * @file
 * Contains \Drupal\purgetest\Form\CodeTestForm.
 */

namespace Drupal\purgetest\Form;

use Drupal\Core\Form\FormBase;

/**
 * Implements an example form.
 */
class CodeTestForm extends FormBase {

  /**
   * Load the class and method that's being requested.
   */
  public function __construct() {

    // Fetch reflection information.
    $this->testClass = $this->getRequest()->attributes->get('testClass');
    $this->testMethod = $this->getRequest()->attributes->get('testMethod');

    // Reference common purge services.
    $this->purgeQueue = \Drupal::getContainer()->get('purge.queue');
    $this->purgePurgeables = \Drupal::getContainer()->get('purge.purgeables');
    //$this->purgePurger = \Drupal::getContainer()->get('purge.purger');
    //$this->purgeDiagnostics = \Drupal::getContainer()->get('purge.diagnostics');
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return __CLASS__;
  }

  /**
   * Retrieve a render array about the test.
   */
  public function getDoc() {
    $rc = new \ReflectionMethod($this->testClass, $this->testMethod);
    $doc = $rc->getDocComment();
    $doc = str_replace('/**', '', str_replace("\n   ", "\n", $doc));
    $doc = str_replace("\n* ", "\n", str_replace('*/', '', $doc));
    $doc = str_replace('@services', $this->getDocServices(), $doc);
    $doc = trim(str_replace("\n*", "\n", $doc));
    if (strlen($doc)) {
      return array('#markup' => "<pre>$doc</pre>");
    }
    else {
      return NULL;
    }
  }

  /**
   * Retrieve common scaffold code lines.
   */
  public function getDocServices() {
    $services = '';
    $services .= "\$purger = \Drupal::service('purge.purger');\n";
    $services .= "\$queue  = \Drupal::service('purge.queue');\n";
    $services .= "\$purgeables = \Drupal::service('purge.purgeables');\n";
    $services .= "\$diagnostics = \Drupal::service('purge.diagnostics');\n";
    return $services;
  }

  /**
   * Execute the function and render the response.
   */
  public function getExecuted() {
    $method = $this->testMethod;
    $object = new $this->testClass();
    $args = array(
      NULL,
      $this->purgeQueue,
      $this->purgePurgeables,
      NULL
    );
    $result = call_user_func_array(array($object, $method), $args);
    return array('#markup' => "<pre>" . var_export($result, TRUE) . "</pre>");
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, array &$form_state) {
    $form['doc'] = $this->getDoc();

    // Execute the code if requested to.
    if ($this->getRequest()->get('run')) {
      $form['result'] = $this->getExecuted();
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run!'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect'] = array(
      $this->getRequest()->get('q'),
      array(
        'query' => array(
          'run' => 'yes',
        ),
      ),
    );
  }
}