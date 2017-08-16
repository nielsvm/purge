<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Describes a service that interacts with diagnostic checks.
 */
interface DiagnosticsServiceInterface extends ServiceInterface, ContainerAwareInterface, \Iterator, \Countable {

  /**
   * Renders severities as a Drupal-like requirements array.
   *
   * @param int $floor
   *   The type of severities to return and everything above it. When you pass
   *   SEVERITY_INFO as value here, all four severities will be returned. But if
   *   you pass SEVERITY_WARNING, only SEVERITY_WARNING and SEVERITY_ERROR level
   *   checks are returned for instance.
   * @param bool $prefix_title
   *   When TRUE, this prefixes titles with "Purge" to mark their origin.
   *
   * @return array[]
   *   Array with Drupal-like requirement arrays as values.
   */
  public function getRequirementsArray($filter = DiagnosticCheckInterface::SEVERITY_INFO, $prefix_title = FALSE);

  /**
   * Reports if any of the diagnostic checks report a SEVERITY_ERROR severity.
   *
   * This method provides a simple - boolean evaluable - way to determine if
   * a \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR severity
   * was reported by one of the checks. If SEVERITY_ERROR was reported, purging
   * cannot continue and should happen once all problems are resolved.
   *
   * @return false|\Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface
   *   The SEVERITY_ERROR reporting check, or FALSE when everything was fine.
   */
  public function isSystemOnFire();

  /**
   * Reports if any of the diagnostic checks report a SEVERITY_WARNING severity.
   *
   * This method provides a - boolean evaluable - way to determine if a check
   * reported a \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING.
   * If SEVERITY_WARNING was reported, cache invalidation can continue but it is
   * important that the site administrator gets notified.
   *
   * @return false|\Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface
   *   The SEVERITY_WARNING reporting check, or FALSE when everything was fine.
   */
  public function isSystemShowingSmoke();

}
