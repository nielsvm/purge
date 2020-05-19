<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\Tests\purge_ui\Functional\Form\Config\PurgerConfigFormTestBase;
use Drupal\purge_purger_test\Form\PurgerConfigForm;

/**
 * Tests the drop-in configuration form for purgers.
 */
class PurgerConfigFormTest extends PurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_purger_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'withform';

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_purger_test.purgerconfigform';

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
