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
   * {@inheritdoc}
   */
  public function getSeverity() {
    $this->runTest();
    return $this->severity;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getRecommendation() {
    $this->runTest();
    return $this->recommendation;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->runTest();
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsSeverity() {
    static $mapping;
    $this->runTest();
    if (is_null($mapping)) {
      include_once DRUPAL_ROOT . '/core/includes/install.inc';

      // Currently, our constants hold the exact same values as core's
      // requirement constants. However, as our runtime test API is more than
      // just a fancy objectification of hook_requirements we need to assure
      // that this lasts over time, and thus map the constants.
      $mapping = array(
        SELF::SEVERITY_INFO      => REQUIREMENT_INFO,
        SELF::SEVERITY_OK        => REQUIREMENT_OK,
        SELF::SEVERITY_WARNING   => REQUIREMENT_WARNING,
        SELF::SEVERITY_ERROR     => REQUIREMENT_ERROR,
      );
    }
    return $mapping[$this->getSeverity()];
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $this->runTest();
    $description = $this->getDescription();
    if ($recommendation = $this->getRecommendation()) {
      $description = $recommendation;
    }
    return array(
      'title' => $this->t('Purge - @title', array('@title' => $this->getTitle())),
      'value' => $this->getValue(),
      'description' => $description,
      'severity' => $this->getHookRequirementsSeverity()
    );
  }
}
