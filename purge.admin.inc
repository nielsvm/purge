<?php

/**
 *  @file
 *   Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge');

// Then provide some extensions to bundles.
/**
 * Object definition for the All Basic Bundle.
 *   This bundle will contain just the basic conf objects used for
 *   administration.
 */
class PurgeBundleAll extends PurgeBundleBasic {

  /**
   * Constructor for for the All Bundle.
   */
  public function __construct() {
    // Get the data.
    $this->data = variable_get('purge_config');
    // Now construct Basic configuration objects.
    $types = array('set', 'target', 'domain', 'header', 'option');
    foreach($types as $type) {
      foreach ($this->data[$type] as $id => $data) {
        $this->$type[$id] = new PurgeConfBasic($id, $data);
      }
    }
  }
}

/**
 * Class definition for the Defaults Bundle.
 */
class PurgeBundleDefault extends PurgeBundleBasic {

  /**
   * Construct the default bundle.
   */
  public function __construct() {
    // First construct the data array
    $this->data = array(
      'target' => array(
        '1' => 'testdata'
      )
    );
  }
}


/**
 * Menu callback for purge admin settings.
 */
function purge_configuration_form($form, &$form_state, $type = NULL) {
  $form = array();
  $form['purge'] = array(
    '#type' => 'item',
    '#title' => t('Purge Configuration'),
  );
  // Header to use for each table.
  $header = array(
    'name' => t('Name'),
    'type' => t('Type'),
    'description' => t('Description'),
    'actions' => t('Actions'),
  );
  $items = array();
  // Get all basic configuration.
  $bundle = new PurgeBundleBasic;
  // See if the type was passed as an agruement.
  if ($type) {
    $types = array($type);
  }
  // Else set to all available types
  else {
    $types = $bundle->types;
  }
  // Loop through all types.
  foreach ($types as $type) {
    // fieldset for each type
    $form['purge'][$type] = array(
      '#type' => 'fieldset',
      '#title' => $type,
    );
    foreach ($bundle->$type as $id => $object) {
      // See what actions are available in the UI
      $actions = array();
      if ($object->access != PURGE_ACCESS_SYSTEM) {
        $actions[] = l(t('View'), "admin/config/system/purge/{$type}/{$object->name}");
      }
      elseif ($object->access != PURGE_ACCESS_VIEW) {
        if ($object->enabled == 1) {
          $actions[] = l(t('Disable'), "admin/config/system/purge/{$type}/{$object->name}/disable");
        }
        else {
          $actions[] = l(t('Enable'), "admin/config/system/purge/{$type}/{$object->name}/enable");
        }
      }
      elseif ($object->access != PURGE_ACCESS_CLONE) {
        $actions[] = l(t('Clone'), "admin/config/system/purge/{$type}/{$object->name}/clone");
      }
      else {
        $actions[] = l(t('Edit'), "admin/config/system/purge/{$type}/{$object->name}/edit");
        $actions[] = l(t('Delete'), "admin/config/system/purge/{$type}/{$object->name}/delete");
      }

      // Get thte item basic properties.
      $items[$proxy->pid] = array(
        'name' => $proxy->name,
        'type' => $proxy->type,
        'description' => $proxy->description,
        'actions' => implode(' | ', $actions),
      );
    }
    $form['purge'][$type]['items'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $items,
    );
  }
  return $form;
}

/**
 * Menu callback for purge item settins.
 */
function purge_item_form($form, &$form_state, $type = NULL, $name, $action = 'view') {

}
