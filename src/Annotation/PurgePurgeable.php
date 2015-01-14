<?php

/**
 * @file
 * Contains \Drupal\purge\Annotation\PurgePurgeable.
 */

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgePurgeable annotation object.
 *
 * @Annotation
 */
class PurgePurgeable extends Plugin {

  /**
   * The plugin ID of the purgeable plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the purgeable plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the purgeable plugin.
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
