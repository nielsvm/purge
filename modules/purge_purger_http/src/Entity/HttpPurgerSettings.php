<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Entity\HttpPurgerSettings.
 */

namespace Drupal\purge_purger_http\Entity;

use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsInterface;

/**
 * Defines the HTTP purger settings entity.
 *
 * @ConfigEntityType(
 *   id = "httppurgersettings",
 *   config_prefix = "settings",
 *   static_cache = TRUE,
 *   entity_keys = {"id" = "id"},
 * )
 */
class HttpPurgerSettings extends PurgerSettingsBase implements PurgerSettingsInterface {

  /**
   * The invalidation plugin ID that this instance is configured to invalidate.
   *
   * @var string
   */
  public $invalidationtype = 'tag';

  /**
   * The hostname to connect to for the custom outbound HTTP request.
   *
   * @var string
   */
  public $hostname = 'localhost';

  /**
   * The port to connect to for the custom outbound HTTP request.
   *
   * @var int
   */
  public $port = 80;

  /**
   * The path to trigger a cache invalidation for.
   *
   * @var string
   */
  public $path = '';

  /**
   * The HTTP request method to use for the HTTP Purger.
   *
   * @var string
   */
  public $request_method = 'BAN';

  /**
   * Float describing the timeout of the request in seconds.
   *
   * @var float
   */
  public $timeout = 0.5;

  /**
   * Float describing the number of seconds to wait while trying to connect to
   * a server.
   *
   * @var float
   */
  public $connect_timeout = 0.2;

  /**
   * Float between 0.0-3.0 that describes the time to wait after invalidation.
   *
   * @var float
   */
  public $cooldown_time = 0.0;

  /**
   * Maximum number of HTTP requests that can be made during the runtime of
   * one request (including CLI). The higher this number is set, the more - CLI
   * based - scripts can process but this can also badly influence your end-user
   * performance when using runtime-based queue processors.
   *
   * @var int
   */
  public $max_requests = 100;

}
