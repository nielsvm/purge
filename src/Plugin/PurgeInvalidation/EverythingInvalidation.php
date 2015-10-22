<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeInvalidation\EverythingInvalidation.
 */

namespace Drupal\purge\Plugin\PurgeInvalidation;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;

/**
 * Describes that everything is to be invalidated.
 *
 * @PurgeInvalidation(
 *   id = "everything",
 *   label = @Translation("Everything"),
 *   description = @Translation("Invalidates everything."),
 *   expression_required = FALSE
 * )
 */
class EverythingInvalidation extends InvalidationBase implements InvalidationInterface {}
