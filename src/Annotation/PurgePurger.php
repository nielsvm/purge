<?php

/**
 * @file
 * Contains \Drupal\purge\Annotation\PurgePurger.
 */

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgePurger annotation object.
 *
 * @Annotation
 */
class PurgePurger extends Plugin {

  /**
   * The plugin ID of the purger plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the purger plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the purger plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Whether end users can create more then one instance of the purger plugin.
   *
   * When you set 'multi_instance = TRUE' in your plugin annotation, it
   * becomes possible for end-users to create multiple instances of your
   * purger. With \Drupal\purge\Purger\PluginInterface::getId(), you can read
   * the unique identifier of your instance to keep multiple instances apart.
   *
   * @var bool
   */
  public $multi_instance = FALSE;

  /**
   * Full class name of the configuration form of your purger. The class must
   * extend \Drupal\purge_ui\Form\PurgerConfigFormBase.
   *
   * @var string
   */
  public $configform = '';

}
