<?php

/**
 * @file
 * Provides form for changing settings
 */


/**
 * Menu callback for purge admin settings.
 */
function purge_admin_settings_form() {
  $form = array();
  $form['purge_proxy_urls'] = array(
    '#type' => 'textfield',
    '#title' => t('Proxy Url'),
    '#default_value' => variable_get('purge_proxy_urls', 'http://localhost:80'),
    '#description' => t('Set this to the server IP or hostname that your proxy server(s) runs on (e.g. http://127.0.0.1:80). Use the optional method parameter for nginx support like http://192.168.1.76:8080/purge?purge_method=get For Acquia Hosting platforms configure like http://yoursite.com/?purge_method=ah Separate multiple servers with spaces.')
  );
  $form['purge_expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expire Integration'),
    '#default_value' => variable_get('purge_expire', 1),
    '#description' => t('Uncheck this box to disable integration with the Cache Expiration module.'),
  );
  return system_settings_form($form);
}

/**
 * Validate the Purge settings
 */
function purge_admin_settings_form_validate($form, &$form_state) {
  // Fill an array with the proxy server settings
  $proxy_urls = explode(' ', $form_state['values']['purge_proxy_urls']);
  // Check all proxy server urls
  foreach ($proxy_urls as $proxy_url) {
    // Check if it's a valid url
    if (!valid_url($proxy_url, TRUE)) {
      form_set_error('purge_proxy_urls', t('This is not a valid URL: !url.', array('!url' => $proxy_url)));
    }
    // Check for the Acquia Hosting environment setting
    if (strstr(parse_url($proxy_url, PHP_URL_QUERY), 'purge_method=ah')) {
      if (!isset($_SERVER['AH_SITE_NAME'])) {
        form_set_error('purge_proxy_urls', t('Acquia Hosting environment variable not found.'));
      }
    }
  }
}

/**
 * Menu callback for purge proxy configuration.
 */
function purge_proxy_form($formstate, $pid = NULL) {
  if ($pid) {
    $proxy = array();
    $proxy = purge_proxy_get_config($pid);
  }
  $form = array();
  $form['purge_proxy_general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Proxy Configuration'),
  );
  $form['purge_proxy_general']['purge_proxy_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    // '#default_value' => 
    '#description' => t('The unique machine readable name of this proxy configuration.'),
  );
  $form['purge_proxy_general']['purge_proxy_protocol'] = array(
    '#type' => 'select',
    // '#default_value' => '
    '#title' => t('Protocol'),
    '#description' => t('The request protocol the proxy server accepts purge requests in.'),
    '#options' => array(
      'http' => 'http',
      'https' => 'https',
    ),
  );
  $form['purge_proxy_general']['purge_proxy_host'] = array(
    '#type' => 'textfield',
    '#title' => t('Host'),
    // '#default_value' => 
    '#description' => t('The hostname or ip adress of the proxy server.'),
  );
  $form['purge_proxy_advanced'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Advanced Proxy Configuration'),
  );
  $form['purge_proxy_advanced']['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Proxy options'),
  );
  $form['purge_proxy_advanced']['options']['type'] = array(
    '#type' => 'select',
    '#title' => t('Request type'),
    '#description' => t('The type of http request issued. Either PURGE or GET.'),
    '#options' => array(
      'PURGE' => t('Purge'),
      'GET' => t('Get'),
    ),
  );
  $form['purge_proxy_advanced']['options']['parallel'] = array(
    '#type' => 'checkbox',
    '#title' => t('Parallel requests'),
    '#description' => t('Purge requests are send in parallel. Disable to send requests on at a time.'),
  );
  $form['purge_proxy_advanced']['options']['nonblocking'] = array(
    '#type' => 'checkbox',
    '#title' => t('Non blocking'),
    '#description' => t('Send non blocking purge requests.'),
  );
  $form['purge_proxy_advanced']['domains'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domains'),
  );
  $form['purge_proxy_advanced']['domains']['expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domains from Expire'),
    '#description' => t('Get domain names from the Expire module, if available.'),
  );
  $form['purge_proxy_advanced']['domains']['drupal'] = array(
    '#type' => 'checkbox',
    '#title' => t('Domain from Drupal'),
    '#description' => t('Get domain names from Drupal. Used as default when no other hostnames are provided.'),
  );
  $form['purge_proxy_advanced']['domains']['custom'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom domains'),
    '#description' => t('Provide custom domains to be purged with each request. One line each'),
  );
  $form['purge_proxy_advanced']['urls'] = array(
    '#type' => 'fieldset',
    '#title' => t('URLs'),
  );
  $form['purge_proxy_advanced']['urls']['prefix'] = array(
    '#type' => 'textfield',
    '#title' => t('URL prefix'),
    '#description' => t('This prefix will be inserted before the purge URL.'),
  );
  $form['purge_proxy_advanced']['urls']['wildcards'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow wildcards'),
    '#description' => t('Setting to indicate the proxy accepts wildcards. (Experimental)'),
  );
  $form['purge_proxy_advanced']['headers'] = array(
    '#type' => 'fieldset',
    '#title' => t('Headers'),
  );
  $form['purge_proxy_advanced']['headers']['acquia'] = array(
    '#type' => 'checkbox',
    '#title' => t('Acquia'),
    '#description' => t('Set headers for the Acquia hosting platforms'),
  );
  $form['purge_proxy_advanced']['headers']['fastly'] = array(
    '#type' => 'textfield',
    '#title' => t('Fastly API Key'),
    '#description' => t('Insert a header with your Fastly API key.'),
  );

  return $form;
}

