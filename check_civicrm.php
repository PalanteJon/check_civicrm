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
 * /usr/bin/php /usr/lib/nagios/plugins/check_civicrm.php $HOSTADDRESS$ $_HOSTHTTP$ $_HOSTCMS$ $_HOSTSITE_KEY$ $_HOSTAPI_KEY$ cron
 * and
 * /usr/bin/php /usr/lib/nagios/plugins/check_civicrm.php $HOSTADDRESS$ $_HOSTHTTP$ $_HOSTCMS$ $_HOSTSITE_KEY$ $_HOSTAPI_KEY$ version
 *
 * in the host definition, provide the following custom variables:
 * _http      [http|https]
 * _cms       [drupal|joomla|wordpress]
 * _site_key  {your site key from settings.php}
 * _api_key   {an api key set on the civicrm_contact row corresponding to an admin user}
 */

$prot = ($argv[2] == 'https') ? 'https' : 'http';
$api_key = $argv[5];
$site_key = $argv[4];
$host_address = $argv[1];

switch (strtolower($argv[3])) {
  case 'joomla':
    $path = 'administrator/components/com_civicrm/civicrm';
    break;

  case 'wordpress':
    $path ='wp-content/plugins/civicrm/civicrm';
    break;

  case 'drupal':
  default:
    $path = 'sites/all/modules/civicrm';
}

echo "hi";
systemCheck($prot, $host_address, $path, $site_key, $api_key);

function systemCheck($prot, $host_address, $path, $site_key, $api_key) {
  echo "hi2";
    $result = file_get_contents("$prot://$host_address/$path/extern/rest.php?entity=system&action=check&key=$site_key&api_key=$api_key&json=1");

    $a = json_decode($result, true);

    if ($a["is_error"] != 1 && is_array($a['values'])) {
      $exit = 0;

      $message = array();
      foreach ($a["values"] as $attrib) {

        // first check for missing info
        $neededKeys = array(
          'title' => true,
          'message' => true,
          'name' => true,
        );
        if (array_intersect_key($neededKeys, $attrib) != $neededKeys) {
          $message[] = 'Missing keys: ' . implode(', ', array_diff($neededKeys, array_intersect_key($neededKeys, $attrib))) . '.';
          $exit = 3;
          continue;
        }

        $message[] = filter_var($attrib['title'], FILTER_SANITIZE_STRING) . ': ' . filter_var($attrib['message'], FILTER_SANITIZE_STRING);

        // temporarily setting this based upon message key
        // future versions of CiviCRM are likely to send severity
        switch ($attrib['name']) {
          // warnings
          case checkMysqlTime:
            $exit = ($exit > 1) ? $exit : 1;
            break;

          // critical
          case checkDebug:
          case checkOutboundMail:
          case checkLogFileIsNotAccessible:
          case checkUploadsAreNotAccessible:
          case checkDirectoriesAreNotBrowseable:
          case checkFilesAreNotPresent:
            $exit = ($exit > 2) ? $exit : 2;
            break;

          // assuming all others are warning
          default:
            $exit = ($exit > 1) ? $exit : 1;
        }
      }
      echo implode(' / ', $message);
      exit($exit);
    }
    echo 'Unknown error';
    exit(3);
}
