<?php

/**
 * @file
 * Contains \Drupal\purge\Queuer\QueuerInterface.
 */

namespace Drupal\purge\Queuer;

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

}
