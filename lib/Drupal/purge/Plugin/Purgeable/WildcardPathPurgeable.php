<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\WildcardPathPurgeable.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;
use Drupal\purge\Plugin\Purgeable\PathPurgeable;

/**
 * Describes a path based cache wipe with wildcard, e.g. "news/*".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "WildcardPathPurgeable",
 *   label = @Translation("Wildcard Path Purgeable")
 * )
 */
class WildcardPathPurgeable extends PathPurgeable {

}
