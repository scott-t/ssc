<?php
/**
 * The primary php file.  All page requests come via this file
 * @package SSC
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
error_reporting(0);
// Begin application startup
include('./includes/core.inc.php');
ssc_init();

$page = ssc_execute();
theme_render($page);

// Clean up
ssc_close();
ssc_debug_show();