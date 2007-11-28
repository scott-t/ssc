<?php
/**
 * @file
 * This file provides the bulk of the SSC core.
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Core initialization levels
 */
define('SSC_INIT_CONFIG', 1);
define('SSC_INIT_DATABASE', 2);
define('SSC_INIT_FULL', 3);


/**
 * Retrieve the correct site configuration.  Sites can only be set on a (sub-)domain basis. 
 */

function core_conf_file(){
	static $path = ''; echo $path;
	if ($path)
		return $path;						// So we don't go through this every timne

	$path = explode('.', $_SERVER['SERVER_NAME']);
	do{
		$filepath = implode('.', $path);
		unset($path[count($path)-1]);
		if($filepath == ''){
			$path = 'default';
			$filepath = 'default';
			break;
		}
	} while (!file_exists("./config/$filepath.settings.inc.php"));
	
	return "./config/$filepath.settings.inc.php";
}

/**
 * Initialize the SSC configuration
 */

function core_conf_init(){
	// Global environment information
	global $site_url, $site_path;
	
	// Global site configuration
	global $config;
	$config = array();
	
	// Global database settings
	global $db_config, $db_prefix;

	// Fill in environment information
	$site_url = "http://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['SCRIPT_NAME'], 0, -10);
	$site_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -10);

	// Get our configuration path
	$path = core_conf_file();
	if (file_exists($path)){
		include $path;
	}
	else {
		core_die(array(
			'title' => 'Upload Error',
			'body'  => 'It seems SSC was not successfully uploaded as some files are missing!'
			));
	}
		
}

/**
 * Initialize connection with the database
 */

function core_database_init(){
	global $database; 
}

/**
 * Displays fatal error messages
 */

function core_die($information){

}

/**
 * Initializes the environment.  An optional parameter is available to specify
 * how much to initialize.  All levels below the specified will be loaded automatically
 * 
 * @param $level Level to initialize core to
 */

function core_init($level = SSC_INIT_FULL){
	if ($level > SSC_INIT_FULL)	$level = SSC_INIT_FULL;
	
	for ($i = 1; $i <= $level; $i++){
		// Load up all previous levels
		_core_load($i);				 
	}
}

/**
 * Initialize a particular level of the core.
 * @private
 * @param $level Level of the core to initialize
 */

function _core_load($level){
	switch($level){
		
		case SSC_INIT_CONFIG:
			core_conf_init();
			break;
			
		case SSC_INIT_DATABASE:
			core_database_init();
			break;
			
		case SSC_INIT_FULL:
			break;
	
	
	}
}