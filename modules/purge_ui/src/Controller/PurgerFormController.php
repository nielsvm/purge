<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Controller\PurgerFormController.
 */

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Purger\ServiceInterface as PurgersService;

/**
 * Controller for forms working with purgers that are enabled, e.g.:
 *   - \Drupal\purge_ui\Form\PurgerDeleteForm
 *   - \Drupal\purge_ui\Form\PurgerConfigFormBase derivatives.
 */
class PurgerFormController extends ControllerBase {

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * Construct the PurgerFormController.
   *
   * @param \Drupal\purge\Purger\ServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  function __construct(PurgersService $purge_purgers) {
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.purgers'));
  }

  /**
   * Retrieve the plugin definition for the given instance ID.
   *
   * @param string $id
   *   Unique instance ID for the purger instance requested.
   *
   * @return array|false
   *   The definition or FALSE when it doesn't exist.
   */
  protected function getPurgerPluginDefinition($id) {
    $enabled = $this->purgePurgers->getPluginsEnabled();
    if (!isset($enabled[$id])) {
      return FALSE;
    }
    return $this->purgePurgers->getPlugins()[$enabled[$id]];
  }

  /**
   * Render the purger configuration form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   * @param bool $dialog
   *   Determines if the modal dialog variant of the form should be rendered.
   *
   * @return array
   */
  public function configForm($id, $dialog) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        return $this->formBuilder()->getForm(
          $definition['configform'],
          [
            'id' => $id,
            'dialog' => $dialog
          ]
        );
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   *   The page title.
   */
  public function configFormTitle($id) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        return $this->t('Configure @label', ['@label' => $definition['label']]);
      }
    }
    return $this->t('Configure');
  }

  /**
   * Render the purger delete form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return array
   */
  public function deleteForm($id) {
    if (!($definition = $this->getPurgerPluginDefinition($id))) {
      $definition = ['label' => ''];
    }
    return $this->formBuilder()->getForm(
      "\Drupal\purge_ui\Form\PurgerDeleteForm",
      [
        'id' => $id,
        'definition' => $definition
      ]
    );
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   *   The page title.
   */
  public function deleteFormTitle($id) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      return $this->t('Delete @label', ['@label' => $definition['label']]);
    }
    return $this->t('Delete');
  }

}
