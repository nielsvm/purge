<?php

/**
 * @file
 * Contains \Drupal\purge\Annotation\PurgeRuntimeTest.
 */

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgeRuntimeTest annotation object.
 *
 * @Annotation
 */
class PurgeRuntimeTest extends Plugin {

  /**
   * The plugin ID of the runtime test.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the test.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of what the test does.
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
  public $service_dependencies = array();

  /**
   * If your runtime test performs checks necessary for a specific queue plugin
   * to work, you can bind this test to the queues with this setting. If any of
   * the listed queues aren't loaded, your test won't run either.
   *
   * @code
   * dependent_queue_plugins = {"memory", "file"}
   * @endcode
   *
   * @var array
   */
  public $dependent_queue_plugins = array();

  /**
   * If your runtime test performs checks necessary for a specific purger plugin
   * to work, you can bind this test to your purger with this setting. If any of
   * the listed purger isn't enabled, your test won't run either.
   *
   * @code
   * dependent_purger_plugins = {"mypurger"}
   * @endcode
   *
   * @var array
   */
  public $dependent_purger_plugins = array();

}
