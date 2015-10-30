<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\Plugin\PluginBase;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;

/**
 * Provides base implementations the immutable invalidation object.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
abstract class ImmutableInvalidationBase extends PluginBase implements ImmutableInvalidationInterface {

  /**
   * Unique integer ID for this object instance (during runtime).
   *
   * @var int
   */
  protected $id;

  /**
   * Mixed expression (or NULL) that describes what needs to be invalidated.
   *
   * @var mixed|null
   */
  protected $expression;

  /**
   * A enumerator that describes the current state of this invalidation.
   *
   * @var int|null
   */
  protected $state = NULL;

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return is_null($this->expression) ? '' : $this->expression;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpression() {
    return $this->expression;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    if (is_null($this->state)) {
      $this->state = SELF::FRESH;
    }
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    $mapping = [
      SELF::FRESH         => 'FRESH',
      SELF::PROCESSING    => 'PROCESSING',
      SELF::SUCCEEDED     => 'SUCCEEDED',
      SELF::FAILED        => 'FAILED',
      SELF::NOT_SUPPORTED => 'NOT_SUPPORTED',
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function getStateStringTranslated() {
    $mapping = [
      SELF::FRESH         => $this->t('New'),
      SELF::PROCESSING    => $this->t('Currently invalidating'),
      SELF::SUCCEEDED     => $this->t('Succeeded'),
      SELF::FAILED        => $this->t('Failed'),
      SELF::NOT_SUPPORTED => $this->t('Not supported'),
    ];
    return $mapping[$this->getState()];
  }

}
