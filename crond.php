<?php
/**
 * File for executing cron jobs
 * @package SSC
 * @subpackage Core
 */

/**
 * Define if an included file is accessed through the core
 */
define("_VALID_SSC", 1);

define("SSC_CRON_MIN_TIME", 60 * 59);	// Minimum 1hr time

// Only load from internally
if (isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['argv']))
	die('Restricted access');

$sites = glob('./config/*.settings.inc.php');

if ($sites === false)
	die('Restricted access');

include('./includes/core.inc.php');
	
foreach($sites as $site) {
	$site = str_replace(".settings.inc.php", "", $site);
	$site = substr($site, 9);
	$_SERVER['SERVER_NAME'] = $site;

	if ($site == 'default')
		continue;

	// Begin application startup
	ssc_init(SSC_INIT_EXTENSION);

	$lastrun = ssc_var_get("cron_last_run", 0);
	$now = time();

	// Run only if not up to hardcoded minimum per-run time
	if ($lastrun < ($now - SSC_CRON_MIN_TIME))
		module_hook('cron');

	ssc_var_set("cron_last_run", $now);

	ssc_close();
}
