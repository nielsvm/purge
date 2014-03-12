<?php

/**
 * @file
 * Contains \Drupal\purge_fakerefbackend\Plugin\Purgeable\NodePurgeable.
 */

namespace Drupal\purge_fakerefbackend\Plugin\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;

/**
 * Wipe a node by its path from the cache, e.g 'node/5'.
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "NodePurgeable",
 *   label = @Translation("Node Purgeable")
 * )
 */
class NodePurgeable extends PurgeableBase {


}
