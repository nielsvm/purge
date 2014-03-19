<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeQueue\DatabaseQueue.
 */

namespace Drupal\purge\Plugin\PurgeQueue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\purge\Queue\QueueInterface;
use Drupal\purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Queue\QueueInterface compliant database backed queue.
 *
 * @ingroup purge_queue_types
 *
 * @Plugin(
 *   id = "DatabaseQueue",
 *   label = @Translation("Database backed purge queue.")
 * )
 */
class DatabaseQueue extends QueueBase implements QueueInterface {

  /**
   * Holds the 'queue.database' queue retrieved from Drupal.
   */
  protected $dbqueue;

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * Setup a database backed queue.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $service_container
   *   The service container, allowing plugins to inject dependencies.
   */
  function __construct(ContainerInterface $service_container) {
    parent::__construct($service_container);
    $this->name = 'purge';
    $this->connection = $this->container->get('database');
    $this->dbqueue = $this->container->get('queue.database')->get($this->name);
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createItem().
   */
  public function createItem($data) {
    return $this->dbqueue->createItem($data);
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $item_ids = $records = array();

    // Build a array with all exactly records as they should turn into rows.
    $time = time();
    foreach ($items as $data) {
      $records[] = array(
        'name' => $this->name,
        'data' => serialize($data),
        'created' => $time,
      );
    }

    // Insert all of them using just one multi-row query.
    $query = db_insert('queue')->fields(array('name', 'data', 'created'));
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int)$id;

      // A multiple row-insert doesn't give back all the individual IDs, so
      // calculate them back by applying subtraction.
      for ($i = 1; $i <= count($records); $i++) {
        $item_ids[] = $id;
        $id++;
      }
      return $item_ids;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::numberOfItems().
   */
  public function numberOfItems() {
    return $this->dbqueue->numberOfItems();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function claimItem($lease_time = 30) {
    return $this->dbqueue->claimItem($lease_time);
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $returned_items = $item_ids = array();

    // Retrieve all items in one query.
    $items = $this->connection->queryRange('SELECT data, created, item_id FROM {queue} q WHERE expire = 0 AND name = :name ORDER BY created ASC', 0, $claims, array(':name' => $this->name));

    // Iterate all returned items and unpack them.
    foreach ($items as $item) {
      if (!$item) continue;
      $item_ids[] = $item->item_id;
      $item->data = unserialize($item->data);
      $returned_items[] = $item;
    }

    // Update the items (marking them claimed) in one query.
    $update = $this->connection->update('queue')
      ->fields(array(
        'expire' => time() + $lease_time,
      ))
      ->condition('item_id', $item_ids, 'IN')
      ->condition('expire', 0);

    // Commit the update and return the items (or not).
    if ($update->execute()) {
      return $returned_items;
    }
    else {
      return array();
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    return $this->dbqueue->releaseItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    $item_ids = array();
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $update = $this->connection->update('queue')
      ->fields(array(
        'expire' => 0,
      ))
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
    if ($update) {
      return array();
    }
    else {
      return $items;
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteItem().
   */
  public function deleteItem($item) {
    return $this->dbqueue->deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $item_ids = array();
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $update = $this->connection
      ->delete('queue')
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createQueue().
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // Drupal is first installed) so there is nothing we need to do to create
    // a new queue.
    return $this->dbqueue->createQueue();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteQueue().
   */
  public function deleteQueue() {
    return $this->dbqueue->deleteQueue();
  }
}
