<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purgeable\FullDomainPurgeable.
 */

namespace Drupal\purge\Purgeable;

use Drupal\purge\Purgeable\PurgeableBase;

/**
 * Instructs a full domain or full cache clear, string representation: "*".
 *
 * @ingroup purge_purgeable_types
 *
 * @Plugin(
 *   id = "FullDomainPurgeable",
 *   label = @Translation("Full Domain Purgeable")
 * )
 */
class FullDomainPurgeable extends PurgeableBase {

}
