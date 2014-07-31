<?php

/**
 *  @file
 *   Provides administrative interface for the Purge module.
 */

// Load the purge.inc file
module_load_include('inc', 'purge', 'includes/purge');
// module_load_include('inc', 'purge', 'includes/defaults');

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
    // Make sure purgers are displayed first.
    $types = array_diff($types, array('purger'));
    array_unshift($types, 'purger');
  }
  // Loop through all types.
  foreach ($types as $type) {
    // fieldset for each type
    $form['purge'][$type] = array(
      '#type' => 'fieldset',
      '#title' => $bundle->type[$type]->name,
    );
    $form['purge'][$type]['description'] = array(
      '#type' => 'item',
      '#markup' => $bundle->type[$type]->description,
    );
    // Add link
    if (in_array(PURGE_ACCESS_FULL, $bundle->type[$type]->access)) {
      $form['purge'][$type]['add'] = array(
        '#type' => 'item',
        '#title' => l(t('Add @type', array('@type' => $bundle->type[$type]->name)), "admin/config/system/purge/add/{$type}"),
      );
    }

    $options = array();
    foreach ($bundle->{$type} as $item_name => $item) {
      // See what actions are available in the UI
      $actions = array();
      // Skip all system items.
      if (!in_array(PURGE_ACCESS_SYSTEM, $item->access)) {
        // Check if we'll only show an edit option.
        if (
          in_array(PURGE_ACCESS_FULL, $item->access) ||
          in_array(PURGE_ACCESS_ENABLE, $item->access) ||
          in_array(PURGE_ACCESS_RENAME, $item->access)
        ) {
          $actions[] = l(t('Edit'), "admin/config/system/purge/{$type}/{$item_name}/edit");
        }
        // else check if a view action is needed.
        elseif (in_array(PURGE_ACCESS_VIEW, $item->access)) {
          $actions[] = l(t('View'), "admin/config/system/purge/{$type}/{$item_name}/view");
        }

        // Enable/Disable actions
        if (in_array(PURGE_ACCESS_FULL, $item->access) || in_array(PURGE_ACCESS_ENABLE, $item->access)) {
          if ($item->enabled == 1) {
            $actions[] = l(t('Disable'), "admin/config/system/purge/{$type}/{$item_name}/disable");
          }
          else {
            $actions[] = l(t('Enable'), "admin/config/system/purge/{$type}/{$item_name}/enable");
          }
        }
        // Clone
        if (in_array(PURGE_ACCESS_FULL, $item->access) || in_array(PURGE_ACCESS_CLONE, $item->access)) {
          $actions[] = l(t('Clone'), "admin/config/system/purge/{$type}/{$item_name}/clone");
        }
        // Delete action is this is user provided object 
        if ($item->provider == 'user') {
          $actions[] = l(t('Delete'), "admin/config/system/purge/{$type}/{$item_name}/delete");
        }
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
 * Menu callback for the add item form.
 */
function purge_item_add_form($form, &$form_state, $item_type) {
  // Load the bundle.
  $bundle = new PurgeBundleBasic();
  // And load the example bundle.
  module_load_include('inc', 'purge', 'includes/defaults');
  $example_bundle = new PurgeBundleExample();
  $form = array();
  // Hidden field with item_type information for later processing.
  $form['item_type'] = array(
    '#type' => 'hidden',
    '#default_value' => $item_type,
  );

  // First check if this itme type is allowed to be added.
  if (!in_array(PURGE_ACCESS_FULL, $bundle->type[$item_type]->access)) {
    drupal_set_message(t('You are not permitted to add items of type @type.', array('@type' => $bundle->type[$item_type]->name)));
    drupal_goto('admin/config/system/purge');
    return;
  }
  // The header of the selecfield.
  $header = array(
    'name' => t('Name'),
    'description' => t('Description'),
    'provider' => t('Provided by'),
  );

  // The options.
  // First the option to create a new item.
  $options = array(
    'new' => array(
      'name' => t('New'),
      'description' => t('Start from scratch with an empty @type.', array('@type' => $bundle->type[$item_type]->name)),
      'provider' => t('You!'),
    ),
  );
  // Now include all built in examples.
  foreach ($example_bundle->{$item_type} as $item_name => $item) {
    $options[$item_name] = array(
      'name' => $item->name,
      'description' => $item->description,
      'provider' => t('Purge Examples'),
    );
  }

  $form['template_select'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#multiple' => FALSE,
    '#default_value' => 'new',
  );
  // Submit
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Select'),
  );

  return $form;
}

/**
  *  * Callback for the select button on the template form.
  *   */
function purge_item_add_form_submit($form, &$form_state) {
  if (isset($form_state['values']['template_select'])) {
    // New item selected.
    if ($form_state['values']['template_select'] == 'new') {
      $form_state['redirect'] = 'admin/config/system/purge/' . $form_state['values']['item_type'] . '/new/add';
    }
    // Example selected.
    else {
      $form_state['redirect'] = 'admin/config/system/purge/' . $form_state['values']['item_type'] .
        '/' . $form_state['values']['template_select'] . '/example';
    }
  }
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
      case 'purger':
        $item = new PurgeItemPurgerBasicUrl();
        break;
    }
    $item_name = '';
  }
  elseif ($action == 'clone') {
    $item = clone $bundle->{$item_type}[$item_name];
    $item->name = '';
    $item->description = '';
  }
  elseif ($action == 'example') {
    // Get the example bundle
    module_load_include('inc', 'purge', 'includes/defaults');
    $examples = new PurgeBundleExample();
    $item = clone $examples->{$item_type}[$item_name];
    // Set pointers for purgers.
    if ($item_type == 'purger') {
      foreach ($item->pointers as $pointer_type => $pointers) {
        foreach ($pointers as $pointer_name) {
          // Check if the bundle has the item.
          if (!isset($bundle->{$pointer_type}[$pointer_name])) {
            // Check look for it in the exampele bundle.
            if (isset($example->{$pointer_type}[$pointer_name])) {
              // add it to the bundle.
              $bundle->{$pointer_type}[$pointer_name] = clone $example{$pointer_type}[$pointer_name];
            }
          }
          // Link the item to the bundle object.
          $item->{$pointer_type}[$pointer_name] = $bundle->{$pointer_type}[$pointer_name];
        }
      }
    }
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
    '#description' => t('Disabled items will ignored during operations, even when included in purgers.'),
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
    case 'purger':
      $form['purger'] = array(
        '#type' => 'fieldset',
        '#title' => 'Purger configuration',
      );
      // Generate the set
      $table_header = array(
        'name' => t('Name'),
        'description' => t('Description'),
      );
      foreach ($item->pointers as $pointer_type => $pointer_names) {
        $rows = array();
        $default_value = array();
        $multiple = true;
        if ($pointer_type == 'handler' || $pointer_type == 'option') {
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
        $form['purger'][$pointer_type] = array(
          '#type' => 'fieldset',
          '#title' => $bundle->type[$pointer_type]->name,
          '#description' => $bundle->type[$pointer_type]->description,
        );
        $form['purger'][$pointer_type][$pointer_type . '_settings'] = array(
          '#type' => 'tableselect',
          '#header' => $table_header,
          '#options' => $rows,
          '#default_value' => $default_value,
          '#multiple' => $multiple,

        );
        // Check if this type has options.
        $options = array();
        if (isset($bundle->type[$pointer_type]->options)) {
          // Now get the form items for the options.
          $form['purger'][$pointer_type][$pointer_type . '_options'] = array(
            '#type' => 'fieldset',
            '#title' => $bundle->type[$pointer_type]->name . ' ' . t('Options'),
          );
          // Display the type options
          $options = $bundle->type[$pointer_type]->options;
          $all_item_options = array();
          foreach ($options as $option_name => $option) {
            // First display a form for all type specific options
            if ($options[$option_name]['context'] == 'type') {
            // If the purger has a value for this option
              if (isset($item->options[$pointer_type][$option_name])) {
                $option['purger_value'] = $item->options[$pointer_type][$option_name];
              }
              // Now generate a form for these options.
              $form['purger'][$pointer_type][$pointer_type . '_options'][$item_name . '_' . $option_name] =
                purge_option_form($option);
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
                  $this_item_options[$item_option_name]['purger_value'] = $item->options[$pointer_type][$item_option_name];
                }
              }
            }
            if (count($this_item_options) > 0) {
              $form['purger'][$pointer_type][$pointer_type . '_options'][$pointer_name] = array(
                '#type' => 'fieldset',
                '#title' => $bundle->{$pointer_type}[$pointer_name]->name . ' ' . t('Options'),
                '#states' => array(
                  'visible' => array(
                    ':input[name="' . $pointer_type . '_settings"]' => array('value' => $pointer_name),
                  ),
                ),
              );
              // Generate an option form.
              foreach ($this_item_options as $option_name => $option) {
                $form['purger'][$pointer_type][$pointer_type . '_options'][$pointer_name][$pointer_name . '_' . $option_name]
                  = purge_option_form($option);
              }
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
    if ($item_type == 'purger') {
      $form[$item_type]['options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Purger Options'),
        '#description' => t('These settings apply to this purger only.'),
      );
      foreach ($options as $option_name => $option) {
        if (isset($item->options['purger'][$option_name])) {
          $option['purger_value'] = $item->options['purger'][$option_name];
        }
        $form[$item_type]['options'][$item_type . '_' . $option_name] =  purge_option_form($option);
      }
    }
    else {
      $form[$item_type]['options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Default Options'),
        '#description' => t('These defaults can be overruled per purger.'),
      );
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
function purge_option_form($option) {
  $form = array(
    '#title' => $option['name'],
    '#description' => $option['description'],
  );
  if (isset($option['values'])) {
    $form['#type'] = 'select';
    $form['#options'] = $option['values'];
  }
  else {
    $form['#type'] = 'checkbox';
  }
  if (isset($option['purger_value'])) {
    $form['#default_value'] = $option['purger_value'];
  }
  else {
    $form['#default_value'] = $option['default_value'];
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
  if ($item_action == 'add' || $item_action == 'clone' || $item_action == 'example') {
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
      case 'purger':
        $bundle->purger[$item_name] = new PurgeItemPurgerBasicUrl();
        $item = $bundle->purger[$item_name];
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
    case 'purger':
      // Loop through all item types
      foreach ($item->pointers as $pointer_type => $pointer) {
        // Get the item valuea from the form
        $items = $form_state['values'][$pointer_type . '_settings'];
        $items_enabled = array();
        // See if this was a single of multi field.
        if ($form['purger'][$pointer_type][$pointer_type . '_settings']['#multiple'] == 0) {
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
        // Set the options
        if (isset($bundle->type[$pointer_type]->options)) {
          foreach ($bundle->type[$pointer_type]->options as $option_name => $option) {
            // Check if context is type
            if ($option['context'] == 'type') {
              $item->options[$pointer_type][$option_name] = $form_state['values'][$pointer_type . '_' . $option_name];
            }
            // or process as item specific options.
            elseif ($option['context'] == 'item') {
              $item_name = $form_state['values'][$pointer_type . '_settings'];
              $item->options[$pointer_type][$option_name] = $form_state['values'][$item_name . '_' . $option_name];
            }
          }
        }
      }
      // Now process the purger options
      foreach ($bundle->type['purger']->options as $option_name => $option) {
        if ($option['context'] == 'type') {
          $item->options['purger'][$option_name] = $form_state['values']['purger_' . $option_name];
        }
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

