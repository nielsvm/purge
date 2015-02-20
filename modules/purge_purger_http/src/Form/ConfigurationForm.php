<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Form\ConfigurationForm.
 */

namespace Drupal\purge_purger_http\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;

/**
 * Configuration form for the HTTP Purger.
 */
class ConfigurationForm extends PurgerConfigFormBase {

  /**
   * Static listing of all possible requests methods.
   *
   * @var array
   */
  protected $request_methods = ['BAN', 'GET', 'POST', 'HEAD', 'PUT', 'OPTIONS', 'PURGE', 'DELETE', 'TRACE', 'CONNECT'];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge_purger_http.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_purger_http.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('purge_purger_http.settings');

    $form['http_settings'] = [
      '#title' => $this->t('HTTP Settings'),
      '#description' => $this->t('Configure how custom outbound HTTP requests should
      be formed.'),
      '#type' => 'details',
      '#open' => TRUE,

    ];
    $form['http_settings']['hostname'] = [
      '#title' => $this->t('Hostname'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hostname'),
      '#required' => FALSE,
    ];
    $form['http_settings']['port'] = [
      '#title' => $this->t('Port'),
      '#type' => 'textfield',
      '#default_value' => $config->get('port'),
      '#required' => FALSE,
    ];
    /*
     * @todo We should get token support in the future.
     */
    $form['http_settings']['path'] = [
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#default_value' => $config->get('path'),
      '#required' => FALSE,
    ];
    /*
     * @todo Confirm all relevant HTTP requests are covered.
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
     */
    $form['http_settings']['request_method'] = [
      '#title' => $this->t('Request Method'),
      '#type' => 'select',
      '#default_value' => array_search($config->get('request_method'), $this->request_methods),
      '#options' => $this->request_methods,
      '#required' => FALSE,
    ];

    /*
     * @todo Implement repeatable rows with two text fields for HEADER -> VALUE
     */
    $form['headers'] = [
      '#title' => $this->t('Headers'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['ssl'] = [
      '#title' => $this->t('SSL'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('purge_purger_http.settings')
      ->set('hostname', $form_state->getValue('hostname'))
      ->set('port', $form_state->getValue('port'))
      ->set('path', $form_state->getValue('path'))
      ->set('request_method', $this->request_methods[$form_state->getValue('request_method')])
      ->save();

    return parent::submitForm($form, $form_state);
  }
}
