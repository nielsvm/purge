<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface.
 */

namespace Drupal\purge\Plugin\Purge\Queuer;

/**
 * Describes services that queue invalidation objects upon certain events.
 */
interface QueuerInterface {

  /**
   * Disables the queuer upon end-user request.
   *
   * @return void
   */
  public function disable();

  /**
   * Enables the queuer upon end-user request.
   *
   * @return void
   */
  public function enable();

  /**
   * Describes whether the queuer has its behavior activated or not.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Get the container id of the queuer.
   *
   * @return string
   *   The container id of the queuer.
   */
  public function getId();

  /**
   * Retrieve the title of this queuing service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getTitle();

  /**
   * Retrieve a description of what this queuer, queues.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getDescription();

  /**
   * Set the container id of the queuer.
   *
   * @param string $id
   *   The container id of the queuer.
   *
   * @return void
   */
  public function setId($id);

}
