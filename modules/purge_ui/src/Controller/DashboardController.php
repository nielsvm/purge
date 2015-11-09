<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Controller\DashboardController.
 */

namespace Drupal\purge_ui\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;

/**
 * Configuration dashboard for configuring the cache invalidation pipeline.
 */
class DashboardController extends ControllerBase {

  /**
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Central listing of code-used aliases and the routes we open modals for.
   *
   * @var string[]
   */
  protected $routes = [
    'purger_add'        => 'purge_ui.purger_add_form',
    'purger_detail'     => 'purge_ui.purger_detail_form',
    'purger_config'     => 'purge_ui.purger_config_form',
    'purger_configd'    => 'purge_ui.purger_config_dialog_form',
    'purger_delete'     => 'purge_ui.purger_delete_form',
    'purger_up'         => 'purge_ui.purger_move_down_form',
    'purger_down'       => 'purge_ui.purger_move_up_form',
    'processor_add'     => 'purge_ui.processor_add_form',
    'processor_detail'  => 'purge_ui.processor_detail_form',
    'processor_config'  => 'purge_ui.processor_config_form',
    'processor_configd' => 'purge_ui.processor_config_dialog_form',
    'processor_delete'  => 'purge_ui.processor_delete_form',
    'queuer_add'        => 'purge_ui.queuer_add_form',
    'queuer_detail'     => 'purge_ui.queuer_detail_form',
    'queuer_config'     => 'purge_ui.queuer_config_form',
    'queuer_configd'    => 'purge_ui.queuer_config_dialog_form',
    'queuer_delete'     => 'purge_ui.queuer_delete_form',
    'queue_detail'      => 'purge_ui.queue_detail_form',
    'queue_change'      => 'purge_ui.queue_change_form',
    'queue_browser'     => 'purge_ui.queue_browser_form',
    'queue_empty'       => 'purge_ui.queue_empty_form',
  ];

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purge_diagnostics
   *   Diagnostics service that reports any preliminary issues regarding purge.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purgers service.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request from the request stack.
   */
  public function __construct(DiagnosticsServiceInterface $purge_diagnostics, InvalidationsServiceInterface $purge_invalidation_factory, ProcessorsServiceInterface $purge_processors, PurgersServiceInterface $purge_purgers, QueueServiceInterface $purge_queue, QueuersServiceInterface $purge_queuers, Request $request) {
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeProcessors = $purge_processors;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueue = $purge_queue;
    $this->purgeQueuers = $purge_queuers;
    $this->request = $request;
  }

  /**
   * Build all dashboard sections.
   *
   * @return array
   */
  public function build() {
    $build = [
      '#theme' => ['purge_ui_dashboard'],
      '#attached' => ['library' => ['purge_ui/purge_ui.dashboard']],
    ];
    $build['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('When content on your website changes, your purge setup will take care of refreshing external caching systems and CDNs.'),
    ];
    $build['diagnostics'] = $this->buildDiagnosticReport();
    $build['purgers'] = $this->buildPurgers();
    $build['processors'] = $this->buildProcessors();
    $build['queuers'] = $this->buildQueuers();
    $build['queue'] = $this->buildQueue();
    return $build;
  }

  /**
   * Add a visual report on the current state of the purge module.
   *
   * @return array
   *   The elements inside the queue fieldset.
   */
  protected function buildDiagnosticReport() {
    $build['status'] = [
      '#type' => 'fieldset',
      '#title' => t('Status'),
      '#attributes' => [],
    ];
    $build['status']['report'] = [
      '#theme' => 'status_report',
      '#requirements' => $this->purgeDiagnostics->getRequirementsArray()
    ];
    return $build;
  }

