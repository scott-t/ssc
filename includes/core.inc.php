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
 * Module error status: Forbidden
 */
define("SSC_ERROR_FORBIDDEN", 403);

/**
 * Module error status: Page not found
 */
define("SSC_ERROR_NOT_FOUND", 404);

/**
 * Module message type: Info
 */
define("SSC_MSG_INFO", 1);
/**
 * Module message type: Warning
 */
define("SSC_MSG_WARN", 2);
/**
 * Module message type: Error
 */
define("SSC_MSG_CRIT", 3);

/**
 * URL domain information.
 * @global string $ssc_site_url
 */
$ssc_site_url;

/**
 * File system path information.
 * @global string $ssc_site_path
 */
$ssc_site_path;

/**
 * Application settings array.
 * 
 * This array contains several sections relating to various parts of the application which are
 * generally set inside the appropriate settings file.  This includes database engine and login,
 * among with several other hard-coded program settings.  Other database stored settings may be
 * loaded into a sub-array with the key 'runtime'.
 * 
 * @see default.settings.inc.php
 * @global mixed $SSC_SETTINGS
 * 
 * 
 */
$SSC_SETTINGS;


/**
 * Clean up
 */
function core_close(){
	global $ssc_database;
	module_hook('close');
	unset($ssc_database);
}

/**
 * Retrieve the correct site configuration.  Sites can only be set on a (sub-)domain basis. 
 * 
 * @see default.settings.inc.php
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
	// Path settings
	global $ssc_site_url, $ssc_site_path;
	// App settings
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
	
	// Set theme path
	$SSC_SETTINGS['theme']['path'] = "$ssc_site_path/themes/{$SSC_SETTINGS['theme']['name']}";
	$SSC_SETTINGS['theme']['url'] = "$ssc_site_url/themes/{$SSC_SETTINGS['theme']['name']}";
	
	// Set referer if none present
	if (!isset($_SERVER['HTTP_REFERER']))
		$_SERVER['HTTP_REFERER'] = '';
		
}

/**
 * Set a cookie
 * @param string $name Cookie name
 * @param mixed $value Cookie value
 * @param int $timeout Offset from the current time to expire the cookie
 */
function core_cookie($name, $value, $timeout = 0){
	//static $cookie_val[] = '';
	core_debug(array('title'=>'setcookie','body'=>'For ' . ".".$_SERVER['HTTP_HOST'].", cookie $name = $value"));
	setcookie($name, $value, $timeout, "/", ($_SERVER['HTTP_HOST'] != "localhost" ? ".".$_SERVER['HTTP_HOST'] : false), false, true);
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
			core_frontend_init();
			break;
	
	}
}

/**
 * Start the display of the page by booting up the theme
 */
function core_frontend_init(){
	global $SSC_SETTINGS, $ssc_site_path, $ssc_database;

	// Include the language file
	$file = "$ssc_site_path/lang/" . $SSC_SETTINGS['lang']['tag'] . ".inc.php";

	if (file_exists($file))
		require_once $file;
	else
		core_die(array(
					'title' => 'Bad language',
					'body'  => "Language '" . $SSC_SETTINGS['lang']['tag'] . "' is currently not installed"
					));
	
	// Load up all enabled modules
	require_once "$ssc_site_path/includes/core.module.inc.php";
	module_load();
	
	// Check inputs
	core_magic_check();
	core_form_check();
	
	// Set up the theme
	require_once "$ssc_site_path/includes/core.theme.inc.php";
	$file = "{$SSC_SETTINGS['theme']['path']}/{$SSC_SETTINGS['theme']['name']}.theme.php";
	if (!file_exists($file))
		core_die(array(
					'title' => 'Bad theme',
					'body' => 'Specified theme ' . $SSC_SETTINGS['theme']['name'] . ' is not installed'
				));
				
}

/**
 * Generates a form based on the structure of the passed array
 * @param array $struc Array depicting form structure
 */
