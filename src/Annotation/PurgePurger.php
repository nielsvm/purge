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
   * An ordered array of service definitions that this plugin requires, which
   * will be passed to the constructor of the plugin upon instantiation.
   *
   * @code
   * service_dependencies = {"database", "lock", "language_manager"}
   * @endcode
   *
   * @var array
   */
  public $service_dependencies = [];

}
