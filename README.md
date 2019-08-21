# check_civicrm
This is a Nagios/Icinga check for CiviCRM 4.7+.  Use it to monitor remote instances of CiviCRM over the REST API.

### Compatibility
The current version of this plugin supports the new WordPress REST interface, so it will not monitor WordPress sites earlier than CiviCRM version 5.18.0.  Download a version prior to August 2019 to support all versions of CiviCRM from 4.7 onward (including, for the foreseeable future, current versions of CiviCRM on WordPress).

### Usage
This plugin takes a set of ordered arguments:
```
check_civicrm.php hostname protocol cms site_key api_key
```

e.g.:
```
php /usr/lib/nagios/plugins/check_civicrm.php mysite.org https drupal mysitekey myapikey
```

### Permissions
By default, the API key in Icinga must correspond to a user with the **Administer CiviCRM** permission.  Consider installing my [monitoring extension](https://github.com/MegaphoneJon/com.megaphonetech.monitoring) which defines a new permission, **CiviCRM Remote Monitoring**, which allows an API key to be defined that has access only to remote monitor and nothing else.

### Icinga CheckCommand
Here is an Icinga2 CheckCommand that supports this plugin:
```js
object CheckCommand "civicrm" {
  command = [
    "/usr/bin/php",
    PluginDir + "/check_civicrm.php",
    "$http_vhost$",
    "$protocol$",
    "$cms$",
    "$crm_site_key$",
    "$crm_api_key$",
    0
  ]
}
```
