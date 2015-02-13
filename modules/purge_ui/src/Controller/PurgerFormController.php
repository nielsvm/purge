<?php
/**
 * @file
 * Contains \Drupal\purge_ui\Controller\PurgerFormController.
 */

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
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
   * @todo
   * @return string
   *   The page title.
   */
  public function getTitle() {
    return "TITLE TODO";
    // return Xss::filter($menu->label());
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
    if ($this->pluginManager->hasDefinition($purger)) {
      $definition = $this->pluginManager->getDefinition($purger);
      if (isset($definition['configform'])) {
        return $this->formBuilder()->getForm($definition['configform']);
      }
    }
    throw new AccessDeniedHttpException();
  }

}
