<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\ConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\purge\DiagnosticCheck\ServiceInterface as DiagnosticsInterface;
use Drupal\purge\Processor\ServiceInterface as ProcessorsServiceInterface;
use Drupal\purge\Purger\ServiceInterface as PurgersServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;
use Drupal\purge\Queuer\ServiceInterface as QueuersServiceInterface;

/**
 * Configure the Purge pipeline for this site.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Diagnostics service that reports any preliminary issues regarding purge.
   *
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * @var \Drupal\purge\Processor\ServiceInterface
   */
  protected $purgeProcessors;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Queuer\ServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Constructs a PurgeConfigForm object.
   *
   * @param \Drupal\purge\DiagnosticCheck\ServiceInterface $purge_diagnostics
   *   Diagnostics service that reports any preliminary issues regarding purge.
   * @param \Drupal\purge\Processor\ServiceInterface $purge_processors
   *   The purge processors registry service.
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purgers
   *   The purger service.
   * @param \Drupal\purge\Queue\ServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Queuer\ServiceInterface $purge_queuers
   *   The purge queuers registry service.
   */
  public function __construct(DiagnosticsInterface $purge_diagnostics, ProcessorsServiceInterface $purge_processors, QueuersServiceInterface $purge_queuers, QueueServiceInterface $purge_queue, PurgersServiceInterface $purge_purgers) {
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->purgeQueuers = $purge_queuers;
    $this->purgeQueue = $purge_queue;
    $this->purgeProcessors = $purge_processors;
    $this->purgePurgers = $purge_purgers;
    parent::__construct($this->configFactory());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.diagnostics'),
      $container->get('purge.processors'),
      $container->get('purge.queuers'),
      $container->get('purge.queue'),
      $container->get('purge.purgers')
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
    return 'purge_ui.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('Changes made to content & configuration triggers cache tag invalidations, which cause invalidation instructions.'),
    ];

    $this->buildFormDiagnosticReport($form, $form_state);
    $this->buildFormQueuers($form, $form_state);
    $this->buildFormQueue($form, $form_state);
    $this->buildFormPurgers($form, $form_state);
    $this->buildFormProcessors($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper for dropbutton/operations modal buttons (#links).
   *
   * @param string $title
   *   The title of the button.
   * @param \Drupal\Core\Url $url
   *   The route to the modal dialog provider.
   * @param string $width
   *   Optional width of the dialog button to be generated.
   *
   *  @return array
   */
  protected function getDialogButton($title, $url, $width = '70%') {
    return [
      'title' => $title,
      'url' => $url,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-accepts' => 'application/vnd.drupal-modal',
        'data-dialog-options' => Json::encode(['width' => $width]),
      ],
    ];
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
   * Visualize the queuers that are registered and adding things to the queue.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function buildFormQueuers(array &$form, FormStateInterface $form_state) {
    $available = $this->purgeQueuers->getDisabled();
    $enabled = $this->purgeQueuers->getEnabled();
    $form['queuers'] = [
      '#description' => '<p>' . $this->t('Queuers queue items in the queue upon certain events.') . '</p>',
      '#type' => 'details',
      '#title' => t('Queuers'),
      '#open' => $this->getRequest()->get('queuers', FALSE) || (!count($enabled)),
    ];
    $form['queuers']['table'] = [
      '#type' => 'table',
      '#access' => count($enabled),
      '#responsive' => TRUE,
      '#header' => [
        'title' => $this->t('Queuer'),
        'id' => ['data' => $this->t('Container ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'operations' => $this->t('Operations'),
      ],
    ];
    foreach ($enabled as $id => $queuer) {
      $form['queuers']['table']['#rows'][$id] = [
        'id' => $id,
        'data' => [
          'title' => ['data' => ['#markup' => $queuer->getTitle()]],
          'id' => ['data' => ['#markup' => String::checkPlain($id)]],
          'description' => ['data' => ['#markup' => $queuer->getDescription()]],
          'operations' => ['data' => ['#type' => 'operations', '#links' => ['disable' => $this->getDialogButton($this->t("Disable"), Url::fromRoute('purge_ui.queuer_disable_form', ['id' => $id]), '40%')]]],
        ],
      ];
    }
    if (count($available)) {
      $form['queuers']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add queuer"), Url::fromRoute('purge_ui.queuer_enable_form'), '40%')]
      ];
    }
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
      '#open' => FALSE,
    ];
    $form['queue']['queue_plugin'] = [
      '#type' => 'tableselect',
      '#default_value' => current($this->purgeQueue->getPluginsEnabled()),
      '#responsive' => TRUE,
      '#multiple' => FALSE,
      '#options' => [],
      '#header' => [
        'label' => $this->t('Queue'),
        'description' => [
          'data' => $this->t('Description'),
          'class' => [RESPONSIVE_PRIORITY_MEDIUM],
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
   * List all enabled processors.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return void
   */
  protected function buildFormProcessors(array &$form, FormStateInterface $form_state) {
    $available = $this->purgeProcessors->getDisabled();
    $enabled = $this->purgeProcessors->getEnabled();
    $form['processors'] = [
      '#description' => '<p>' . $this->t('Processors queue items in the queue upon certain events.') . '</p>',
      '#type' => 'details',
      '#title' => t('Processors'),
      '#open' => $this->getRequest()->get('processors', FALSE) || (!count($enabled)),
    ];
    $form['processors']['table'] = [
      '#type' => 'table',
      '#access' => count($enabled),
      '#responsive' => TRUE,
      '#header' => [
        'title' => $this->t('Processor'),
        'id' => ['data' => $this->t('Container ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'operations' => $this->t('Operations'),
      ],
    ];
    foreach ($enabled as $id => $processor) {
      $form['processors']['table']['#rows'][$id] = [
        'id' => $id,
        'data' => [
          'title' => ['data' => ['#markup' => $processor->getTitle()]],
          'id' => ['data' => ['#markup' => String::checkPlain($id)]],
          'description' => ['data' => ['#markup' => $processor->getDescription()]],
          'operations' => ['data' => ['#type' => 'operations', '#links' => ['disable' => $this->getDialogButton($this->t("Disable"), Url::fromRoute('purge_ui.processor_disable_form', ['id' => $id]), '40%')]]],
        ],
      ];
    }
    if (count($available)) {
      $form['processors']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add processor"), Url::fromRoute('purge_ui.processor_enable_form'), '40%')]
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
    $all = $this->purgePurgers->getPlugins();
    $available = $this->purgePurgers->getPluginsAvailable();
    $enabled = $this->purgePurgers->getPluginsEnabled();
    unset($enabled['null']);

    // Include the ajax library as we'll need it.
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['purgers'] = [
      '#description' => '<p>' . $this->t('Purgers invalidate external caches, which third-party modules provide.') . '</p>',
      '#type' => 'details',
      '#title' => $this->t('Purgers'),
      '#open' => $this->getRequest()->get('purgers', FALSE) || (!count($enabled)),
    ];
    $add_delete_link = function(&$links, $id, $definition) {
      $links['delete'] = $this->getDialogButton($this->t("Remove"), Url::fromRoute('purge_ui.purger_delete_form', ['id' => $id]), '40%');
    };
    $add_configure_link = function(&$links, $id, $definition) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $url = Url::fromRoute('purge_ui.purger_config_dialog_form', ['id' => $id]);
        $links['configure'] = $this->getDialogButton($this->t("Configure"), $url);
      }
    };

    // Define the table and add all enabled plugins.
    $form['purgers']['table'] = [
      '#type' => 'table',
      '#access' => count($enabled),
      '#responsive' => TRUE,
      '#header' => [
        'label' => $this->t('Purger'),
        'id' => ['data' => $this->t('Instance ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
        'operations' => $this->t('Operations'),
      ],
    ];
    foreach($enabled as $id => $plugin_id) {
      $form['purgers']['table']['#rows'][$id] = [
        'id' => $id,
        'data' => [
          'label' => ['data' => ['#markup' => $all[$plugin_id]['label']]],
          'id' => ['data' => ['#markup' => String::checkPlain($id)]],
          'description' => ['data' => ['#markup' => $all[$plugin_id]['description']]],
          'operations' => ['data' => ['#type' => 'operations', '#links' => []]],
        ],
      ];
      $add_configure_link($form['purgers']['table']['#rows'][$id]['data']['operations']['data']['#links'], $id, $all[$plugin_id]);
      $add_delete_link($form['purgers']['table']['#rows'][$id]['data']['operations']['data']['#links'], $id, $all[$plugin_id]);
    }
    if (count($available)) {
      $form['purgers']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add purger"), Url::fromRoute('purge_ui.purger_add_form'), '40%')]
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormQueue($form, $form_state);
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitFormQueue($form, $form_state);
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
    $this->purgeQueue->setPluginsEnabled([$form_state->getValue('queue_plugin')]);
  }

}
