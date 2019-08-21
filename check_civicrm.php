#!/usr/bin/php
<?php

/**
 * Copyright 2014-2015 AGH Strategies, LLC
 * Released under the Affero GNU Public License version 3
 * but with NO WARRANTY: neither the implied warranty of merchantability
 * nor fitness for a particular purpose
 *
 * Place in /usr/lib/nagios/plugins
 *
 * Call with the commands:
 * /usr/bin/php /usr/lib/nagios/plugins/check_civicrm.php $HOSTADDRESS$ $_HOSTHTTP$ $_HOSTCMS$ $_HOSTSITE_KEY$ $_HOSTAPI_KEY$ $SHOW_HIDDEN$ $WARNING_THRESHOLD$ $CRITICAL_THRESHOLD$
 *
 * in the host definition, provide the following custom variables:
 * _http      [http|https]
 * _cms       [drupal|joomla|wordpress|backdrop]
 * _site_key  {your site key from settings.php}
 * _api_key   {an api key set on the civicrm_contact row corresponding to an admin user}
 * _show_hidden |1|0|
 */

$prot = ($argv[2] == 'https') ? 'https' : 'http';
$api_key = $argv[5];
$site_key = $argv[4];
$host_address = $argv[1];
// $show_hidden will evaluate to true unless it's a zero.
$show_hidden = isset($argv[6]) ? $argv[6] : TRUE;
$warning_threshold = isset($argv[7]) ? $argv[7] : 2;
$critical_threshold = isset($argv[8]) ? $argv[8] : 4;

switch (strtolower($argv[3])) {
  case 'joomla':
    $path = 'administrator/components/com_civicrm/civicrm/extern/rest.php';
    break;

  case 'wordpress':
    $path = 'wp-json/civicrm/v3/rest';
    break;

  case 'backdrop':
    $path = 'modules/civicrm/extern/rest.php';
    break;

  case 'drupal8':
    $path = 'libraries/civicrm/extern/rest.php';
    break;

  case 'drupal':
  default:
    $path = 'sites/all/modules/civicrm/extern/rest.php';
}

systemCheck($prot, $host_address, $path, $site_key, $api_key, $show_hidden, $warning_threshold, $critical_threshold);

function systemCheck($prot, $host_address, $path, $site_key, $api_key, $show_hidden, $warning_threshold, $critical_threshold) {
  $options = array(
    'http' => array(
      'header'  => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent: CiviMonitor\r\n",
      //'method'  => 'POST',
      //'content' => http_build_query($request),
    ),
  );
  $context  = stream_context_create($options);
  $result = file_get_contents("$prot://$host_address/$path?entity=system&action=check&key=$site_key&api_key=$api_key&json=1&version=3", FALSE, $context);

  $a = json_decode($result, TRUE);
  if ($a["is_error"] != 1 && is_array($a['values'])) {
    $exit = 0;

    $message = array();

    $max_severity = 0;
    foreach ($a["values"] as $attrib) {

      // first check for missing info
      $neededKeys = array(
        'title' => TRUE,
        'message' => TRUE,
        'name' => TRUE,
      );
      if (array_intersect_key($neededKeys, $attrib) != $neededKeys) {
        $message[] = 'Missing keys: ' . implode(', ', array_diff($neededKeys, array_intersect_key($neededKeys, $attrib))) . '.';
        $exit = 3;
        continue;
      }
      // Skip this item if it's hidden and we're hiding hidden items
      if ($attrib['is_visible'] == 0 && !$show_hidden) {
        continue;
      }
      // Skip this item if it doesn't meet the warning threshold
      if ($attrib['severity_id'] < $warning_threshold) {
        continue;
      }
      $message[] = filter_var($attrib['title'], FILTER_SANITIZE_STRING) . ': ' . filter_var($attrib['message'], FILTER_SANITIZE_STRING);

      // temporarily setting this based upon message key
      // future versions of CiviCRM are likely to send severity
      if ($attrib['severity_id'] >= $warning_threshold) {
        $max_severity = max(1, $max_severity);
      };
      if ($attrib['severity_id'] >= $critical_threshold) {
        $max_severity = max(2, $max_severity);
      };

    }
    echo implode(' / ', $message);
    exit($max_severity);
  }
  if ($a["is_error"] == 1) {
    echo $a['error_message'];
    exit(2);
  }
  echo 'Unknown error';
  exit(3);
}
