<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Form\ConfigurationForm.
 */

namespace Drupal\purge_purger_http\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Configuration form for the HTTP Purger.
 */
class ConfigurationForm extends PurgerConfigFormBase {

  /**
   * The service that generates invalidation objects on-demand.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * Static listing of all possible requests methods.
   *
   * @todo
   *   Confirm if all relevant HTTP methods are covered.
   *   http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
   *
   * @var array
   */
  protected $request_methods = ['BAN', 'GET', 'POST', 'HEAD', 'PUT', 'OPTIONS', 'PURGE', 'DELETE', 'TRACE', 'CONNECT'];

  /**
   * Constructs a \Drupal\purge_purger_http\Form\ConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, InvalidationsServiceInterface $purge_invalidation_factory) {
    $this->setConfigFactory($config_factory);
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('purge.invalidation.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
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
    $settings = HttpPurgerSettings::load($this->getId($form_state));

    // Invalidation type.
    $types = [];
    foreach ($this->purgeInvalidationFactory->getPlugins() as $type => $definition) {
      $types[$type] = (string)$definition['label'];
    }
    $form['invalidationtype'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of invalidation'),
      '#description' => $this->t('Configure which type of cache invalidation this purger will clear.'),
      '#default_value' => $settings->invalidationtype,
      '#options' => $types,
      '#required' => FALSE,
    ];

    // HTTP Settings.
    $form['http_settings'] = [
      '#title' => $this->t('HTTP Settings'),
      '#description' => $this->t('Configure how custom outbound HTTP requests should be formed.'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['http_settings']['hostname'] = [
      '#title' => $this->t('Hostname'),
      '#type' => 'textfield',
      '#default_value' => $settings->hostname,
      '#required' => FALSE,
    ];
    $form['http_settings']['port'] = [
      '#title' => $this->t('Port'),
      '#type' => 'textfield',
      '#default_value' => $settings->port,
      '#required' => FALSE,
    ];

    // @todo We should get token support in the future.
    $form['http_settings']['path'] = [
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#default_value' => $settings->path,
      '#required' => FALSE,
    ];
    $form['http_settings']['request_method'] = [
      '#title' => $this->t('Request Method'),
      '#type' => 'select',
      '#default_value' => array_search($settings->request_method, $this->request_methods),
      '#options' => $this->request_methods,
      '#required' => FALSE,
    ];

    // Performance.
    $form['performance'] = [
      '#title' => $this->t('Performance'),
      '#description' => $this->t('Tune HTTP-based processing and ensure a good trade off between capacity and system stability.'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['performance']['timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 8,
      '#title' => $this->t('Timeout'),
      '#default_value' => $settings->timeout,
      '#required' => TRUE,
      '#description' => $this->t('Float describing the timeout of the request in seconds.')
    ];
    $form['performance']['connect_timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 4,
      '#title' => $this->t('Connection timeout'),
      '#default_value' => $settings->connect_timeout,
      '#required' => TRUE,
      '#description' => $this->t('Float describing the number of seconds to wait while trying to connect to a server.')
    ];
    $form['performance']['max_requests'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#max' => 500,
      '#title' => $this->t('Maximum requests'),
      '#default_value' => $settings->max_requests,
      '#required' => TRUE,
      '#description' => $this->t('Maximum number of HTTP requests that can be made during the runtime of one request (including CLI). The higher this number is set, the more - CLI based - scripts can process but this can also badly influence your end-user performance when using runtime-based queue processors.')
    ];

    // @todo Implement repeatable rows with two text fields for HEADER -> VALUE
    $form['headers'] = [
      '#title' => $this->t('Headers'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // @todo Implement SSL configuration options.
    $form['ssl'] = [
      '#title' => $this->t('SSL'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // @todo Implement repeatable row options to configure *when* invalidations
    // are considered successful or not, for instance a option that says "when
    // HTTP response code: 200, 404".
    $form['success_conditions'] = [
      '#title' => $this->t('Success conditions'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate that our timeouts stay between the boundaries purge demands.
    $timeout = $form_state->getValue('connect_timeout') + $form_state->getValue('timeout');
    if ($timeout > 10) {
      $form_state->setErrorByName('connect_timeout');
      $form_state->setErrorByName('timeout', $this->t('The sum of both timeouts cannot be higher than 10.00 as this would affect performance too negatively.'));
    }
    elseif ($timeout < 0.4) {
      $form_state->setErrorByName('connect_timeout');
      $form_state->setErrorByName('timeout', $this->t('The sum of both timeouts cannot be lower as 0.4 as this can lead to too many failures under real usage conditions.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    $settings = HttpPurgerSettings::load($this->getId($form_state));
    $settings->invalidationtype = $form_state->getValue('invalidationtype');
    $settings->hostname = $form_state->getValue('hostname');
    $settings->port = $form_state->getValue('port');
    $settings->path = $form_state->getValue('path');
    $settings->request_method = $this->request_methods[$form_state->getValue('request_method')];
    $settings->timeout = $form_state->getValue('timeout');
    $settings->connect_timeout = $form_state->getValue('connect_timeout');
    $settings->max_requests = $form_state->getValue('max_requests');
    $settings->save();
  }

}