  /**
   * Visualize enabled queuers.
   *
   * @return array
   */
  protected function buildQueuers() {
    $available = $this->purgeQueuers->getPluginsAvailable();
    $build = [
      '#description' => '<p>' . $this->t('Queuers queue items in the queue upon certain events.') . '</p>',
      '#type' => 'details',
      '#title' => t('Queuers'),
      '#open' => TRUE,
    ];
    if (count($this->purgeQueuers)) {
      $add_delete_link = function(&$links, $id) {
        $links['delete'] = $this->button($this->t("Delete"), ['queuer_delete', 'id' => $id]);
      };
      $add_configure_link = function(&$links, $queuer) {
        $definition = $queuer->getPluginDefinition();
        if (isset($definition['configform']) && !empty($definition['configform'])) {
          $links['configure'] = $this->button($this->t("Configure"), ['queuer_configd', 'id' => $queuer->getPluginId()]);
        }
      };
      $build['table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#header' => [
          'title' => $this->t('Queuer'),
          'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          'operations' => $this->t('Operations'),
        ],
      ];
      foreach ($this->purgeQueuers as $queuer) {
        $id = $queuer->getPluginId();
        $ops = [];
        $add_delete_link($ops, $id);
        $add_configure_link($ops, $queuer);
        $build['table']['#rows'][$id] = [
          'data' => [
            'title' => ['data' => ['#markup' => $queuer->getLabel()]],
            'description' => ['data' => ['#markup' => $queuer->getDescription()]],
            'operations' => ['data' => ['#type' => 'operations', '#links' => $ops]],
          ],
        ];
      }
    }
    if (count($available)) {
      $build['add'] = [
        '#type' => 'operations',
        '#links' => [$this->button($this->t("Add queuer"), 'queuer_add')]
      ];
    }
    elseif (!count($this->purgeQueuers)) {
      $build['#description'] = '<p><b>' . $this->t("No queuers available, install module(s) that provide them!") . '</b></p>';
    }
    return $build;
  }

  /**
   * Add configuration elements for selecting the queue backend.
   *
   * @return array
   */
  protected function buildQueue() {
    $build = [
      '#description' => '<p>' . $this->t('Purge instructions are stored in a queue.') . '</p>',
      '#type' => 'details',
      '#title' => t('Queue'),
      '#open' => TRUE,
    ];
    $build['change'] = $this->link($this->purgeQueue->getLabel(), 'queue_change', '900');
    $build['browser'] = $this->link($this->t("Inspect data"), 'queue_browser', '900');
    $build['empty'] = $this->link($this->t("Empty the queue"),'queue_empty');
    return $build;
  }

  /**
   * Configure processors.
   *
   * @return array
   */
  protected function buildProcessors() {
    $available = $this->purgeProcessors->getPluginsAvailable();
    $build = [
      '#description' => '<p>' . $this->t('Processors queue items in the queue upon certain events.') . '</p>',
      '#type' => 'details',
      '#title' => t('Processors'),
      '#open' => TRUE,
    ];
    if (count($this->purgeProcessors)) {
      $add_delete_link = function(&$links, $id) {
        $links['delete'] = $this->button($this->t("Delete"), ['processor_delete', 'id' => $id]);
      };
      $add_configure_link = function(&$links, $processor) {
        $definition = $processor->getPluginDefinition();
        if (isset($definition['configform']) && !empty($definition['configform'])) {
          $links['configure'] = $this->button($this->t("Configure"), ['processor_configd', 'id' => $processor->getPluginId()]);
        }
      };
      $build['table'] = [
        '#type' => 'table',
        '#responsive' => TRUE,
        '#header' => [
          'title' => $this->t('Processor'),
          'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
          'operations' => $this->t('Operations'),
        ],
      ];
      foreach ($this->purgeProcessors as $processor) {
        $id = $processor->getPluginId();
        $ops = [];
        $add_delete_link($ops, $id);
        $add_configure_link($ops, $processor);
        $build['table']['#rows'][$id] = [
          'data' => [
            'title' => ['data' => ['#markup' => $processor->getLabel()]],
            'description' => ['data' => ['#markup' => $processor->getDescription()]],
            'operations' => ['data' => ['#type' => 'operations', '#links' => $ops]],
          ],
        ];
      }
    }
    if (count($available)) {
      $build['add'] = [
        '#type' => 'operations',
        '#links' => [$this->button($this->t("Add processor"), 'processor_add')]
      ];
    }
    elseif (!count($this->purgeProcessors)) {
      $build['#description'] = '<p><b>' . $this->t("No processors available, install module(s) that provide them!") . '</b></p>';
    }
    return $build;
  }

