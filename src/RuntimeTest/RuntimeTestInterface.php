<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\RuntimeTestInterface.
 */

namespace Drupal\purge\RuntimeTest;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes a runtime test that tests a specific purging requirement.
 */
interface RuntimeTestInterface extends PluginInspectionInterface {

  /**
   * Non-blocking severity -- Informational message only.
   */
  const SEVERITY_INFO = -1;

  /**
   * Non-blocking severity -- test successfully passed.
   */
  const SEVERITY_OK = 0;

  /**
   * Non-blocking severity -- Warning condition; proceed but flag warning.
   */
  const SEVERITY_WARNING = 1;

  /**
   * BLOCKING severity -- Error condition; purge.purger service cannot operate.
   */
  const SEVERITY_ERROR = 2;

  /**
   * Perform the test and determine the severity level.
   *
   * Runtime tests determine whether something you are checking for is in shape,
   * for instance CMI settings on which your plugin depends. Any test reporting
   * SELF::SEVERITY_ERROR in its run() method, will cause purging to stop
   * working. Any other severity level will let the purger proceed operating
   * but you may report any warning through getRecommendation() to be shown
   * on Drupal's status report or any other diagnostic listing.
   *
   * @code
   * public function run() {
   *   if (...test..) {
   *     return SELF::SEVERITY_OK;
   *   }
   *   return SELF::SEVERITY_WARNING;
   * }
   * @endcode
   *
   * @warning
   *   As runtime tests can be expensive, this method is called as rarely as
   *   possible. Tests derived from \Drupal\purge\RuntimeTest\RuntimeTestBase
   *   will only see the test getting executed when any of the get* methods are
   *   called.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_INFO
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_OK
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_WARNING
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_ERROR
   */
  public function run();

  /**
   * Gets the title of the test.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getTitle();

  /**
   * Gets the description of the test.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getDescription();

  /**
   * Get the severity level.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_INFO
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_OK
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_WARNING
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_ERROR
   */
  public function getSeverity();

  /**
   * Get the severity level as unprefixed string.
   *
   * @return string
   *  The string comes without the 'SEVERITY_' prefix as on the constants.
   */
  public function getSeverityString();

  /**
   * Get a recommendation matching the severity level, may return NULL.
   *
   * @return NULL or \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getRecommendation();

  /**
   * Get an optional value for the test output, may return NULL.
   *
   * @return NULL or \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getValue();

  /**
   * Get the severity level, expressed as hook_requirements() severity.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - REQUIREMENT_INFO
   *    - REQUIREMENT_OK
   *    - REQUIREMENT_WARNING
   *    - REQUIREMENT_ERROR
   */
  public function getHookRequirementsSeverity();

  /**
   * Generates a hook_requirements() compatible item array.
   *
   * @return array
   *   An associative array with the following elements:
   *   - title: The name of this test.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the test.
   *   - severity: The test's result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getHookRequirementsArray();
}
