<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgeConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\purge\DiagnosticCheck\ServiceInterface as DiagnosticsInterface;
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

/**
 * Configure the Purge pipeline for this site.
 */
class PurgeConfigForm extends ConfigFormBase {

  /**
   * Diagnostics service that reports any preliminary issues regarding purge.
   *
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeConfigForm object.
   *
   * @param \Drupal\purge\DiagnosticCheck\ServiceInterface $purge_diagnostics
   *   Diagnostics service that reports any preliminary issues regarding purge.
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purgers
   *   The purger service.
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The purge queue service.
   */
  public function __construct(DiagnosticsInterface $purge_diagnostics, PurgerServiceInterface $purge_purgers, QueueServiceInterface $purge_queue) {
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueue = $purge_queue;
    parent::__construct($this->configFactory());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.diagnostics'),
      $container->get('purge.purgers'),
      $container->get('purge.queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge.plugins'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#type' => 'item',
      '#markup' => 'Changes made to content & configuration triggers cache tag invalidations, which cause invalidation instructions.',
    ];

    $this->buildFormDiagnosticReport($form, $form_state);
    $this->buildFormQueue($form, $form_state);
    $this->buildFormPurgers($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Add a visual report on the current state of the purge module.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The elements inside the queue fieldset.
   */
  protected function buildFormDiagnosticReport(array &$form, FormStateInterface $form_state) {
    $form['diagnostics'] = [
      '#open' => $this->purgeDiagnostics->isSystemShowingSmoke() || $this->purgeDiagnostics->isSystemOnFire(),
      '#type' => 'details',
      '#title' => t('Status'),
    ];
    $form['diagnostics']['report'] = [
      '#theme' => 'status_report',
      '#requirements' => $this->purgeDiagnostics->getRequirementsArray()
    ];
  }

  /**
   * Add configuration elements for selecting the queue backend.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function buildFormQueue(array &$form, FormStateInterface $form_state) {
    $form['queue'] = [
      '#description' => '<p>' . $this->t('Purge instructions are stored in a queue.') . '</p>',
      '#type' => 'details',
      '#title' => t('Queue'),
      '#open' => TRUE,
    ];
    $form['queue']['queue_plugin'] = [
      '#type' => 'tableselect',
      '#default_value' => $this->config('purge.plugins')->get('queue'),
      '#responsive' => TRUE,
      '#multiple' => FALSE,
      '#options' => [],
      '#header' => [
        'label' => $this->t('Queue'),
        'description' => [
          'data' => $this->t('Description'),
          'class' => array('description', 'priority-low'),
        ],
      ],
    ];
    foreach ($this->purgeQueue->getPlugins() as $plugin_id => $definition) {
      $form['queue']['queue_plugin']['#options'][$plugin_id] = [
        'label' => $definition['label'],
        'description' => $definition['description'],
      ];
    }
  }

  /**
   * Add configuration elements for configuring the enabled purgers.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function buildFormPurgers(array &$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['purger'] = [
      '#description' => '<p>' . $this->t('Purgers invalidate external caches.<p/>') . '</p>',
      '#type' => 'details',
      '#title' => $this->t('Purger'),
      '#open' => TRUE,
    ];

    // Define the base table that we are going to build and return.
    $form['purger']['purger_plugins'] = [
      '#empty' => $this->t("You do not have any third-party modules installed that provide purgers. Please install the appropriate module that provides a purger for your external cache system."),
      '#type' => 'tableselect',
      '#default_value' => [],
      '#responsive' => TRUE,
      '#multiple' => TRUE,
      '#options' => [],
      '#header' => [
        'label' => $this->t('Purger'),
        'description' => [
          'data' => $this->t('Description'),
          'class' => array('description', 'priority-low'),
        ],
        'operations' => $this->t('Operations')
      ],
    ];

    // Check the purgers that are already enabled.
    foreach($this->purgePurgers->getPluginsEnabled() as $plugin_id) {
      $form['purger']['purger_plugins']['#default_value'][$plugin_id] = TRUE;
    }

    // LAMBDA: Build a configuration link given the plugin definition.
    $link = function($definition) {
      if (isset($definition['configform'])) {
        return [
          'configure' => [
            'title' => $this->t("Configure"),
            'url' => Url::fromRoute(
              'purge_ui.purger_form',
              ['purger' => $definition['id']],
              ['query' => ['dialog' => '1']]
            ),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-accepts' => 'application/vnd.drupal-modal',
              'data-dialog-options' => Json::encode(['width' => '70%']),
            ],
          ]
        ];
      }
      return [];
    };

    // Define a row for each purger and add the other columns.
    foreach ($this->purgePurgers->getPlugins() as $plugin_id => $definition) {
      $form['purger']['purger_plugins']['#options'][$plugin_id] = [
        'label' => $definition['label'],
        'description' => $definition['description'],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $link($definition),
          ]
        ]
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormQueue($form, $form_state);
    $this->validateFormPurgers($form, $form_state);
    parent::validateForm($form, $form_state);
  }

  /**
   * Validate the queue form values.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function validateFormQueue(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('queue_plugin')) {
      $form_state->setError($form['queue']['queue_plugin'], $this->t('Value missing.'));
    }
    $plugins = array_keys($this->purgeQueue->getPlugins());
    if (!in_array($form_state->getValue('queue_plugin'), $plugins)) {
      $form_state->setError($form['queue']['queue_plugin'], $this->t('Invalid input.'));
    }
  }

  /**
   * Validate the purgers form values.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function validateFormPurgers(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('purger_plugins')) {
      $form_state->setError($form['purger']['purger_plugins'], $this->t('Value missing.'));
    }
    $plugins = array_keys($this->purgePurgers->getPlugins());
    foreach ($form_state->getValue('purger_plugins') as $plugin_id => $checked) {
      if (!in_array($plugin_id, $plugins)) {
        $form_state->setError($form['purger']['purger_plugins'], $this->t('Invalid input.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitFormQueue($form, $form_state);
    $this->submitFormPurgers($form, $form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * Store the queue form submission values into configuration.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function submitFormQueue(array &$form, FormStateInterface $form_state) {
    $this->config('purge.plugins')
      ->set('queue', $form_state->getValue('queue_plugin'))
      ->save();
  }

  /**
   * Store the purgers form submission values into configuration.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function submitFormPurgers(array &$form, FormStateInterface $form_state) {
    $purgers = [];
    foreach ($form_state->getValue('purger_plugins') as $plugin_id => $checked) {
      if ($checked) {
        $purgers[] = $plugin_id;
      }
    }
    $this->config('purge.plugins')
      ->set('purgers', $purgers)
      ->save();
  }
}
