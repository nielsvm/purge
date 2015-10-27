<?php

/**
 * @file
 * Contains \Drupal\purge_processor_test\Plugin\Purge\Processor\BProcessor.
 */

namespace Drupal\purge_processor_test\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Test processor B.
 *
 * @PurgeProcessor(
 *   id = "b",
 *   label = @Translation("Processor B"),
 *   description = @Translation("Test processor B."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class BProcessor implements ProcessorInterface {

}
