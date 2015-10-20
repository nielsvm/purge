<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Purger\ResourceTracking\Tracker.
 */

namespace Drupal\purge\Plugin\Purge\Purger\ResourceTracking;

use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\ResourceTracking\TrackerInterface;

/**
 * Provides the resource tracker API.
 */
class Tracker implements TrackerInterface {

  /**
   * {@inheritdoc}
   */
  public function getLimit() {throw new \Exception("NOT IMPLEMENTED");
    // * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException
    // *   Thrown when the returned value is not a integer or when it equals to 0.
    // static $limit;
    //
    // // As the limit gets cached during this request, calculate it only once.
    // if (is_null($limit)) {
    //   $purgers = count($this->purgers);
    //   $limits = [];
    //
    //   // Ask all purgers to estimate how many invalidations they can process.
    //   foreach ($this->purgers as $purger) {
    //     if (!is_int($limits[] = $purger->getCapacityLimit())) {
    //       throw new BadPluginBehaviorException(
    //         "The purger '$plugin_id' did not return an integer on getCapacityLimit().");
    //     }
    //   }
    //
    //   // Directly use its limit for just one loaded purger, lower it otherwise.
    //   if ($purgers === 1) {
    //     $limit = current($limits);
    //   }
    //   else {
    //     $limit = (int) floor(array_sum($limits) / $purgers / $purgers);
    //     if ($limit < 1) {
    //       $limit = 1;
    //     }
    //   }
    // }
    //
    // return $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {throw new \Exception("NOT IMPLEMENTED");
    // static $hint;
    //
    // // The hint is calculated just once per request, expectations don't change.
    // if (is_null($hint)) {
    //   $hint_per_type = [];
    //   foreach ($this->purgers as $id => $purger) {
    //     $plugin_id = $purger->getPluginId();
    //     $hint = $purger->getClaimTimeHint();
    //
    //     // Be strict about what values are accepted, better throwing exceptions
    //     // than having a crashing website because it is purging too heavily.
    //     if (!is_float($hint)) {
    //       $method = sprintf("%s::getClaimTimeHint()", get_class($purger));
    //       throw new BadPluginBehaviorException(
    //         "$method did not return a floating point value.");
    //     }
    //     if ($hint < 0.2) {
    //       $method = sprintf("%s::getClaimTimeHint()", get_class($purger));
    //       throw new BadPluginBehaviorException(
    //         "$method returned $hint, a value lower than 0.2.");
    //     }
    //     if ($hint > 10.0) {
    //       $method = sprintf("%s::getClaimTimeHint()", get_class($purger));
    //       throw new BadPluginBehaviorException(
    //         "$method returned $hint, a value higher than 10.0.");
    //     }
    //
    //     // Group the values by supported invalidation types and add up.
    //     foreach ($purger->getTypes() as $type) {
    //       if (!isset($hint_per_type[$type])) {
    //         $hint_per_type[$type] = 0.0;
    //       }
    //       $hint_per_type[$type] = $hint_per_type[$type] + $hint;
    //     }
    //   }
    //
    //   // Take the highest value, so the slowest invalidation type decides.
    //   $hint = max($hint_per_type);
    // }
    //
    // return $hint;
  }

}
