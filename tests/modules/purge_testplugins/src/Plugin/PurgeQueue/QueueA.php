<?php

/**
 * @file
 * Contains \Drupal\purge_testplugins\Plugin\PurgeQueue\QueueA.
 */

namespace Drupal\purge_testplugins\Plugin\PurgeQueue;

use Drupal\purge\Plugin\PurgeQueue\Memory;
use Drupal\purge\Queue\PluginInterface;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "queue_a",
 *   label = @Translation("Memqueue A"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class QueueA extends Memory implements PluginInterface {}
