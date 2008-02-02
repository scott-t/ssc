<?php
/**
 * The primary php file.  All page requests come via this file
 * @package SSC
 */

setlocale(LC_ALL, "en_AU.utf8");

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

// Clean up
ssc_close();

ssc_debug_show();