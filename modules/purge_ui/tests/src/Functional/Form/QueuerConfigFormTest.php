<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\Tests\purge_ui\Functional\Form\Config\QueuerConfigFormTestBase;
use Drupal\purge_queuer_test\Form\QueuerConfigForm;

/**
 * Tests the drop-in configuration form for queuers.
 */
class QueuerConfigFormTest extends QueuerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_queuer_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'withform';

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_queuer_test.configform';

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
