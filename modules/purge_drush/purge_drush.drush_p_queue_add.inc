<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;

/**
 * Schedule an item for later processing.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purgeQueuers
 *   The purge queuers service.
 * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purgeInvalidationFactory
 *   The purge invalidation factory service.
 * @param string $type
 *   The type of invalidation to queue.
 * @param mixed|null $expression
 *   Value - usually string - that describes the kind of invalidation, NULL
 *   when the type of invalidation doesn't require $expression. Types usually
 *   validate the given expression and throw exceptions for bad input.
 *
 * @return array
 *   Drush render/format array.
 */
function drush_purge_drush_p_queue_add($format, QueueServiceInterface $purgeQueue, QueuersServiceInterface $purgeQueuers, InvalidationsServiceInterface $purgeInvalidationFactory, $type, $expression = NULL) {

  // Retrieve our queuer object and fail when it is not returned.
  if (!($queuer = $purgeQueuers->get('drush_purge_queue_add'))) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('NOT_AUTHORIZED', dt('Not authorized, queuer missing!'));
  }

  // Instantiate the invalidation object based on user input.
  try {
    $invalidation = $purgeInvalidationFactory->get($type, $expression);
  }
  catch (PluginNotFoundException $e) {
    if (in_array($format, ['yaml', 'json'])) return FALSE;
    return drush_set_error(dt("Type '@type' does not exist, see 'drush p-types' for available types.", ['@type' => $type]));
  }
  catch (InvalidExpressionException $e) {
    if (in_array($format, ['yaml', 'json'])) return FALSE;
    return drush_set_error($e->getMessage());
  }
  catch (MissingExpressionException $e) {
    if (in_array($format, ['yaml', 'json'])) return FALSE;
    return drush_set_error($e->getMessage());
  }

  // Add the object and return TRUE/message.
  $purgeQueue->add($queuer, [$invalidation]);
  if (in_array($format, ['yaml', 'json'])) {
    return TRUE;
  }
  else {
    return dt('Item successfully queued, will be processed later...');
  }
}
