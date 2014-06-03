<?php

/**
 *  @file
 *   Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge', 'includes/purge');
module_load_include('inc', 'purge', 'includes/defaults');

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
    $types = array_keys($bundle->type);
    // Make sure sets are displayed first.
    $types = array_diff($types, array('set'));
    array_unshift($types, 'set');
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
      if ($item->access > PURGE_ACCESS_CLONE) {
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
function purge_item_form($form, &$form_state, $item_type, $item_name = NULL, $action = 'view') {
  // First lets load a bundle.
  $bundle = new PurgeBundleBasic();
  // Select the object we need for this form.
  if ($action == 'add') {
    switch ($item_type) {
      case 'target':
        $item = new PurgeItemTargetStatic();
        break;
      case 'domain':
        $item = new PurgeItemDomainStatic();
        break;
      case 'header':
        $item = new PurgeItemHeaderStatic();
        break;
      case 'set':
        $item = new PurgeItemSet();
        break;
    }
    $item_name = '';
  }
  elseif ($action == 'clone') {
    $item = clone $bundle->{$item_type}[$item_name];
    $item->name = '';
    $item->description = '';
  }
  else {
    $item = $bundle->{$item_type}[$item_name];
  }

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
  if ($action == 'add' || $action == 'clone') {
    $form['purge_item']['machine_name'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 21,
      '#default_value' => '',
      '#machine_name' => array(
        'exists' => 'purge_item_name_exists',
        'source' => array('purge_item', 'name'),
      ),
    );
  }
  else {
    $form['purge_item']['machine_name'] = array(
      '#type' => 'hidden',
      '#default_value' => $item_name,
    );
  }

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
  switch($item_type) {
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
      $form['domain']['domains'] = array(
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
      foreach ($item->headers as $header_key => $header_value) {
        $headers[] = implode(": ", array($header_key, $header_value));
      }
      $form['header']['headers'] = array(
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
      // foreach ($item->pointers
      foreach ($item->pointers as $pointer_type => $pointer_names) {
        $rows = array();
        $default_value = array();
        $multiple = true;
        if ($pointer_type == 'purger' || $pointer_type == 'option') {
          $multiple = false;
        }
        foreach ($bundle->{$pointer_type} as $pointer_name => $pointer) {
          $rows[$pointer_name] = array(
            'name' => $bundle->{$pointer_type}[$pointer_name]->name,
            'description' => $bundle->{$pointer_type}[$pointer_name]->description,
          );
          if (in_array($pointer_name, $item->pointers[$pointer_type])) {
            if ($multiple) {
              $default_value[$pointer_name] = $pointer_name;
            }
            else {
              $default_value = $pointer_name;
            }
          }
        }
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
        if (isset($bundle->type[$pointer_type]->options)) {
          // isset($bundle->{$pointer_type}[$pointer_name]->options)) {
          // Now get the form items for the options.
          $form['set'][$pointer_type][$pointer_type . '_options'] = array(
            '#type' => 'fieldset',
            '#title' => $bundle->type[$pointer_type]->name . ' ' . t('Options'),
          );
          // Display the type options
          $options = $bundle->type[$pointer_type]->options;
          $all_item_options = array();
          foreach ($options as $option_name => $option) {
            // First display a form for all type specific options
            if ($options[$option_name]['context'] == 'type') {
            // If the set has a value for this option
              if (isset($item->options[$pointer_type])) {
                $options[$option_name]['set_value'] = $item->options[$pointer_type][$option_name];
              }
              // Now generate a form for these options.
              $form['set'][$pointer_type][$pointer_type . '_options'][$option_name] = purge_option_form($options);
            }
            // Then save the item specific options
            elseif ($options[$option_name]['context'] == 'item') {
              $all_item_options[$option_name] = $option;
            }
          }
          // First go to all items of this type
          foreach ($bundle->{$pointer_type} as $pointer_name => $pointer) {
            $this_item_options = array();
            // Now pass each item option
            foreach ($all_item_options as $item_option_name => $item_option) {
              // Check if the item has the option defined.
              if (isset($bundle->{$pointer_type}[$pointer_name]->options[$item_option_name])) {
                $this_item_options[$item_option_name] = $item_option;
                if (isset($item->options[$pointer_type][$item_option_name])) {
                  $item_option['set_value'] = $item->options[$pointer_type][$item_option_name];
                }
              }
            }
            if (count($this_item_options) > 0) {
              $form['set'][$pointer_type][$pointer_type . '_options'][$pointer_name] = array(
                '#type' => 'fieldset',
                '#title' => $bundle->{$pointer_type}[$pointer_name]->name . ' ' . t('Options'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="' . $pointer_type . '_settings"]' => array('value' => $pointer_name),
                  ),
                ),
              );
              // Generate an option form.
              $form['set'][$pointer_type][$pointer_type . '_options'][$pointer_name][$pointer_name . '_settings']
                = purge_option_form($this_item_options);
            }
          }
        }
      }
    break;
  }
  // Now the items type options.
  $options = array();
  $options = $bundle->type[$item_type]->options;
  if (count($options) > 0) {
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
  }

  // Now display submit buttons.
  if ($action != 'view' && $item->access >= PURGE_ACCESS_CLONE) {
    $form['submit']  = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    if ($item instanceof PurgeValidateble) {
      $form['validate'] = array(
        '#type' => 'submit',
        '#value' => t('Preview'),
        '#submit' => array('purge_item_form_validate'),
      );
    }
  }
  if ($item->access >= PURGE_ACCESS_ENABLE) {
    if ($item->enabled) {
      $form['disable'] = array(
        '#type' => 'submit',
        '#value' => t('Disable'),
        '#submit' => array('purge_item_form_disable'),
      );
    }
    else {
      $form['enable'] = array(
        '#type' => 'submit',
        '#value' => t('Enable'),
        '#submit' => array('purge_item_form_enable'),
      );
    }
  }
  return $form;
}

/**
 * Form validation for the item form.
 */
