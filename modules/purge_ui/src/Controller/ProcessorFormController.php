<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Controller\ProcessorFormController.
 */

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Processor\ServiceInterface as ProcessorService;

/**
 * Controller for:
 *  - \Drupal\purge_ui\Form\ProcessorDisableForm.
 *  - \Drupal\purge_ui\Form\ProcessorEnableForm.
 */
class ProcessorFormController extends ControllerBase {

  /**
   * @var \Drupal\purge\Processor\ServiceInterface
   */
  protected $purgeProcessors;

  /**
   * Construct the ProcessorFormController.
   *
   * @param \Drupal\purge\Processor\ServiceInterface $purge_processors
   *   The purge processors registry.
   */
  function __construct(ProcessorService $purge_processors) {
    $this->purgeProcessors = $purge_processors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.processors'));
  }

  /**
   * Render the processor disable form.
   *
   * @param string $id
   *   The container id of the processor to retrieve.
   *
   * @return array
   */
  public function disableForm($id) {
    if ($processor = $this->purgeProcessors->get($id)) {
      return $this->formBuilder()->getForm("\Drupal\purge_ui\Form\ProcessorDisableForm", $id);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The container id of the processor to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   *   The page title.
   */
  public function disableFormTitle($id) {
    if ($processor = $this->purgeProcessors->get($id)) {
      return $this->t('Disable @label', ['@label' => $processor->getTitle()]);
    }
    return $this->t('Disable');
  }

  /**
   * Render the processor enable form.
   *
   * @return array
   */
  public function enableForm() {
    if ($this->purgeProcessors->getDisabled()) {
      return $this->formBuilder()->getForm("Drupal\purge_ui\Form\ProcessorEnableForm");
    }
    throw new NotFoundHttpException();
  }

}
