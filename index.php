<?php
/**
 * @file
 * The primary php file.  All page requests come via this file
 */

/**
 * Define if an included file is accessed through the core
 */
define("_VALID_SSC", 1);


include('./includes/core.inc.php');
core_init();