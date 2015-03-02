<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Entity\VarnishTagPurgerSettings.
 */

namespace Drupal\purge_purger_varnishpoc\Entity;

use Drupal\purge\Purger\SettingsBase;
use Drupal\purge\Purger\SettingsInterface;

/**
 * Defines the VarnishTagPurgerSettings entity.
 *
 * @ConfigEntityType(
 *   id = "varnishtagpurgersettings",
 *   config_prefix = "settings",
 *   static_cache = TRUE,
 *   entity_keys = {"id" = "id"},
 * )
 */
class VarnishTagPurgerSettings extends SettingsBase implements SettingsInterface {

  /**
   * The URL of the Varnish instance to send BAN requests to.
   *
   * @var \Drupal\Core\Url
   */
  public $uri = '';

  /**
   * The outbound HTTP header that identifies the tag to be purged.
   *
   * @var string
   */
  public $header = 'X-Drupal-Cache-Tags-Banned';

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
