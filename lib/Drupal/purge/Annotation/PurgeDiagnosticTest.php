<?php

/**
 * @file
 * Contains \Drupal\purge\Annotation\PurgeDiagnosticTest.
 */

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgeDiagnosticTest annotation object.
 *
 * @Annotation
 */
class PurgeDiagnosticTest extends Plugin {

  /**
   * The plugin ID of the diagnostic test plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the diagnostic test plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the diagnostic test plugin.
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
   * @var array
   */
  public $service_dependencies;

}