  /**
   * Add new- and configure purgers, support matrix.
   *
   * @return array
   */
  protected function buildPurgers() {
    $all = $this->purgePurgers->getPlugins();
    $available = $this->purgePurgers->getPluginsAvailable();
    $enabled = $this->purgePurgers->getPluginsEnabled();
    $enabledlabels = $this->purgePurgers->getLabels();
    $types_by_purger = $this->purgePurgers->getTypesByPurger();

    // Define the main form section and the closures we use for the buttons.
    $build = [
      '#description' => '<p>' . $this->t('Purgers are provided by third-party modules and clear content from external caching systems.') . '</p>',
      '#type' => 'details',
      '#title' => $this->t('Purgers'),
      '#open' => TRUE,
    ];
    $add_delete_link = function(&$links, $id, $definition) {
      $links['delete'] = $this->button($this->t("Delete"), ['purger_delete', 'id' => $id]);
    };
    $add_configure_link = function(&$links, $id, $definition) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $links['configure'] = $this->button($this->t("Configure"), ['purger_configd', 'id' => $id]);
      }
    };

    // If purgers have been enabled, we build up a type-purgers matrix table.
    if (count($enabled)) {

      $build['table'] = [
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
        $build['table']['#header'][$id] = [
          'data' => $enabledlabels[$id],
          'title' => $all[$enabled[$id]]['description'],
          'class' => [$cols < 3 ? RESPONSIVE_PRIORITY_MEDIUM : RESPONSIVE_PRIORITY_LOW],
        ];
      }
      if (count($available)) {
        $build['table']['#header']['add'] = ['data' => '',];
      }

      // Register the columns for the (last) operations row.
      $operationsrow_cols = ['type' => ['data' => '']];

      // Iterate the invalidation types and add checkmarks for supported types.
      foreach ($this->purgeInvalidationFactory->getPlugins() as $type) {
        $typeid = $type['id'];
        $build['table']['#rows'][$typeid] = [
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
        foreach ($build['table']['#header'] as $id => $header) {
          if (in_array($id, ['type', 'add'])) {
            continue;
          }

          $build['table']['#rows'][$typeid]['data'][$id] = [
            'data' => ['#markup' => '&nbsp;']
          ];
          if (in_array($typeid, $types_by_purger[$id])) {
            $build['table']['#rows'][$typeid]['data'][$id]['data'] = [
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
        if (isset($build['table']['#header']['add'])) {
          $build['table']['#rows'][$typeid]['data']['add'] = [
            'data' => ['#markup' => str_repeat('&nbsp;', 30)]
          ];
        }
      }

      // Place the add-purger button or set a message.
      if (count($available)) {
        $operationsrow_cols['add'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => [$this->button($this->t("Add purger"), 'purger_add')]
          ]
        ];
      }

      // Add the operations row to the table.
      $build['table']['#rows']['ops'] = [
        'data' => $operationsrow_cols,
      ];
    }

    // Render add-purger button when the table is hidden.
    elseif (count($available)) {
      $build['add'] = [
        '#type' => 'operations',
        '#links' => [$this->button($this->t("Add purger"), 'purger_add')]
      ];
    }
    else {
      $build['#description'] = '<p><b>' . $this->t("No purgers available, install module(s) that provide them!") . '</b></p>';
    }
    return $build;
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
      $container->get('purge.queuers'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Helper for creating dropbutton modal links.
   *
   * @param string $title
   *   The title of the button.
   * @param string|array $route
   *   The aliased route in $this->route or when passed in as array, element 0
   *   is then the route alias and all other keys are passed on as arguments.
   * @param string $width
   *   Optional width of the dialog button to be generated.
   *
   *  @return array
   */
  protected function button($title, $route, $width = '60%') {
    if (is_array($route)) {
      $args = $route;
      $route = array_shift($args);
      $url = Url::fromRoute($this->routes[$route], $args);
    }
    else {
      $url = Url::fromRoute($this->routes[$route]);
    }
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
   * Helper for creating modal links.
   *
   * @param string $title
   *   The title of the link.
   * @param string|array $route
   *   The aliased route in $this->route or when passed in as array, element 0
   *   is then the route alias and all other keys are passed on as arguments.
   * @param string $width
   *   Optional width of the dialog button to be generated.
   *
   *  @return array
   */
  protected function link($title, $route, $width = '60%') {
    if (is_array($route)) {
      $args = $route;
      $route = array_shift($args);
      $url = Url::fromRoute($this->routes[$route], $args);
    }
    else {
      $url = Url::fromRoute($this->routes[$route]);
    }
    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => $width]),
      ],
    ];
  }

}
