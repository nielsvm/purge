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
class PurgeBundleAllBasic extends PurgeBundleBasic {

  /**
   * Constructor for for the All Basic Bundle.
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
function purge_configuration_form($form, &$form_state) {
  $form = array();
  $form['purge'] = array(
    '#type' => 'item',
    '#title' => t('Purge Configuration'),
  );
  // Get all basic configuration
  $bundle = new PurgeBundleRuntime;
  return $form;
}



/**
 * Load the defaults.
 */
function purge_defaults_get() {

}
