<?php

/**
 * @file
 * Contains \Drupal\purge_processor_test\Plugin\Purge\Processor\CProcessor.
 */

namespace Drupal\purge_processor_test\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Test processor C.
 *
 * @PurgeProcessor(
 *   id = "c",
 *   label = @Translation("Processor C"),
 *   description = @Translation("Test processor C."),
 *   enable_by_default = false,
 *   configform = "",
 * )
 */
class CProcessor implements ProcessorInterface {

}
