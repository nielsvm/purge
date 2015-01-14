<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurgeable\Path.
 */

namespace Drupal\purge\Plugin\PurgePurgeable;

use Drupal\purge\Purgeable\PluginInterface as Purgeable;
use Drupal\purge\Purgeable\PluginBase;
use Drupal\purge\Purgeable\Exception\InvalidRepresentationException;

/**
 * Describes a path based cache wipe, e.g. "/news/article-1".
 *
 * @PurgePurgeable(
 *   id = "path",
 *   label = @Translation("Path Purgeable")
 * )
 */
class Path extends PluginBase implements Purgeable {

  /**
   * {@inheritdoc}
   */
  public function __construct($representation, $wildcard_check = TRUE) {
    parent::__construct($representation);
    if ($wildcard_check && (strpos($representation, '*') !== FALSE)) {
      throw new InvalidRepresentationException(
      'HTTP path purgeables should not contain asterisks, wildcard paths '
      .' should use \Drupal\purge\Plugin\PurgePurgeable\WildcardPath.');
    }
    if ($representation === '*') {
      throw new InvalidRepresentationException(
        'HTTP path purgeables cannot be "*".');
    }
    if (($representation[0] !== '/') || (strpos($representation, '/') === FALSE)) {
      throw new InvalidRepresentationException(
        'HTTP path purgeables should always start with /, e.g.: "/node/1".');
    }
    if (strpos($representation, ' ') !== FALSE) {
      throw new InvalidRepresentationException(
      'HTTP path and wildcard purgeables cannot contain spaces, use %20.');
    }
  }
}
