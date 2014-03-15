<?php

/**
 *  @file
 *   Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge', 'includes/purge');
module_load_include('inc', 'purge', 'includes/defaults');

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
  // $bundle = new PurgeBundleRuntime();
  //  $bundle = new PurgeBundleDefault();
  $bundle = new PurgeBundleBasic();
  // echo serialize($bundle->type);
  // See if the type was passed as an agruement.
  if ($item_type) {
    $types = array($item_type);
  }
  // Else set to all available types
  else {
    $types = array_keys($bundle->type);
  }
  // dprint_r($bundle);
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
  // $bundle = new PurgeBundleSingle($item_name, $item_type);
  $bundle = new PurgeBundleBasic();
  // echo (serialize($bundle->target['localhost']));
  // Select the object we need from the bundle
  $item = $bundle->{$item_type}[$item_name];
  // echo serialize($item);

  // The Basic Form elements.
  $form = array();
  $form['purge_item'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic Item Configuration'),
  );
  // First some hidden fields for internal use.
  $form['purge_item']['item_action'] = array(
    '#type' => 'hidden',
    '#default_value' => $action,
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
        '#title' => 'Target Configuration',
      );
      $form['target']['urls'] = array(
        '#type' => 'textarea',
        '#title' => t('URLs'),
        '#description' => t('The URLs where the Purge requests will be sent to. One URL per line.'),
        '#required' => true,
        '#default_value' => implode("\n", $item->urls),
      );
    break;
    case 'domain':
      $form['domain'] = array(
        '#type' => 'fieldset',
        '#title' => 'Domain Configuration',
      );
      $form['domain']['domain_conf'] = array(
        '#type' => 'textarea',
        '#title' => t('Domains'),
        '#description' => t('For each domain name Purge sends out a request. One domain per line.'),
        '#required' => true,
        '#default_value' => implode("\n", $item->domains),
      );
    break;
    case 'header':
      $form['header'] = array(
        '#type' => 'fieldset',
        '#title' => 'Header Configuration',
      );
      $headers = array();
      foreach ($item->headers as $key => $value) {
        $headers[] = implode(": ", array($key, $value));
      }
      $form['header']['header_conf'] = array(
        '#type' => 'textarea',
        '#title' => t('Header'),
        '#description' => t('Headers are added to each request. One header per line. Format: Header: value'),
        '#required' => true,
        '#default_value' => implode("\n", $headers),
      );
    break;
    case 'set':
      $form['set'] = array(
        '#type' => 'fieldset',
        '#title' => 'Set configuration',
      );
      // Generate the set
      $table_header = array(
        'name' => t('Name'),
        'description' => t('Description'),
      );
      // dprint_r($bundle->set);
      // foreach ($item->pointers
      // $set_pointers = array('target', 'domain', 'header', 'purger', 'option');
      foreach ($item->pointers as $pointer_type => $pointer_names) {
        $rows = array();
        $default_value = array();
        $multiple = true;
        if ($pointer_type == 'purger' || $pointer_type == 'option') {
          $multiple = false;
        }

        // foreach ($item->{$pointer_type} as $pointer_name) {
        foreach ($bundle->{$pointer_type} as $pointer_name => $pointer) {
        // foreach ($pointer_names as $pointer_name) {
          // echo serialize($item_object);
          $rows[$pointer_name] = array(
            // 'name' => $bundle->{$pointer_type}->{$pointer_name}->name,
            'name' => $bundle->{$pointer_type}[$pointer_name]->name,
            'description' => $bundle->{$pointer_type}[$pointer_name]->description,
          );

          // if ($item->{$pointer_type} == $pointer_name) {
          // dprint_r($item->pointers[$pointer_type]);
          if (in_array($pointer_name, $item->pointers[$pointer_type])) {
          // if ($pointer_name == array_keys($item->{$pointer_type}, $pointer_name)) {
            if ($multiple) {
              $default_value[$pointer_name] = $pointer_name;
            }
            else {
              $default_value = $pointer_name;
            }
          }
        }
        // dprint_r($default_value);
        $form['set'][$pointer_type] = array(
          '#type' => 'fieldset',
          '#title' => $bundle->type[$pointer_type]->name,
          '#description' => $bundle->type[$pointer_type]->description,
        );
        $form['set'][$pointer_type][$pointer_type . '_settings'] = array(
          '#type' => 'tableselect',
          '#title' => 'test', // $bundle->type[$pointer_type]->name,
          '#header' => $table_header,
          '#options' => $rows,
          '#default_value' => $default_value,
          '#multiple' => $multiple,

        );
        // Check if this type has options.
        $options = array();
        if (isset($bundle->type[$pointer_type]->options) || isset($bundle->{$pointer_type}[$pointer_name]->options)) {
          // Now get the form items for the options.
          $form['set'][$pointer_type][$pointer_type . '_options'] = array(
            '#type' => 'fieldset',
            '#title' => $bundle->type[$pointer_type]->name . ' ' . t('Options'),
          );
          // Display the type options
          if (isset($bundle->type[$pointer_type]->options)) {
            $options = $bundle->type[$pointer_type]->options;
            foreach ($options as $option_name => $option) {
            // If the set has a value for this option
              if (isset($item->options[$pointer_type])) {
                $options[$option_name]['set_value'] = $item->options[$pointer_type][$option_name];
              }
              // Now generate a form for these options.
              $form['set'][$pointer_type][$pointer_type . '_options'][$option_name] = purge_option_form($options);

            }
          }
          // dprint_r($options);


        }
        // $form['set'][$pointer_type][$pointer_type] . '_options'][
      }
      // Now disply the options for this set.
//      $form['set']['set']['set


    break;
  }
  // Now the items type options.
  // dprint_r($bundle->type[$item_type]->options);
  $options = array();

  $options = $bundle->type[$item_type]->options;
  if ($item_type == 'set') {
    $form[$item_type]['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Set Options'),
      '#description' => t('These settings apply to this set only.'),
    );
  }
  else {
    $form[$item_type]['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default Options'),
      '#description' => t('These defaults can be overruled per set.'),
    );
  }

  foreach ($options as $option_name => $option) {
    $form[$item_type]['options'][$option_name] =  purge_option_form($options);
  }


  $form['submit']  = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Form generator for option form.
 */
function purge_option_form($options) {
  $form = array();
  foreach ($options as $option_name => $option) {
    $form[$option_name] = array(
      '#title' => $option['name'],
      '#description' => $option['description'],
    );
    if (isset($option['values'])) {
      $form[$option_name]['#type'] = 'select';
      $form[$option_name]['#options'] = $option['values'];
    }
    else {
      $form[$option_name]['#type'] = 'checkbox';
    }
    if (isset($option['set_value'])) {
      $form[$option_name]['#default_value'] = $option['set_value'];
    }
    else {
      $form[$option_name]['#default_value'] = $option['default_value'];
    }
  }
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

