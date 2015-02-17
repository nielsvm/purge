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
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

/**
 * Configure settings for the Purge core APIs.
 */
class PurgeConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeConfigForm object.
   *
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purger
   *   The purger service.
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The purge queue service.
   */
  public function __construct(PurgerServiceInterface $purge_purger, QueueServiceInterface $purge_queue) {
    $this->purgePurger = $purge_purger;
    $this->purgeQueue = $purge_queue;
    parent::__construct($this->configFactory());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.purger'),
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
    $this->buildFormQueue($form, $form_state);
    $this->buildFormPurger($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Build the queue part of the form.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The elements inside the queue fieldset.
   */
  protected function buildFormQueue(array &$form, FormStateInterface $form_state) {
    $form['queue'] = [
      '#description' => '<p>' . $this->t('The queue is where purge instructions are getting stored in.') . '</p>',
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
   * Build the purger part of the form.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The elements inside the purger fieldset.
   */
  protected function buildFormPurger(array &$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['purger'] = [
      '#description' => '<p>' . $this->t('Purgers take care of invalidating external cache systems.<p/>') . '</p>',
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
    foreach($this->purgePurger->getPluginsEnabled() as $plugin_id) {
      $form['purger']['purger_plugins']['#default_value'][$plugin_id] = TRUE;
    }

    // LAMBDA: Build a configuration link given the plugin definition.
    $link = function($definition) {
      if (isset($definition['configform'])) {
        return [
          'configure' => [
            'title' => $this->t("Configure"),
            'url' => Url::fromRoute('purge_ui.purger_form', ['purger' => $definition['id']]),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-accepts' => 'application/vnd.drupal-modal',
              'data-dialog-options' => Json::encode(['width' => 700]),
            ],
          ]
        ];
      }
      return [];
    };

    // Define a row for each purger and add the other columns.
    foreach ($this->purgePurger->getPlugins() as $plugin_id => $definition) {
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
    if ($form_state->hasValue('queue_plugin')) {
      $this->config('purge.plugins')
        ->set('queue', $form_state->getValue('queue_plugin'))
        ->save();
    }
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
  protected function submitFormPurgers(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('purger_plugins')) {
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
}
