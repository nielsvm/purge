<?php

/**
 * @file
 * Contains \Drupal\purge\Purger\PluginInterface.
 */

namespace Drupal\purge\Purger;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Plugin\Purge\Purger\Capacity\TrackerPurgerInterface;
use Drupal\purge\Purger\SharedInterface;

/**
 * Describes a purger - the cache invalidation executor.
 */
interface PluginInterface extends ContainerFactoryPluginInterface, SharedInterface, TrackerPurgerInterface {

  /**
   * Retrieve the unique instance ID for this purger instance.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. Plugins with 'multi_instance' set
   * to TRUE in their annotations, are likely to require the use of this method
   * to differentiate their purger instance (e.g. through configuration).
   *
   * @see \Drupal\purge\Purger\ServiceInterface::createId()
   *
   * @return string
   *   The unique identifier for this purger instance.
   */
  public function getId();

  /**
   * Retrieve the user-readable label for this purger instance.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$label
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getLabel();

  /**
   * The current instance of this purger plugin is about to be deleted.
   *
   * When end-users decide to uninstall this purger through the user interface,
   * this method gets called. Especially when this purger is multi-instantiable
   * this gets useful as it allows to remove configuration and perform cleanup
   * prior to when the instance gets uninstalled.
   *
   * @see \Drupal\purge\Purger\ServiceInterface::deletePluginsEnabled()
   *
   * @return void
   */
  public function delete();

}
