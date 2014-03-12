<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\PathPurgeable.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;

/**
 * Describes a path based cache wipe, e.g. "news/article-1".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "PathPurgeable",
 *   label = @Translation("Path Purgeable")
 * )
 */
class PathPurgeable extends PurgeableBase {

}
