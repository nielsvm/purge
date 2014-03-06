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
  // public $target = array();
  public $single;

  /**
   * Constructor for for the Single Bundle.
   */
  public function __construct($item_name, $item_type) {
    // Get the data from the given dataset.
    $select_data = parent::select_data();
    // dprint_r($select_data);
    // dprint_r($select_data[$item_type][$item_name]);
    // Then create an object out of it.
    $item_data = $select_data[$item_type][$item_name];
    $this->{$item_type}[$item_name] = unserialize($item_data);
    // $this->{$item_type}['localhost'] = unserialize($item_data);
    // parent::$item_type[$item_name] = unserialize($item_data);
    // set the single pointer to the created item.
    $this->single = $this->{$item_type}[$item_name];
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
    // The Drupal base url as target
    $this->target['drupal_url'] = new PurgeConfTarget();
    $this->target['drupal_url']->name = t('Drupal URL');
    $this->target['drupal_url']->description = t('Use the Drupal Base URL as a target.');
    $this->target['drupal_url']->enabled = 1;
    $this->target['drupal_url']->access = PURGE_ACCESS_ENABLE;
    $this->target['drupal_url']->api = array(
      'callback' => 'purge_target_drupal_url',
      'arguments' => PURGE_CONF_RUNTIME,
    );
    $this->target['drupal_url']->urls = array();

    // The localhost target
    $this->target['localhost'] = new PurgeConfTarget();
    $this->target['localhost']->name = t('Localhost');
    $this->target['localhost']->description = t('The default localhost ip address.');
    $this->target['localhost']->enabled = 1;
    $this->target['localhost']->access = PURGE_ACCESS_FULL;
    $this->target['localhost']->urls = array('http://127.0.0.1/');

    // The domain from the Drupal Basd URL.
    $this->domain['drupal_domain'] = new PurgeConfDomain();
    $this->domain['drupal_domain']->name = t('Drupal Domain');
    $this->domain['drupal_domain']->description = t('Use the domain in the Drupal Base URL.');
    $this->domain['drupal_domain']->enabled = 1;
    $this->domain['drupal_domain']->access = PURGE_ACCESS_ENABLE;
    $this->domain['drupal_domain']->api = array(
      'callback' => 'purge_domain_drupal_domain',
      'arguments' => PURGE_CONF_RUNTIME,
    );
    $this->domain['drupal_domain']->domains = array();

    // The Gzip header
    $this->header['gzip'] = new PurgeConfHeader();
    $this->header['gzip']->name = t('Gzip');
    $this->header['gzip']->description = t(
      'The Gzip header enables compression and is enabled by default on most http clients.'
    );
    $this->header['gzip']->enabled = 1;
    $this->header['gzip']->access = PURGE_ACCESS_ENABLE;
    $this->header['gzip']->headers = array('Accept-Encoding' => 'gzip');
  }

  /**
   * Save the Defaults.
   *   Will overwrite all existing configurations!
   *   Use reconfigure to restore the defaults but keep the other items.
   */
  public function save() {
    parent::save();
  }
}


/**
 * Menu callback for purge admin settings.
 */
