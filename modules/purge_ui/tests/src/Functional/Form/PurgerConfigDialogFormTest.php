<?php

namespace Drupal\Tests\purge_ui\Functional\Form;

use Drupal\purge_purger_test\Form\PurgerConfigForm;
use Drupal\Tests\purge_ui\Functional\Form\Config\PurgerConfigFormTestBase;

/**
 * Tests the drop-in configuration form for purgers (modal dialog).
 *
 * @group purge
 */
class PurgerConfigDialogFormTest extends PurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_purger_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $dialogRouteTest = TRUE;

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
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->formArgs[0]['dialog'] = TRUE;
  }

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
