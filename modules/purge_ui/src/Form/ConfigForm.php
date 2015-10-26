<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\ConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * Configure the Purge pipeline for this site.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Diagnostics service that reports any preliminary issues regarding purge.
   *
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * The service that generates invalidation objects on-demand.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Constructs a PurgeConfigForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purge_diagnostics
   *   Diagnostics service that reports any preliminary issues regarding purge.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors registry service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purger service.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers registry service.
   */
  public function __construct(DiagnosticsServiceInterface $purge_diagnostics, InvalidationsServiceInterface $purge_invalidation_factory, ProcessorsServiceInterface $purge_processors, PurgersServiceInterface $purge_purgers, QueueServiceInterface $purge_queue, QueuersServiceInterface $purge_queuers) {
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeProcessors = $purge_processors;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueue = $purge_queue;
    $this->purgeQueuers = $purge_queuers;
    parent::__construct($this->configFactory());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge.diagnostics'),
      $container->get('purge.invalidation.factory'),
      $container->get('purge.processors'),
      $container->get('purge.purgers'),
      $container->get('purge.queue'),
      $container->get('purge.queuers')
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
      '#markup' => $this->t('When content on your website changes, your purge setup will take care of refreshing external caching systems and CDNs.'),
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
        'data-dialog-type' => 'modal',
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
      '#open' => $this->getRequest()->get('edit-queuers', FALSE) || (!count($enabled)),
    ];
    if (count($enabled)) {
      $form['queuers']['table'] = [
        '#type' => 'table',
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
            'id' => ['data' => ['#markup' => SafeMarkup::checkPlain($id)]],
            'description' => ['data' => ['#markup' => $queuer->getDescription()]],
            'operations' => ['data' => ['#type' => 'operations', '#links' => ['disable' => $this->getDialogButton($this->t("Disable"), Url::fromRoute('purge_ui.queuer_disable_form', ['id' => $id]), '40%')]]],
          ],
        ];
      }
    }
    if (count($available)) {
      $form['queuers']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add queuer"), Url::fromRoute('purge_ui.queuer_enable_form'), '40%')]
      ];
    }
    elseif (!count($enabled)) {
      $form['queuers']['#description'] = '<p><b>' . $this->t("No queuers available, install module(s) that provide them!") . '</b></p>';
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
      '#open' => $this->getRequest()->get('edit-processors', FALSE) || (!count($enabled)),
    ];
    if (count($enabled)) {
      $form['processors']['table'] = [
        '#type' => 'table',
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
            'id' => ['data' => ['#markup' => SafeMarkup::checkPlain($id)]],
            'description' => ['data' => ['#markup' => $processor->getDescription()]],
            'operations' => ['data' => ['#type' => 'operations', '#links' => ['disable' => $this->getDialogButton($this->t("Disable"), Url::fromRoute('purge_ui.processor_disable_form', ['id' => $id]), '40%')]]],
          ],
        ];
      }
    }
    if (count($available)) {
      $form['processors']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add processor"), Url::fromRoute('purge_ui.processor_enable_form'), '40%')]
      ];
    }
    elseif (!count($enabled)) {
      $form['processors']['#description'] = '<p><b>' . $this->t("No processors available, install module(s) that provide them!") . '</b></p>';
    }
  }

  /**
   * Add new- and configure enabled purgers, support matrix.
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
    $enabledlabels = $this->purgePurgers->getLabels();
    $types_by_purger = $this->purgePurgers->getTypesByPurger();

    // Include the ajax library as we'll need it.
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['purgers'] = [
      '#description' => '<p>' . $this->t('Purgers are provided by third-party modules and clear content from external caching systems.') . '</p>',
      '#type' => 'details',
      '#title' => $this->t('Purgers'),
      '#open' => (!count($enabled) || $this->getRequest()->get('edit-purgers', FALSE)),
    ];

    // If purgers have been enabled, we build up a type-purgers matrix table.
    if (count($enabled)) {
      $add_delete_link = function(&$links, $id, $definition) {
        $links['delete'] = $this->getDialogButton($this->t("Remove"), Url::fromRoute('purge_ui.purger_delete_form', ['id' => $id]), '40%');
      };
      $add_configure_link = function(&$links, $id, $definition) {
        if (isset($definition['configform']) && !empty($definition['configform'])) {
          $url = Url::fromRoute('purge_ui.purger_config_dialog_form', ['id' => $id]);
          $links['configure'] = $this->getDialogButton($this->t("Configure"), $url);
        }
      };
      $form['purgers']['table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#header' => [
          'type' => [
            'data' => $this->t('Type'),
            'title' => $this->t('The type of external cache invalidation.')],
          ],
      ];

      // Build the table header.
      $cols = 0;
      foreach($types_by_purger as $id => $types) {
        $cols++;
        $form['purgers']['table']['#header'][$id] = [
          'data' => $enabledlabels[$id],
          'title' => $all[$enabled[$id]]['description'],
          'class' => [$cols < 3 ? RESPONSIVE_PRIORITY_MEDIUM : RESPONSIVE_PRIORITY_LOW],
        ];
      }
      if (count($available)) {
        $form['purgers']['table']['#header']['add'] = ['data' => '',];
      }

      // Register the columns for the (last) operations row.
      $operationsrow_cols = ['type' => ['data' => '']];

      // Iterate the invalidation types and add checkmarks for supported types.
      foreach ($this->purgeInvalidationFactory->getPlugins() as $type) {
        $typeid = $type['id'];
        $form['purgers']['table']['#rows'][$typeid] = [
          'id' => $typeid,
          'data' => [
            'type' => [
              'title' => $type['description'],
              'data' => [
                '#markup' => $type['label'],
              ]
            ],
          ],
        ];
        foreach ($form['purgers']['table']['#header'] as $id => $header) {
          if (in_array($id, ['type', 'add'])) {
            continue;
          }

          $form['purgers']['table']['#rows'][$typeid]['data'][$id] = [
            'data' => ['#markup' => '&nbsp;']
          ];
          if (in_array($typeid, $types_by_purger[$id])) {
            $form['purgers']['table']['#rows'][$typeid]['data'][$id]['data'] = [
              '#theme' => 'image',
              '#width' => 18,
              '#height' => 18,
              '#uri' => 'core/misc/icons/73b355/check.svg',
              '#alt' => $this->t("Supported"),
              '#title' => $this->t("Supported"),
            ];
          }
          $operationsrow_cols[$id] = [
            'data' => [
              '#type' => 'operations',
              '#links' => []
            ]
          ];
          $add_configure_link($operationsrow_cols[$id]['data']['#links'], $id, $all[$enabled[$id]]);
          $add_delete_link($operationsrow_cols[$id]['data']['#links'], $id,  $all[$enabled[$id]]);
        }

        // Add the last spacer column when it exists.
        if (isset($form['purgers']['table']['#header']['add'])) {
          $form['purgers']['table']['#rows'][$typeid]['data']['add'] = [
            'data' => ['#markup' => str_repeat('&nbsp;', 30)]
          ];
        }
      }

      // Place the add-purger button or set a message.
      if (count($available)) {
        $operationsrow_cols['add'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => [$this->getDialogButton($this->t("Add purger"), Url::fromRoute('purge_ui.purger_add_form'), '40%')]
          ]
        ];
      }

      // Add the operations row to the table.
      $form['purgers']['table']['#rows']['ops'] = [
        'data' => $operationsrow_cols,
      ];
    }

    // Render add-purger button when the table is hidden.
    elseif (count($available)) {
      $form['purgers']['add'] = [
        '#type' => 'operations',
        '#links' => [$this->getDialogButton($this->t("Add purger"), Url::fromRoute('purge_ui.purger_add_form'), '40%')]
      ];
    }
    else {
      $form['purgers']['#description'] = '<p><b>' . $this->t("No purgers available, install module(s) that provide them!") . '</b></p>';
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
