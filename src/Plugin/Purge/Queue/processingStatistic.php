<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purge\Counter\Counter;
use Drupal\purge\Counter\ExplainedCounterInterface;

/**
 * The number of queue items actively being processed at the moment.
 *
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::claim()
 */
class processingStatistic extends Counter implements ExplainedCounterInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId() {
   return 'processing';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t(
      'The number of actively processing queue items'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t(
        'This counter is not a true statistic, but reflects how many queue'
      . ' items are marked as "claimed" at the moment, which means that a'
      . ' processor is currently busy processing these items. In most'
      . ' circumstances, this number will be 0 as queues usually empty fast,'
      . ' but you can always catch a moment when items are being '
      . ' processed. Quickly after such moment, the number should be 0 again.'
    );
  }

}
