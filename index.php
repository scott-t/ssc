<?php
/**
 * The primary php file.  All page requests come via this file
 * @package SSC
 * @subpackage Core
 */

/**
 * Define if an included file is accessed through the core
 */
define("_VALID_SSC", 1);

/**
 * Enable debug info
 */
define("_SSC_DEBUG", 0);
$ssc_execute_time = microtime(true);
error_reporting(E_ALL);

// App startup
include('./includes/core.inc.php');

// We don't need the front-end initialized for ajax requests
if (isset($_GET['ajax']) && ($_GET['ajax'] == 'y')) {
	ssc_init(SSC_INIT_EXTENSION);
	header("Content-type: application/x-javascript; charset=utf-8", true);
	
	// Hook in form validation as a core function
	if (isset($_GET['core']) && ($_GET['core'] == 'val-form')) {
		// Check the form target exists
		if (!isset($_GET['form']))
			exit;
		
		$target = str_replace("-", "_", $_GET['form']);
		if (function_exists($target))
			echo json_encode($target());
	}
	else {
		echo ssc_execute();
	}
}
else {
	ssc_init(SSC_INIT_FULL);
	$page = ssc_execute();
	theme_render($page);
}

// Clean up
ssc_close();
