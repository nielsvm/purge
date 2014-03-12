<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\TagPurgeable.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;

/**
 * Describes a cache wipe by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "TagPurgeable",
 *   label = @Translation("Tag Purgeable")
 * )
 */
class TagPurgeable extends PurgeableBase {

}