function core_generate_form(&$struct){
	global $ssc_site_url;
	// Check for core components
	if (!isset($struct['action'], $struct['method'], $struct['fields'], $struct['id']))
		return;
		
	// Begin output
	echo "<form action=\"", ($struct['action'] == '' ? '' : $ssc_site_url . $struct['action']), "\" method=\"$struct[method]\" id=\"$struct[id]\"";
	// Optional form enc-types
	if (isset($struct['enc'])){
		echo ' enctype="';
		switch ($struct['enc']){
		case 'multi':
			echo 'multipart/form-data';
			break;
		default:
			echo 'application/x-www-form-urlencoded';
			break;
		}
		echo '">';
	}else{
		echo '>';
	}
	
	// Parse the fieldsets
	foreach ($struct['fields'] as $fieldset){
		echo '<fieldset>';
		// Legend field
		if (isset($fieldset['legend'])){
			echo "<legend>$fieldset[legend]</legend>";
			unset($fieldset['legend']);
		}
		
		if (isset($struct['id'])){
			echo '<input type="hidden" name="form-id" value="', $struct['id'],'" />';
			unset($struct['id']);
		}
		
		foreach ($fieldset as $id => $s){
			if (!isset($s['type']))
				continue;
				
			// Display field types
			switch($s['type']){
			// Text inputs
			case 'text':
			case 'password':
				if (isset($s['label']))
					echo '<label for="', $id, '">', $s['label'], '</label>';
					
				echo '<input type="', $s['type'], '" id="', $id, '" name="' , 
					(isset($s['name']) ? $s['name'] : $id), '"',
					(isset($s['size']) ? ' size="' . $s['size'] . '"' : ''),
					(isset($s['maxlen']) ? ' maxlength="' . $s['maxlen'] . '"' : ''), 
					(isset($s['value']) ? ' value="' . $s['value'] . '"' : ''), ' />'; 
				break;
				
			// Buttons
			case 'submit':
			case 'button':
			case 'reset':
				echo '<input type="', $s['type'], '" id="', $id, '" name="' , 
					(isset($s['name']) ? $s['name'] : $id), '"', 
					(isset($s['value']) ? ' value="' . $s['value'] . '"' : ''), ' />';
					
			}
		}
			
		echo '</fieldset>';
	}
	
	echo '</form>';
}

/**
 * Checks if a form has been submitted and passes it off to the relevant module if present
 */
function core_form_check(){
	global $ssc_site_url;
	// Ensure form was submitted from ourself
	if (isset($_POST['form-id']) && strpos($_SERVER['HTTP_REFERER'], $ssc_site_url) === 0){
		// We have a submitted form sitting here
		$module = explode('-', $_POST['form-id']);
		module_hook('form_handler', $module[0]);
	}
}

/**
 * Stores a list of messages to show to the user
 * @param int $type Message importance level
 * @param string $msg Message to be stored
 */
function core_add_message($type, $msg){
	global $SSC_SETTINGS;
	$SSC_SETTINGS['runtime']['errorlist'][] = array('type' => $type, 'msg' => $msg);
}

/**
 * Undoes gpc_magic_quotes if present
 */
function core_magic_check(){
	static $done = false;
	if (!$done && get_magic_quotes_gpc()){
		array_walk($_POST, '_core_magic_check');
		array_walk($_GET, '_core_magic_check');
		array_walk($_COOKIE, '_core_magic_check');
		$done = true;
	}
}

/**
 * Strip the quotes from the specified string.  If an array is passed, will recursively strip.
 * @param string|array $item Item to strip from.
 * @private
 */
function _core_magic_check(&$item){
	static $sysbase = -1;
	if ($sysbase == -1)
		$sysbase = (ini_get('magic_quotes_sysbase') ? 2 : 1);
		
	if (is_array($item)){
		array_walk($item, '_core_magic_check');
	}
	else{
		if ($sysbase == 2)
			$item = stripslashes($item);
		else
			$item = str_replace("''","'",$item);
	}
	
}