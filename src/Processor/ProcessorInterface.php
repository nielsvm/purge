<?php

/**
 * @file
 * Contains \Drupal\purge\Processor\ProcessorInterface.
 */

namespace Drupal\purge\Processor;

/**
 * Describe services that process invalidation objects.
 */
interface ProcessorInterface {

  /**
   * Disables the processor upon end-user request.
   *
   * @return void
   */
  public function disable();

  /**
   * Enables the processor upon end-user request.
   *
   * @return void
   */
  public function enable();

  /**
   * Describes whether the processor has its behavior activated or not.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Get the container id of the processor.
   *
   * @return string
   *   The container id of the processor.
   */
  public function getId();

  /**
   * Retrieve the title of the processing policy.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getTitle();

  /**
   * Retrieve a description about how the processing policy operates.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getDescription();

  /**
   * Set the container id of the processor.
   *
   * @param string $id
   *   The container id of the processor.
   *
   * @return void
   */
  public function setId($id);

}
