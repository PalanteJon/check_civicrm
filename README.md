# check_civicrm
This is a Nagios/Icinga check for CiviCRM 4.7+.  Use it to monitor remote instances of CiviCRM over the REST API.

### Usage

Place in /usr/lib/nagios/plugins.
Call with the command:
```
/usr/bin/php /usr/lib/nagios/plugins/check_civicrm.php
```

### Required arguments:
 * --hostname `<hostname>`
 * --protocol `<http|https>`
 * --site-key `<your site key>`
 * --api-key `<an API key>` must have system.check permission.  Use a key that has "Administer CiviCRM" permission, or better yet install https://github.com/MegaphoneJon/com.megaphonetech.monitoring
 
 ###Optional arguments:
 * --cms `<Drupal|Wordpress|Joomla|Backdrop|Drupal8>`
 * --rest-path `<path to REST endpoint>` NOTE: either --cms OR --path is required
 * --warning-threshold `<integer>` Checks that report back this severity_id or higher are considered Nagios/Icinga warnings.
 * --critical-threshold `<integer>` Checks that report back this severity_id or higher are considered Nagios/Icinga errors.
 * --show-hidden `<0|1>` If set to "0", checks that are hidden in the CiviCRM Status Console will be hidden from Nagios/Icinga.
 * --exclusions `<comma-separated list of checks, no spaces>` Any checks listed here will be excluded.  E.g. `--exclude checkPhpVersion,checkLastCron` will suppress the PHP version check and the cron check


e.g.:
```
php /usr/lib/nagios/plugins/check_civicrm.php mysite.org https drupal mysitekey myapikey
```
Named arguments are being phased in, and must be passed before the ordered arguments.  For now, there is an "exclude" option, which can tell Nagios/Icinga to ignore certain check results.  E.g.
```
php /usr/lib/nagios/plugins/check_civicrm.php --exclude=checkVersion_upgrade,checkVersion_patch mysite.org https drupal mysitekey myapikey
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
    "--exclude", "$exclude$",
    "--hostname", "$http_vhost$",
    "--protocol", "$protocol$",
    "--cms", "$cms$",
    "--site-key", "$crm_site_key$",
    "--api-key", "$crm_api_key$",
    "--show-hidden", 0,
    "--rest-path", "$rest_path$"
  ]
}
```
