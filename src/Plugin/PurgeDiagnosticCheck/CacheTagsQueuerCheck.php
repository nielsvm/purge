<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeDiagnosticCheck\CacheTagsQueuerCheck.
 */

namespace Drupal\purge\Plugin\PurgeDiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Simply checks if "purge.queuer.cache_tags" is defined and queues cache tags.
 *
 * The sole reason this test exists is to check if a service - defined in this
 * same module - is on the container. Certain use cases of this module - e.g.
 * a pure executive worker - would require submodules to remove that service,
 * which will break the end-user experience. This test therefore serves as
 * UX helper to notify users in the form of a diagnostic warning.
 *
 * @PurgeDiagnosticCheck(
 *   id = "cachetagsqueuerexists",
 *   title = @Translation("Tags queuer"),
 *   description = @Translation("Checks if invalidated core tags get queued."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class CacheTagsQueuerCheck extends PluginBase implements PluginInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface.
   */
  protected $container;

  /**
   * Constructs a \Drupal\purge\Plugin\PurgeDiagnosticCheck\PurgerAvailableCheck object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container.
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Purger\PurgerServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->container->has('purge.queuer.cache_tags')) {
      $this->value = $this->t('Disabled');
      $this->recommendation = $this->t("Purge does NOT add 'tag' invalidations to its queue because the service got disabled by another module.");
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->value = $this->t('Enabled');
      $this->recommendation = $this->t("When Drupal invalidates its own content, purge will add a 'tag' item to its queue for external invalidation.");
      return SELF::SEVERITY_OK;
    }
  }
  
}