/**
 * Function to retrieve proxy configurations
 */
function purge_proxy_get_config($proxy = 0, $status = 'ACTIVE', $view = 'FULL') {
  $proxy_confs = array();
 // $active_proxies = &drupal_static(__FUNCTION__);
 // if (!isset($all_proxies)) {
 // }
  //Query the proxy configuration table
  $query = db_select('purge_proxy', 'p');

  // Gather the fields needed
  $fields = array('name', 'pid', 'enabled', 'active', 'isdefault');
  if ($view = 'FULL') {
    $full_fields = array('proxy_protocol', 'proxy_host', 'proxy_port', 'proxy_wildcards', 'purge_type', 'host_expire', 
      'host_drupal', 'host_custom', 'url_prefix', 'headers');
    $fields = array_merge($fields, $full_fields);
  }
  $query->fields('p', $fields);

  // Set conditions
  if ($status = 'ACTIVE') {
    $query->condition('active', 1);
  }

  // Order, order, order.
  $query->orderBy('isdefault', 'DESC');
  $query->orderBy('active', 'DESC');
  $query->orderBy('enabled', 'DESC');

// print_r($query->__toString());
  // Execute query and parse results
  $result = $query->execute();
  foreach ($result as $record) {
    $proxy_confs[$record->pid]['pid'] = $record->pid;
    $proxy_confs[$record->pid]['name'] = $record->name;
    $proxy_confs[$record->pid]['enabled'] = $record->enabled;
    $proxy_confs[$record->pid]['active'] = $record->active;
    $proxy_confs[$record->pid]['isdefault'] = $record->isdefault;
  }
  // dprint_r($proxy_confs);
  return $proxy_confs;
}

/**
 * Menu callback for purge proxy configuration list
 */
function purge_proxy_list() {
  $rows = array();
  $header = array(t('Name'), t('Status'), t('Actions'));
  $proxies = purge_proxy_get_config(0,'ALL','LIST');
  foreach ($proxies as $proxy) {
    // Get the proxy configuration name
    $row = array();
    $row[] = $proxy['name'];
    // Generate the status and action fields 
    $status = '';
    $actions = array();

    if ($proxy['isdefault']) {
      $status = $status . t('Default, ');
    }
    else {
      $actions[] = l(t('Set Default'), "admin/config/system/purge/proxy/{$proxy['name']}/setdefault");
    }

    if ($proxy['enabled']) {
      $status = $status . t('Enabled, ');
      $actions[] = l(t('Disable'), "admin/config/system/purge/proxy/{$proxy['name']}/disable");
    }
    else {
      $status = $status . t('Disabled, ');
      $actions[] = l(t('Enable'), "admin/config/system/purge/proxy/{$proxy['name']}/enable");
    }

    if ($proxy['active']) {
      $status = $status . t('and Active');
      $actions[] = l(t('Deactivate'), "admin/config/system/purge/proxy/{$proxy['name']}/deactivate");
    }
    else {
      $status = $status . t('and Deactivated');
      $actions[] = l(t('Activate'), "admin/config/system/purge/proxy/{$proxy['name']}/activate");
    }

    // Add actions for edit and delete
    $actions[] = l(t('Edit'), "admin/config/system/purge/proxy/{$proxy['name']}/edit");
    $actions[] = l(t('Delete'), "admin/config/system/purge/proxy/{$proxy['name']}/delete");
    $row[] = $status;
    $row[] = implode(' | ', $actions);
    $rows[] = $row;
  }
  // Output themed table
  $output = theme('table', array('header' => $header, 'rows' => $rows));
  //print($output);
  return $output;
}
