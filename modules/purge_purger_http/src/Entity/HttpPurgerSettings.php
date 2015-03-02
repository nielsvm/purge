<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Entity\HttpPurgerSettings.
 */

namespace Drupal\purge_purger_http\Entity;

use Drupal\purge\Purger\SettingsBase;
use Drupal\purge\Purger\SettingsInterface;

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
class HttpPurgerSettings extends SettingsBase implements SettingsInterface {

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
   * Float describing the timeout of the request in seconds. Use 0 to wait
   * indefinitely (the default behavior).
   *
   * @var float
   */
  public $timeout = 3.0;

  /**
   * Float describing the number of seconds to wait while trying to connect to
   * a server. Use 0 to wait indefinitely (the default behavior).
   *
   * @var float
   */
  public $connect_timeout = 1.5;

  /**
   * Maximum number of HTTP requests that can be made during the runtime of
   * one request (including CLI). The higher this number is set, the more - CLI
   * based - scripts can process but this can also badly influence your end-user
   * performance when using runtime-based queue processors.
   *
   * @var int
   */
  public $max_requests = 50;

  /**
   * Percentage of PHP's maximum execution time that can be allocated to
   * processing. When PHP's setting is set to 0 (e.g. on CLI), the max requests
   * setting will be used for capacity limiting. Whenever you notice Drupal
   * requests timing out, lower this percentage.
   *
   * @var float
   */
  public $execution_time_consumption = 0.75;

}
