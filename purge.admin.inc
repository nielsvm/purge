<?php

/**
 * @file
 * Provides administrative interface for the Purge module.
 */


/**
 * Menu callback for purge admin settings.
 */
function purge_admin_settings_form() {
  $form = array();
  $form['purge_expire'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expire Integration'),
    '#default_value' => variable_get('purge_expire', 1),
    '#description' => t('Uncheck this box to disable integration with the Cache Expiration module.'),
  );
  return system_settings_form($form);
}

/**
 * Menu callback for purge proxy configuration.
 */
function purge_proxy_form($pid = 1) {
  $proxy = array();
  // Load the proxy configuration for editing.
  $proxy = purge_proxy_get_config($pid, 'FULL', 'FULL_SYSTEM');
  dprint_r($proxy);
  $form = array();
  $form['purge_proxy_general'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Proxy Configuration'),
  );
  $form['purge_proxy_general']['use_base_url'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the base URL provided by Drupal'),
    //'#default_value' =>
  );
  $form['purge_proxy_general']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $proxy[$pid]['name'],
    '#description' => t('The unique machine readable name of this proxy configuration.'),
    '#required' => TRUE,
  );
  $form['purge_proxy_general']['protocol'] = array(
    '#type' => 'select',
    '#title' => t('Protocol'),
    '#description' => t('The request protocol the proxy server accepts purge requests in.'),
    '#options' => array(
      'http' => 'http',
      'https' => 'https',
    ),
  );
  $form['purge_proxy_general']['host'] = array(
    '#type' => 'textfield',
    '#title' => t('Host'),
    '#description' => t('The hostname or ip adress of the proxy server.'),
    '#required' => TRUE,
  );
  $form['purge_proxy_general']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('Port'),
    '#description' => t('The tcp port where the proxy is listening.'),
  );
  $form['purge_proxy_advanced'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Advanced Proxy Configuration'),
  );
  $form['purge_proxy_advanced']['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Options'),
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
  $form['purge_proxy_advanced']['headers']['custom'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom headers'),
    '#description' => t('Provide custom headers to be added to each request. One line each'),
  );


  return $form;
}

/**
 * Menu callback for purge proxy configuration list
 */
function purge_proxy_list() {
  $rows = array();
  $header = array(t('Name'), t('Status'), t('Actions'));
  $proxies = purge_proxy_get_config(0, 'LIST', 'ACTIVE');
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
