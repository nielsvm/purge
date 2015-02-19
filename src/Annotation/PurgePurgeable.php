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
   * Whether purgeable objects of this type require a string expression that
   * describes what needs to be purged. If put to FALSE, the fact this type is
   * instantiated is deemed enough information for purgers to execute it.
   *
   * @var bool
   */
  public $expression_required = TRUE;

  /**
   * When expression_required = TRUE, this determines whether a string
   * expression can be equal to "" (empty string). If FALSE, this purgeable type
   * can not be instantiated with empty expression strings.
   *
   * @var bool
   */
  public $expression_can_be_empty = FALSE;

}
