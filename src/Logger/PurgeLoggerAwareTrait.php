<?php

namespace Drupal\purge\Logger;

use Psr\Log\LoggerAwareTrait;

/**
 * Provides logging services for purge components.
 */
trait PurgeLoggerAwareTrait {
  use LoggerAwareTrait;

  /**
   * Channel logger.
   *
   * @var null|\Drupal\purge\Logger\LoggerChannelPartInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function logger() {
    if (is_null($this->logger)) {
      throw new \LogicException('Logger unavailable, call ::setLogger().');
    }
    return $this->logger;
  }

}
