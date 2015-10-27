<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\ProcessorConfigFormBase.
 */

namespace Drupal\purge_ui\Form;

use Drupal\purge_ui\Form\PluginConfigFormBase;

/**
 * Provides a base class for processor configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links
 * pointing at /admin/config/development/performance/purge/processor/ID/dialog. You
 * can use /admin/config/development/performance/purge/processor/ID as testing
 * variant that works outside of the modal dialog.
 */
abstract class ProcessorConfigFormBase extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $parent_id = 'edit-processors';

}
