<?php

/**
 * @file
 * Contains \Drupal\purge\Queue\InvalidQueueConfiguredException.
 */

namespace Drupal\purge\Queue;

/**
 * Exception thrown when the 'queue' setting in "purge.plugin_detection" is set
 * away from 'automatic' and pointing to a plugin that does not exist.
 */
class InvalidQueueConfiguredException extends \Exception {}