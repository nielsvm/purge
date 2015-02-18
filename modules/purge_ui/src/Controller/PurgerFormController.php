<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Controller\PurgerFormController.
 */

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Purger\PluginManager;

/**
 * Return the purger configuration form for the given purger plugin_id.
 */
class PurgerFormController extends ControllerBase {

  /**
   * The plugin manager for purgers ('plugin.manager.purge.purger').
   *
   * @var \Drupal\purge\Purger\PluginManager.
   */
  protected $pluginManager;

  /**
   * Instantiate PurgerFormController.
   *
   * @param \Drupal\purge\Purger\PluginManager $pluginManager
   *   The plugin manager for purgers.
   */
  function __construct(PluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.purge.purger'));
  }

  /**
   * Route title callback.
   *
   * @param string $purger
   *   The plugin_id of the purger plugin to render its configuration form for.
   * @return array|false
   *   The definition or false when it didn't pass validation.
   */
  protected function getDefinition($purger) {
    if ($this->pluginManager->hasDefinition($purger)) {
      $definition = $this->pluginManager->getDefinition($purger);
      if (isset($definition['configform'])) {
        return $definition;
      }
    }
    return FALSE;
  }

  /**
   * Route title callback.
   *
   * @param string $purger
   *   The plugin_id of the purger plugin to render its configuration form for.
   * @return string
   *   The page title.
   */
  public function getTitle($purger) {
    if ($definition = $this->getDefinition($purger)) {
      return $this->t('Configure @label', ['@label' => $definition['label']]);
    }
    return $this->t('Configure');
  }

  /**
   * Render the correct purger configuration form.
   *
   * @param string $purger
   *   The plugin_id of the purger plugin to render its configuration form for.
   *
   * @return array
   *   The render array.
   */
  public function getForm($purger) {
    if ($definition = $this->getDefinition($purger)) {
      return $this->formBuilder()->getForm($definition['configform']);
    }
    throw new NotFoundHttpException();
  }

}
