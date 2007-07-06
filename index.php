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

/* Parse our UIR
 * Takes the mod_rewritten uri and changes it to index.php?q=child/path
 * Sets first var as $_GET['cont'] and second as $_GET['sub']
 * All subsequent as $_GET[%i] = %i+1
 */
if(isset($_GET['q'])){
$tmp = explode('/',$_GET['q']);								//split up the "path"
}
if(isset($tmp[0]) && $tmp[0]!=''){							//set up what content to display. used for titles, etc
	$_GET['cont'] = str_replace('-',' ',$tmp[0]);
}else{
	$_GET['cont'] = 'home';
}

if(isset($tmp[1]) && $tmp[1] != ''){						//sub  = some minor details (page number, page id, etc)
	$_GET['sub'] = $tmp[1];
}

$i = 2;														//remainders
while(isset($tmp[$i],$tmp[$i+1])){
	$_GET[$tmp[$i]] = $tmp[$i+1];
	$i+=2;
}

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
?>