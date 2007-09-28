<?php
/**
 * index.php
 * Root file for SSC.  All page requests should come through this file unless h4xx0r attempts.
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Used to ensure no other pages directly linked. All child pages will need to include the following at the top
 * <code>
 * <?php
 * defined('_VALID_SSC') or die('Restricted access');
 * ?>
 * </code>
 */
define('_VALID_SSC', 1);

/*
 * Check if installation needed
 * Redirect if need be...
 */
if(!file_exists('./conf/config.vars.php')){
	header("Location: http://" . $_SERVER['HTTP_HOST'] . str_replace( '/index.php','', strtolower( $_SERVER['PHP_SELF'] ) ). "/install/index.php" );
	exit();
}


/**
 * Set up core environment.
 * Sets up DB connection, includes configuration details, etc
 */
require_once('./conf/config.core.php'); 


/* To delete 
$mytimerstart = round(microtime(),4); */
 $mytimerstart = explode(" ",microtime()); 
 $mytimerstart = $mytimerstart[0] + $mytimerstart[1]; 

core_start();

/*
 * Ability to close site
 */
if(!$sscConfig_siteStatus && $_GET['cont'] != 'admin'){
	@require_once($sscConfig_absPath . '/conf/offline.php' );
	exit();
}

/**
 * Connect to selected theme
 */
require_once($sscConfig_themeAbs.'/index.php');

$database->cleanUp();
?>