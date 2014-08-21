<?php

/**
 * @file
 * Contains \Drupal\purge\RuntimeTest\RuntimeTestBase.
 */

namespace Drupal\purge\RuntimeTest;

use Drupal\Core\Plugin\PluginBase;
use Drupal\purge\RuntimeTest\Exception\TestNotImplementedCorrectly;
use Drupal\purge\RuntimeTest\RuntimeTestInterface;

/**
 * Describes a runtime test that tests a specific purging requirement.
 */
abstract class RuntimeTestBase extends PluginBase implements RuntimeTestInterface {

  /**
   * The title of the test as described in the plugin's metadata.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  private $title;

  /**
   * The description of the test as described in the plugin's metadata.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  private $description;

  /**
   * The severity of the outcome of this test, maps to any of these constants:
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_INFO
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_OK
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_WARNING
   *    - \Drupal\purge\RuntimeTest\RuntimeTestInterface::SEVERITY_ERROR
   *
   * @var int
   */
  private $severity;

  /**
   * A recommendation matching the severity level, may contain NULL.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $recommendation;

  /**
   * Optional test outcome / value (e.g. version numbers), may contain NULL.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Assures that RuntimeTestInterface::run() is executed and that the
   * severity gets set on the object. Tests for invalid responses.
   */
  protected function runTest() {
    if (!is_null($this->severity)) {
      return;
    }
    $this->severity = $this->run();
    if (!is_int($this->severity)) {
      throw new TestNotImplementedCorrectly('No int was returned by run().');
    }
    if ($this->severity < -1 || $this->severity > 2) {
      throw new TestNotImplementedCorrectly('No valid const response from run().');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $this->runTest();
    if (is_null($this->title)) {
      $this->title = $this->getPluginDefinition()['title'];
    }
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $this->runTest();
    if (is_null($this->description)) {
      $this->description = $this->getPluginDefinition()['description'];
    }
    return $this->description;
  }

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
  public function getSeverity() {
    $this->runTest();
    return $this->severity;
  }

  /**
   * Get the severity level as unprefixed string.
   *
   * @return
   *  The string comes without the 'SEVERITY_' prefix as on the constants.
   */
  public function getSeverityString() {
    $this->runTest();
    $mapping = array(
      SELF::SEVERITY_INFO      => 'INFO',
      SELF::SEVERITY_OK        => 'OK',
      SELF::SEVERITY_WARNING   => 'WARNING',
      SELF::SEVERITY_ERROR     => 'ERROR',
    );
    return $mapping[$this->getSeverity()];
  }

  /**
   * Get a recommendation matching the severity level.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getRecommendation() {
    $this->runTest();
    return $this->recommendation;
  }

  /**
   * Get an optional value for the test output, may return NULL.
   *
   * @return NULL or \Drupal\Core\StringTranslation\TranslationWrapper
   */
  public function getValue() {
    $this->runTest();
    return $this->value;
  }

  /**
   * Generates a hook_requirements() compatible item array.
   *
   * @return array
   *   An associative array with the following elements:
   *   - title: The name of the requirement.
   *   - value: The current value (e.g., version, time, level, etc). During
   *     install phase, this should only be used for version numbers, do not set
   *     it if not applicable.
   *   - description: The description of the requirement/status.
   *   - severity: The requirement's result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getHookRequirementsArray() {
    $this->runTest();
    return array(
      'title' => $this->getTitle(),
      'value' => $this->getValue(),
      'description' => $this->getDescription(),
      'severity' => $this->getSeverity()
    );
  }
}
