<?php
/**
 * File for executing cron jobs
 * @package SSC
 */

/**
 * Define if an included file is accessed through the core
 */
define("_VALID_SSC", 1);

define("SSC_CRON_MIN_TIME", 60 * 60 * 2);	// Minimum 2hr time

// Only load from internally
if (isset($_SERVER['REMOTE_ADDR']))
	die('Restricted access');

// Begin application startup
include('./includes/core.inc.php');
ssc_init(SSC_INIT_EXTENSION);

$lastrun = ssc_var_get("cron_last_run", 0);

// Run only if not up to hardcoded minimum per-run time
if ($lastrun < (time() - SSC_CRON_MIN_TIME))
	module_hook('cron');

ssc_var_set("cron_last_run", time());

ssc_close();