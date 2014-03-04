<?php

/**
 *  @file
 *   Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge');

// Then provide some extensions to bundles.
/**
 * Object definition for the Single Bundle.
 *   This bundle will contain just a single item.
 */
class PurgeBundleSingle extends PurgeBundleBasic {
  public $single;

  /**
   * Constructor for for the Single Bundle.
   */
  public function __construct($item, $item_type) {
    // Call the parent constructor
    parent::__construct();
    // Set the $item variable to the new object for easy reference.
    $this->single = $this->$item_type[$item];
  }

  /**
   * Function to select a single item from the dataset.
   *
   * @return array $data
   */
  public function select_data() {
    // Get the data from the drupal variable
    $item_data = array();
    $all_data = array();
    $all_data = variable_get('purge_config');
    // Check what type the item is.
    if (is_int($item)) {
      $item_id = $item;
      foreach ($this->$types as $type) {
        $item_data[$type] = array();
        if (is_set($all_data[$type][$item_id])) {
          $item_data[$type][$item_id] = $all_data[$type][$item_id];
        }
      }
    }
    return $item_data;
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
    $this->target['localhost'] = new PurgeConfTarget();
    $this->target['localhost']->id = 1;
    $this->target['localhost']->enabled = 1;
    $this->target['localhost']->name = 'localhost';
    $this->target['localhost']->description = '';
    $this->target['localhost']->urls = array('127.0.0.1');
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
    foreach ($bundle->$type as $id => $item) {
      // See what actions are available in the UI
      $actions = array();
      if ($item->access != PURGE_ACCESS_SYSTEM) {
        $actions[] = l(t('View'), "admin/config/system/purge/{$type}/{$item->name}");
      }
      elseif ($item->access != PURGE_ACCESS_VIEW) {
        if ($item->enabled == 1) {
          $actions[] = l(t('Disable'), "admin/config/system/purge/{$type}/{$item->name}/disable");
        }
        else {
          $actions[] = l(t('Enable'), "admin/config/system/purge/{$type}/{$item->name}/enable");
        }
      }
      elseif ($item->access != PURGE_ACCESS_CLONE) {
        $actions[] = l(t('Clone'), "admin/config/system/purge/{$type}/{$item->name}/clone");
      }
      else {
        $actions[] = l(t('Edit'), "admin/config/system/purge/{$type}/{$item->name}/edit");
        $actions[] = l(t('Delete'), "admin/config/system/purge/{$type}/{$item->name}/delete");
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
function purge_item_form($form, &$form_state, $item_type, $item_key = 0, $action = 'view') {
  // First lets load a bundle.
  $bundle = new PurgeBundleSingle($item_key, $item_type);
  $bundle->save;
  echo (serialize($bundle->target['localhost']));
  // Select the object we need from the bundle
  $item = $bundle->single;
  // echo (serialize($item));
  // $item = $bundle->target['1'];

  // The Basic Form elements.
  $form = array();
  // First some hidden fields for internal use.
  $form['item_action'] = array(
    '#type' => 'hidden',
    '#default_value' => $action,
  );
  $form['target_id'] = array(
    '#type' => 'hidden',
    '#default_value' => $item->id,
  );
  $form['target_type'] = array(
    '#type' => 'hidden',
    '#default_value' => $item['type'],
  );
  $form['target_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#required' => TRUE,
    '#default_value' => $item['name'],
  );
  $form['target_desc'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $item['desc'],
  );
}
