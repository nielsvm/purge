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
      $this->state = SELF::STATE_NEW;
    }
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    $mapping = [
      SELF::STATE_NEW           => 'NEW',
      SELF::STATE_PURGING       => 'PURGING',
      SELF::STATE_PURGED        => 'PURGED',
      SELF::STATE_FAILED        => 'FAILED',
      SELF::STATE_UNSUPPORTED   => 'UNSUPPORTED',
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function getStateStringTranslated() {
    $mapping = [
      SELF::STATE_NEW           => $this->t('New'),
      SELF::STATE_PURGING       => $this->t('Currently invalidating'),
      SELF::STATE_PURGED        => $this->t('Succeeded'),
      SELF::STATE_FAILED        => $this->t('Failed'),
      SELF::STATE_UNSUPPORTED   => $this->t('Not supported'),
    ];
    return $mapping[$this->getState()];
  }

}
