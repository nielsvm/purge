<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Form\QueuerConfigFormBase.
 */

namespace Drupal\purge_ui\Form;

use Drupal\purge_ui\Form\PluginConfigFormBase;

/**
 * Provides a base class for queuer configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links
 * pointing at /admin/config/development/performance/purge/queuer/ID/dialog. You
 * can use /admin/config/development/performance/queuer/purger/ID as testing
 * variant that works outside of the modal dialog.
 */
abstract class QueuerConfigFormBase extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $parent_id = 'edit-queuers';

}
