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
core_init();

// Start output
theme_load();

// Clean up
core_close();