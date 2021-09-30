<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_processor_test\Form\ProcessorConfigForm;
use Drupal\Tests\purge_ui\Functional\Form\Config\ProcessorConfigFormTestBase;

/**
 * Tests the drop-in configuration form for processors.
 *
 * @group purge
 */
class ProcessorConfigFormTest extends ProcessorConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_processor_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'withform';

  /**
   * {@inheritdoc}
   */
  protected $formClass = ProcessorConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_processor_test.configform';

  /**
   * {@inheritdoc}
   */
  public function testSaveConfigurationSubmit(): void {
    // Since the stub form under test has no form submission
    // implemented, we verify the presence of its textfield
    // instead. Tests for real configuration form must of
    // course test validation and submit thoroughly.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->fieldExists('textfield');
  }

}