function purge_configuration_form($form, &$form_state, $item_type = NULL) {
  $form = array();
  $form['purge'] = array(
    '#type' => 'item',
    '#title' => t('Purge Configuration'),
  );
  // Header to use for each table.
  $header = array(
    'name' => t('Name'),
    'description' => t('Description'),
    'status' => t('Status'),
    'actions' => t('Actions'),
  );
  $options = array();
  // Get all basic configuration.
  $bundle = new PurgeBundleBasic();
  // See if the type was passed as an agruement.
  if ($item_type) {
    $types = array($item_type);
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
    $options = array();
    foreach ($bundle->{$type} as $item_name => $item) {
      // See what actions are available in the UI
      $actions = array();
      if ($item->access != PURGE_ACCESS_SYSTEM) {
        $actions[] = l(t('View'), "admin/config/system/purge/{$type}/{$item_name}");
      }
      if ($item->access >= PURGE_ACCESS_ENABLE) {
        if ($item->enabled == 1) {
          $actions[] = l(t('Disable'), "admin/config/system/purge/{$type}/{$item_name}/disable");
        }
        else {
          $actions[] = l(t('Enable'), "admin/config/system/purge/{$type}/{$item_name}/enable");
        }
      }
      if ($item->access >= PURGE_ACCESS_CLONE) {
        $actions[] = l(t('Clone'), "admin/config/system/purge/{$type}/{$item_name}/clone");
      }
      else {
        $actions[] = l(t('Edit'), "admin/config/system/purge/{$type}/{$item_name}/edit");
        $actions[] = l(t('Delete'), "admin/config/system/purge/{$type}/{$item_name}/delete");
      }

      // Get the current status.
      if ($item->enabled == 1) {
        $status = t('Enabled');
      }
      else {
        $status = t('Disabled');
      }

      // Get thte item basic properties.
      $options[$item_name] = array(
        'name' => $item->name,
        'description' => $item->description,
        'status' => $status,
        'actions' => implode(' | ', $actions),
      );
    }
    $form['purge'][$type]['items'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
    );
  }
  return $form;
}

/**
 * Menu callback for purge item settins.
 */
function purge_item_form($form, &$form_state, $item_type, $item_name = '_empty_', $action = 'view') {
  // First lets load a bundle.
  $bundle = new PurgeBundleSingle($item_name, $item_type);
  // echo (serialize($bundle->target['localhost']));
  echo (serialize($bundle->$item_type));
  // Select the object we need from the bundle
  $item = $bundle->single;

  // The Basic Form elements.
  $form = array();
  $form['purge_item'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Item Settings'),
  );
  // First some hidden fields for internal use.
  $form['purge_item']['item_action'] = array(
    '#type' => 'hidden',
    '#default_value' => $action,
  );
  $form['purge_item']['item_id'] = array(
    '#type' => 'hidden',
    '#default_value' => $item->id,
  );
  $form['purge_tem']['item_type'] = array(
    '#type' => 'hidden',
    '#default_value' => $item_type,
  );
  $form['purge_item']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#required' => TRUE,
    '#description' => t('The user-friendy name for the item.'),
    '#default_value' => $item->name,
  );
  $form['purge_item']['machine_name'] = array(
    '#type' => 'machine_name',
    '#maxlength' => 21,
    '#default_value' => '',
    '#machine_name' => array(
      'exists' => 'purge_item_name_exists',
      'source' => array('purge_item', 'name'),
    ),
  );

  $form['purge_item']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#default_value' => $item->description,
  );
  $form['purge_item']['enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#description' => t('Disabled items will ignored during operations, even when included in sets.'),
    '#default_value' => $item->enabled,
  );
  // Now switch between the item_types to generate their respecitve forms.
  switch($item_type){
    // The target form.
    case 'target':
      $form['target'] = array(
        '#type' => 'fieldset',
        '#title' => 'Target Settings',
      );
      $form['target']['urls'] = array(
        '#type' => 'textarea',
        '#title' => t('URLs'),
        '#description' => t('The URLs where the Purge requests will be sent to. One URL per line.'),
        '#required' => TRUE,
        '#default_value' => implode("\n", $item->urls),
      );
    break;
  }

  $form['submit']  = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Helper function to check if a name exists in the current configuration.
 */
function purge_item_name_exists($item_name) {
  $error = 0;
  // Get a basic bundle.
  $bundle = new PurgeBundleBasic();
  foreach($bundle->types as $type) {
    if ($type == $item_nam) {
      $error = 1;
    }
    foreach($bundle->{$type} as $name => $item) {
      if ($item_name == $name) {
        $error = 1;
      }
    }
  }
  return $error;
}

