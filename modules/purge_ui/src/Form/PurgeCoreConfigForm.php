<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Form\PurgeCoreConfigForm.
 */

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Purger\PurgerServiceInterface;
use Drupal\purge\Queue\QueueServiceInterface;

/**
 * Configure settings for the Purge core APIs.
 */
class PurgeCoreConfigForm extends ConfigFormBase {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Purger\PurgerServiceInterface
   */
  protected $purgePurger;

  /**
   * @var \Drupal\purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a PurgeCoreConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purger
   *   The purger service.
   * @param \Drupal\purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PurgerServiceInterface $purge_purger, QueueServiceInterface $purge_queue) {
    $this->configFactory = $config_factory;
    $this->purgePurger = $purge_purger;
    $this->purgeQueue = $purge_queue;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('purge.purger'),
      $container->get('purge.queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.core_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['intro'] = array(
      '#type' => 'item',
      '#description' => $this->t('The purge module provides a generic (external)
        cache invalidation interface, technology agnostic.')
    );

    // Settings related to the purge.purger service.
    $plugins = $this->configFactory->get('purge.purger')->get('plugins');
    $purgers = $this->purgePurger->getPlugins(TRUE);
    $form['purger'] = array(
      '#type' => 'details',
      '#title' => $this->t('Purger'),
      '#description' => $this->t('The purger is the plugin that executes purge instructions on the external cache system.'),
      '#open' => TRUE,
    );
    if (empty($purgers)) {
      $form['purger']['purger_nothing_available'] = array(
        '#type' => 'item',
        '#description' => $this->t("You don't have any third-party modules
          installed that provide purgers. Please install the appropriate module
          that provides a purger for your external cache system.")
      );
    }
    else {
      $form['purger']['purger_detection'] = array(
        '#default_value' => ($plugins == 'automatic_detection') ? $plugins : 'manual',
        '#type' => 'radios',
        '#options' => array(
          'automatic_detection' => $this->t('Use all available purgers'),
          'manual' => $this->t('Select plugins:'),
        ),
      );
      $form['purger']['purger_plugins'] = array(
        '#default_value' => $this->purgePurger->getPluginsLoaded(),
        '#options' => $purgers,
        '#type' => 'checkboxes',
        '#description' => $this->t('When multiple purgers are enabled, each purge instruction will be sent to all plugins. If one plugin fails to execute a purge, all purgers are considered to have failed.'),
        '#states' => array(
          'disabled' => array(
            ':input[name="purger_detection"]' => array('value' => 'automatic_detection'),
          ),
        ),
      );
    }

    // Settings related to the purge.queue service.
    $form['queue'] = array(
      '#type' => 'details',
      '#title' => t('Queue'),
      '#description' => $this->t('The queue is where purge instructions are getting stored in.'),
      '#open' => TRUE,
    );
    $form['queue']['queue_plugin'] = array(
      '#default_value' => $this->configFactory->get('purge.queue')->get('plugin'),
      '#options' => $this->purgeQueue->getPlugins(TRUE),
      '#type' => 'radios',
      '#description' => $this->t('The queue service is backed by a queue plugin
        that stores the purge instructions. On most setups the database provider
        scales best up to a couple of thousand items in the queue, whereas the
        file queue plugin depends much more on the underlying IO performance
        of your server.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if (isset($form_state['values']['purger_plugins'])) {
      if ($form_state['values']['purger_detection'] == 'automatic_detection') {
        $this->configFactory->get('purge.purger')
          ->set('plugins', 'automatic_detection')
          ->save();
      }
      else {
        $purgers = array();
        foreach ($form_state['values']['purger_plugins'] as $option) {
          if (is_string($option)) {
            $purgers[] = $option;
          }
        }
        $this->configFactory->get('purge.purger')
          ->set('plugins', implode(',', $purgers))
          ->save();
      }
    }
    if (isset($form_state['values']['queue_plugin'])) {
      $this->configFactory->get('purge.queue')
        ->set('plugin', $form_state['values']['queue_plugin'])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }
}