function purge_item_form_validate($form, &$form_state) {
  // First get the bundle with the form state
  $bundle = purge_item_form_load($form, $form_state);
  $errors = $bundle->validate();
  if (count($errors) > 0) {
    foreach ($errors as $error_name => $error_message) {
      // Display the errors
      form_set_error($error_name, $error_message);
    }
  }
}

/**
 * Form submit for the item form.
 */
function purge_item_form_submit($form, &$form_state) {
  $bundle = purge_item_form_load($form, $form_state);
  $bundle->save();
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
 * Function to load an item from current form values.
 *
 * @param array $form
 * @param array $form_state
 *
 * @return object $bundle
 */
function purge_item_form_load($form, &$form_state) {
  $bundle = new PurgeBundleBasic;
  $item_action = $form_state['values']['item_action'];
  // See what action is to be taken.
  $item_name = $form_state['values']['machine_name'];
  $item_type = $form_state['values']['item_type'];
  if ($item_action == 'add' || $item_action == 'clone') {
    switch ($item_type) {
      case 'target':
        $bundle->target[$item_name] = new PurgeItemTargetStatic();
        $item = $bundle->target[$item_name];
        break;
      case 'domain':
        $bundle->domain[$item_name] = new PurgeItemDomainStatic();
        $item = $bundle->domain[$item_name];
        break;
      case 'header':
        $bundle->header[$item_name] = new PurgeItemHeaderStatic();
        $item = $bundle->header[$item_name];
        break;
      case 'set':
        $bundle->set[$item_name] = new PurgeItemSet();
        $item = $bundle->set[$item_name];
        break;
    }
  }
  elseif ($item_action == 'edit') {
    $item = $bundle->{$item_type}[$item_name];
  }
  // First process the basic item properties
  $item->name = check_plain($form_state['values']['name']);
  $item->description = check_plain($form_state['values']['description']);
  $item->enabled = $form_state['values']['enabled'];

  // Now for each item specific property
  switch ($item_type) {
    case 'target':
      $item->urls = preg_split('/$\R?^/m', $form_state['values']['urls']);
      break;
    case 'domain':
      $item->domains = preg_split('/$\R?^/m', $form_state['values']['domains']);
      break;
    case 'header':
      $headers = preg_split('/$\R?^/m', $form_state['values']['headers']);
      $item->headers = array();
      foreach ($headers as $header) {
        $header_key = trim(strstr($header, ":", true));
        $header_value = trim(strstr($header, ":"), " :\t");
        $item->headers[$header_key] = $header_value;
      }
      break;
    case 'set':
      // Loop through all item types
      foreach ($item->pointers as $pointer_type => $pointer) {
        // Get the item valuea from the form
        $items = $form_state['values'][$pointer_type . '_settings'];
        $items_enabled = array();
        // See if this was a single of multi field.
        if ($form['set'][$pointer_type][$pointer_type . '_settings']['#multiple'] == 0) {
          $item_name = $items;
          $item->{$pointer_type}[$item_name] = $bundle->{$pointer_type}[$item_name];
          $items_enabled = array($items);
        }
        else {
          // Multiple options form
          foreach ($items as $item_name => $item_selected) {
            // Check each item if it's selected
            if ($item_selected != '0') {
              // Point to set property to the bundle object
              $item->{$pointer_type}[$item_name] = $bundle->{$pointer_type}[$item_name];
              $items_enabled[] = $item_name;
            }
          }
        }
        // Set the pointers
        $item->pointers[$pointer_type] = $items_enabled;
      }
      break;
  }
  return $bundle;
}

/**
 * Helper function to check if a name exists in the current configuration.
 */
function purge_item_name_exists($item_name) {
  $error = 0;
  // Get a basic bundle.
  $bundle = new PurgeBundleBasic();
  foreach($bundle->type as $type_name => $type) {
    if ($type_name == $item_name) {
      $error = 1;
    }
    foreach($bundle->{$type_name} as $name => $item) {
      if ($item_name == $name) {
        $error = 1;
      }
    }
  }
  return $error;
}

