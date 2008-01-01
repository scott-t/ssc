<?php
/**
 * This file provides the bulk of the SSC core.
 * @package SSC
 * @subpackage Core
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
	global $ssc_site_path;
	static $path;
	
	if (isset($path)){
		core_debug(array(
			'title' => 'core_conf_file',
			'body'  => "Path exists from static variable - shortcutting..."
			));
		return $path;						// So we don't go through this every timne
	}

	$path = explode('.', $_SERVER['SERVER_NAME']);
	do{
		$filepath = implode('.', $path);
		unset($path[count($path)-1]);
		if($filepath == ''){
			$path = 'default';
			$filepath = 'default';
			break;
		}
	} while (!file_exists("$ssc_site_path/config/$filepath.settings.inc.php"));
	
	return "$ssc_site_path/config/$filepath.settings.inc.php";
}

/**
 * Initialize the SSC configuration
 */

function core_conf_init(){
	/**
	 * @global string $ssc_site_url URL information
	 */
	global $ssc_site_url;
	
	/**
	 * @global string $ssc_site_path Absolute path information
	 */
	global $ssc_site_path;
	
	/**
	 * Application settings array
	 * @global mixed $SSC_SETTINGS Mixed array containing environment settings
	 */
	global $SSC_SETTINGS;

	$SSC_SETTINGS = array();
	
	// Fill in environment information
	$ssc_site_url = "http://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['SCRIPT_NAME'], 0, -10);
	$ssc_site_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -10);

	core_debug(array(
			'title' => 'core_conf_init',
			'body'  => "Running from $ssc_site_url in path $ssc_site_path"
			));
	
	// Get our configuration path
	$path = core_conf_file();
	if (file_exists($path)){
		core_debug(array(
					'title' => 'core_conf_init',
					'body'  => "Loading configuration $path"
					));
		include_once $path;
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
	global $ssc_site_path, $ssc_database, $SSC_SETTINGS;

	// Check if the database engine is available
	if (!file_exists("$ssc_site_path/includes/database.".$SSC_SETTINGS['database']['engine'].".inc.php")){
		core_die(array(
			'title' => 'Installation Error',
			'body'  => 'The specified database engine '.$SSC_SETTINGS['database']['engine'].' is not available.'
			));
		return;
	}
	
	// Load database engine
	core_debug(array(
				'title' => 'core_database_init',
				'body'  => "Loading database engine ".$SSC_SETTINGS['database']['engine']
				));
				
	include_once "$ssc_site_path/includes/core.database.inc.php";
	include_once "$ssc_site_path/includes/database.".$SSC_SETTINGS['database']['engine'].".inc.php";
	
	// Create database object
	$ssc_database = new $SSC_SETTINGS['database']['engineclass']();
	
	$ssc_database->set_query("UPDATE blah SET %s = %s", "p1\'s%_Afl", "p2");
	$ssc_database->query(); 
	
	
}

/**
 * Displays fatal error messages
 * @param array $information Formatted array containing title and body keys with
 * 			reason for dieing
 */

function core_die($information){
	echo $information['body'],'<br />';
	core_debug($information);
	core_debug_show();
	
	exit (1);
}

/**
 * Keep track of debug messages
 * @param array $information Formatted array containing title and body keys with
 * 			reason for dieing
 */

function core_debug($information){
	global $ssc_debug, $ssc_execute_time;
	
	if (defined("_SSC_DEBUG")){
		if(!isset($ssc_debug['count']))
			$ssc_debug['count'] = 0;
			
		$ssc_debug['message'][$ssc_debug['count']] = $information;
		$ssc_debug['message'][$ssc_debug['count']]['time'] = round(microtime(true) - $ssc_execute_time, 4); 
		$ssc_debug['count']++;
	}
}

function core_debug_show(){
	global $ssc_debug;
	if (defined("_SSC_DEBUG")){
		echo '<table>';
		for ($i = 0; $i < $ssc_debug['count']; $i++){
			echo "<tr><td>",$ssc_debug['message'][$i]['time'],"</td><td>",$ssc_debug['message'][$i]['title'],"</td><td>",$ssc_debug['message'][$i]['body'],"</td></tr>";
		}
		echo '</table>';
	}
}

/**
 * Initializes the environment.  An optional parameter is available to specify
 * how much to initialize.  All levels below the specified will be loaded automatically
 * 
 * @param int $level Level to initialize core to
 */

function core_init($level = SSC_INIT_FULL){
	if ($level > SSC_INIT_FULL)	$level = SSC_INIT_FULL;
	
	for ($i = 1; $i <= $level; $i++){
		// Load up all previous levels
		core_debug(array(
					'title' => 'core_init',
					'body'  => "Initializing core level $i"
					));
		_core_load($i);				 
	}
}

/**
 * Initialize a particular level of the core.
 * @private
 * @param int $level Level of the core to initialize
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