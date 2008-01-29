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
define("_SSC_DEBUG", 1);
$ssc_execute_time = microtime(true);

// Begin application startup
include('./includes/core.inc.php');
ssc_init();

$page = ssc_execute();

theme_render($page);
ssc_debug_show();
// Clean up
ssc_close();