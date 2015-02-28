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
use Drupal\purge\Purger\ServiceInterface as PurgerServiceInterface;
use Drupal\purge\Queue\ServiceInterface as QueueServiceInterface;

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
      '#default_value' => current($this->purgeQueue->getPluginsEnabled()),
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
    $all = $this->purgePurgers->getPlugins();
    $available = $this->purgePurgers->getPluginsAvailable();
    $enabled = $this->purgePurgers->getPluginsEnabled();
    unset($enabled['null']);

    // Include the ajax library as we'll need it.
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['purgers'] = [
      '#description' => '<p>' . $this->t('Purgers invalidate external caches, which third-party modules provide.') . '</p>',
      '#type' => 'details',
      '#title' => $this->t('Purger'),
      '#open' => TRUE,
    ];

    // Anonymous functions to take the pain out of generating modal dialogs.
    $dialog = function($title, $url, $width = '70%') {
      return [
        'title' => $title,
        'url' => $url,
        'attributes' => [
          'class' => ['use-ajax'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(['width' => $width]),
        ],
      ];
    };
    $add_delete_link = function(&$links, $id, $definition) use ($dialog) {
      $links['delete'] = $dialog($this->t("Remove"), Url::fromRoute('purge_ui.purger_delete_form', ['id' => $id]), '40%');
    };
    $add_configure_link = function(&$links, $id, $definition) use ($dialog) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $url = Url::fromRoute('purge_ui.purger_config_dialog_form', ['id' => $id]);
        $links['configure'] = $dialog($this->t("Configure"), $url);
      }
    };

    // Define the table and add all enabled plugins.
    $form['purgers']['table'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#header' => [
        'label' => ['data' => $this->t('Purger')],
        'id' => ['data' => $this->t('Instance ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'description' => ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
        'link1' => ['style' => 'min-width: 3em;', 'data' => ' '],
        'link2' => ['style' => 'min-width: 3em;', 'data' => ' '],
      ],
    ];

    foreach($enabled as $id => $plugin_id) {
      $form['purgers']['table']['#rows'][$id] = [
        'id' => $id,
        'data' => [
          'label' => ['data' => ['#markup' => $all[$plugin_id]['label']]],
          'id' => ['data' => ['#markup' => String::checkPlain($id)]],
          'description' => ['data' => ['#markup' => $all[$plugin_id]['description']]],
          'link1' => ['data' => ['#type' => 'dropbutton', '#links' => []]],
          'link2' => ['data' => ['#type' => 'dropbutton', '#links' => []]],
        ],
      ];
      $add_configure_link($form['purgers']['table']['#rows'][$id]['data']['link1']['data']['#links'], $id, $all[$plugin_id]);
      $add_delete_link($form['purgers']['table']['#rows'][$id]['data']['link2']['data']['#links'], $id, $all[$plugin_id]);
    }

    // Add the footer of the table with empty message and "Add purger" button.
    $emptycel = ['#markup' => '&nbsp;'];
    $emptyrow = ['no_striping' => TRUE, 'data' => [['data' => $emptycel, 'colspan' => 5]]];
    if (empty($enabled)) {
      $form['purgers']['table']['#rows'][] = $emptyrow;
    }
    if (count($available)) {
      $addlink = ['#type' => 'dropbutton', '#links' => [$dialog($this->t("Add purger"), Url::fromRoute('purge_ui.purger_add_form'), '40%')]];
      if (!empty($enabled)) {
        $form['purgers']['table']['#rows'][] = $emptyrow;
      }
      $form['purgers']['table']['#rows'][] = [
        'data' => [
          'label' => ['data' => $emptycel],
          'id' => ['data' => $emptycel],
          'description' => ['data' => $emptycel],
          'link1' => ['data' => $emptycel],
          'link2' => ['data' => $addlink],
        ],
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
