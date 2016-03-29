<?php

/**
 * @file
 * Contains \Drupal\purge\Logger\LoggerServiceInterface.
 */

namespace Drupal\purge\Logger;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Describes logging services to purge and its submodules, via a single channel.
 */
interface LoggerServiceInterface extends ServiceProviderInterface, ServiceModifierInterface {

  /**
   * Delete a channel part.
   *
   * @param string $id
   *   The identifier of the channel part.
   */
  public function deleteChannel($id);

  /**
   * Delete channel parts of which the ID starts with...
   *
   * @param string $id_starts_with
   *   Prefix to match all channel parts with to delete.
   */
  public function deleteChannels($id_starts_with);

  /**
   * Retrieve a channel part.
   *
   * @param string $id
   *   The identifier of the channel part.
   *
   * @throws \LogicException
   *   Thrown when the given identifier isn't registered.
   *
   * @return \Drupal\purge\Logger\LoggerChannelPartInterface.
   */
  public function get($id);

  /**
   * Check whether the given channel is registered.
   *
   * @param string $id
   *   The identifier of the channel part.
   *
   * @return true|false
   */
  public function hasChannel($id);

  /**
   * Add or update a channel part and its permissions.
   *
   * @param string $id
   *   The identifier of the channel part.
   * @param int[] $grants
   *   Unassociative array of RFC 5424 log types. Each passed type grants the
   *   channel permission to log that type of message, without specific
   *   permissions the logger will stay silent for that type.
   *
   *   Grants available:
   *    - \Drupal\Core\Logger\RfcLogLevel::EMERGENCY
   *    - \Drupal\Core\Logger\RfcLogLevel::ALERT
   *    - \Drupal\Core\Logger\RfcLogLevel::CRITICAL
   *    - \Drupal\Core\Logger\RfcLogLevel::ERROR
   *    - \Drupal\Core\Logger\RfcLogLevel::WARNING
   *    - \Drupal\Core\Logger\RfcLogLevel::NOTICE
   *    - \Drupal\Core\Logger\RfcLogLevel::INFO
   *    - \Drupal\Core\Logger\RfcLogLevel::DEBUG
   *
   * @throws \LogicException
   *   Thrown when the given id is empty or otherwise invalid.
   * @throws \LogicException
   *   Thrown when any given grant isn't known or otherwise invalid.
   */
  public function setChannel($id, array $grants = []);

}
