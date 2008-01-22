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
//define("SSC_ERROR_FORBIDDEN", 403);

/**
 * Module error status: Page not found
 */
//define("SSC_ERROR_NOT_FOUND", 404);

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
 * Site title
 * @global string $ssc_title
 */
$ssc_title;

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
 * Application variables.  Stored in a DB and read in at each page load
 */
$SSC_VARS;

/**
 * Clean up
 */
function ssc_close(){
	global $ssc_database;
	module_hook('close');
	unset($ssc_database);
}

/**
 * Retrieve the correct site configuration.  Sites can only be set on a (sub-)domain basis. 
 * 
 * @see default.settings.inc.php
 */

function ssc_conf_file(){
	global $ssc_site_path;
	static $path;
	
	if (isset($path)){
		ssc_debug(array(
			'title' => 'ssc_conf_file',
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

function ssc_conf_init(){
	// Path settings
	global $ssc_site_url, $ssc_site_path;
	// App settings
	global $SSC_SETTINGS;

	$SSC_SETTINGS = array();
	
	// Fill in environment information
	$ssc_site_url = "http://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['SCRIPT_NAME'], 0, -10);
	$ssc_site_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -10);

	ssc_debug(array(
			'title' => 'ssc_conf_init',
			'body'  => "Running from $ssc_site_url in path $ssc_site_path"
			));
	
	// Get our configuration path
	$path = ssc_conf_file();
	if (file_exists($path)){
		ssc_debug(array(
					'title' => 'ssc_conf_init',
					'body'  => "Loading configuration $path"
					));
		include_once $path;
	}
	else {
		ssc_die(array(
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
function ssc_cookie($name, $value, $timeout = 0){
	//static $cookie_val[] = '';
	ssc_debug(array('title'=>'setcookie','body'=>'For ' . ".".$_SERVER['HTTP_HOST'].", cookie $name = $value"));
	setcookie($name, $value, $timeout, "/", ($_SERVER['HTTP_HOST'] != "localhost" ? ".".$_SERVER['HTTP_HOST'] : false), false, true);
}

/**
 * Initialize connection with the database
 */

function ssc_database_init(){
	global $ssc_site_path, $ssc_database, $SSC_SETTINGS;

	// Check if the database engine is available
	if (!file_exists("$ssc_site_path/includes/database.".$SSC_SETTINGS['db-engine'].".inc.php")){
		ssc_die(array(
			'title' => 'Installation Error',
			'body'  => 'The specified database engine '.$SSC_SETTINGS['db-engine'].' is not available.'
			));
		return;
	}
	
	// Load database engine
	ssc_debug(array(
				'title' => 'ssc_database_init',
				'body'  => "Loading database engine ".$SSC_SETTINGS['db-engine']
				));
				
	include_once "$ssc_site_path/includes/core.database.inc.php";
	include_once "$ssc_site_path/includes/database.".$SSC_SETTINGS['db-engine'].".inc.php";
	
	// Create database object
	$ssc_database = new $SSC_SETTINGS['db-engineclass']();
	
}

/**
 * Displays fatal error messages
 * @param array $information Formatted array containing title and body keys with
 * 			reason for dieing
 */

function ssc_die($information){
	echo $information['body'],'<br />';
	ssc_debug($information);
	ssc_debug_show();
	
	exit (1);
}

/**
 * Keep track of debug messages
 * @param array $information Formatted array containing title and body keys with
 * 			reason for dieing
 */

function ssc_debug($information){
	global $ssc_debug, $ssc_execute_time;
	
	if (defined("_SSC_DEBUG")){
		if(!isset($ssc_debug['count']))
			$ssc_debug['count'] = 0;
			
		$ssc_debug['message'][$ssc_debug['count']] = $information;
		$ssc_debug['message'][$ssc_debug['count']]['time'] = round(microtime(true) - $ssc_execute_time, 4); 
		$ssc_debug['count']++;
	}
}

function ssc_debug_show(){
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

function ssc_init($level = SSC_INIT_FULL){
	if ($level > SSC_INIT_FULL)	$level = SSC_INIT_FULL;
	
	for ($i = 1; $i <= $level; $i++){
		// Load up all previous levels
		ssc_debug(array(
					'title' => 'ssc_init',
					'body'  => "Initializing core level $i"
					));
		_ssc_load($i);				 
	}
}

/**
 * Initialize a particular level of the core.
 * @private
 * @param int $level Level of the core to initialize
 */

function _ssc_load($level){
	switch($level){
		
		case SSC_INIT_CONFIG:
			ssc_conf_init();
			break;
			
		case SSC_INIT_DATABASE:
			ssc_database_init();
			break;
			
		case SSC_INIT_FULL:
			ssc_var_init();
			ssc_frontend_init();
			break;
	
	}
}

/**
 * Start the display of the page by booting up the theme
 */
function ssc_frontend_init(){
	global $SSC_SETTINGS, $ssc_site_path, $ssc_database;

	// Include the language file
	$file = "$ssc_site_path/lang/" . $SSC_SETTINGS['lang']['tag'] . ".inc.php";

	if (file_exists($file))
		require_once $file;
	else
		ssc_die(array(
					'title' => 'Bad language',
					'body'  => "Language '" . $SSC_SETTINGS['lang']['tag'] . "' is currently not installed"
					));
	
	// Load up all enabled modules
	require_once "$ssc_site_path/includes/core.module.inc.php";
	module_load();
	
	// Check inputs
	ssc_magic_check();
	ssc_form_check();
	
	// Set up the theme
	require_once "$ssc_site_path/includes/core.theme.inc.php";
	$theme = ssc_var_get('theme_default', SSC_DEFAULT_THEME);
	$file = "$ssc_site_path/themes/$theme/$theme.theme.php";
	if (!file_exists($file))
		ssc_die(array(
					'title' => 'Bad theme',
					'body' => 'Specified theme ' . $theme . ' is not installed'
				));
				
}

/**
 * Generates a form based on the structure of the passed array
 * @param array $struc Array depicting form structure
 */
function ssc_generate_form(&$struct){
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
function ssc_form_check(){
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
function ssc_add_message($type, $msg){
	global $SSC_SETTINGS;
	$SSC_SETTINGS['runtime']['errorlist'][] = array('type' => $type, 'msg' => $msg);
}

/**
 * Undoes gpc_magic_quotes if present
 */
function ssc_magic_check(){
	static $done = false;
	if (!$done && get_magic_quotes_gpc()){
		array_walk($_POST, '_ssc_magic_check');
		array_walk($_GET, '_ssc_magic_check');
		array_walk($_COOKIE, '_ssc_magic_check');
		$done = true;
	}
}

/**
 * Strip the quotes from the specified string.  If an array is passed, will recursively strip.
 * @param string|array $item Item to strip from.
 * @private
 */
function _ssc_magic_check(&$item){
	static $sysbase = -1;
	if ($sysbase == -1)
		$sysbase = (ini_get('magic_quotes_sysbase') ? 2 : 1);
		
	if (is_array($item)){
		array_walk($item, '_ssc_magic_check');
	}
	else{
		if ($sysbase == 2)
			$item = stripslashes($item);
		else
			$item = str_replace("''","'",$item);
	}
	
}

/**
 * Retrieve a stored application variable
 * @param string $name Variable name
 * @param mixed $default Default value if variable not yet set
 */
function ssc_var_get($name, $default){
	global $SSC_VARS;
	return isset($SSC_VARS[$name]) ? $SSC_VARS[$name] : $default;
}

/**
 * Set a variable for the application
 * @param string $name Variable name
 * @param mixed $value Value to be stored
 */
function ssc_var_set($name, $value){
	global $SSC_VARS, $ssc_database, $SSC_SETTINGS;
	$SSC_VARS[$name] = $value;
	
	switch ($SSC_SETTINGS['db-engine']){
	case 'mysqli':
	case 'mysql':
		return $ssc_database->query("REPLACE INTO #__variable (id, value) VALUES ('%s', '%s')", $name, serialize($value));
		break;
	default:
		return false;
	}
}

/**
 * Initializes the application variables
 */
function ssc_var_init(){ 
	global $SSC_VARS, $ssc_database;
	$SSC_VARS = array();
	
	$result = $ssc_database->query("SELECT id, value FROM #__variable");
	while($data = $ssc_database->fetch_object($result)){
		$SSC_VARS[$data->id] = unserialize($data->value);
	}
}

/**
 * Builds a link.
 * 
 * @param string $title Text for use as title of link
 * @param string $path Path relative to base of fully HTTP url
 * @param array $op Options for formatting the link
 * @return string Completed HTML link
 */
function l($title, $path, $op = array()){
	global $ssc_site_url;
	
	// Defaults
	$op += array('abs' => false,
				 'html' => false); 
	
	// Are we an active link?
	if ($path == $_GET['q']){
		if(isset($op['attributes']['class']))
			$op['attributes']['class'] .= ' active';
		else
			$op['attributes']['class'] = 'active';
	}

	// Check for absolute path
	if (strpos('://', $path) !== false){
		$op += array('ext' => true);
	}
	
	return '<a href="' . $path . '" ' . core_attributes($op['attributes']) . '>' . ($op['html'] ? $title : do_plain($text)). '</a>';
}

/**
 * Builds up the attribute list for a html element
 * 
 * @param array $attrib Array containing attribute=>value pairs
 * @return string XHTML compliant key="value" ... list
 */
function ssc_attributes($attrib){
	$ret = '';
	foreach($attrib as $a => $v){
		$ret .= " $a=\"$v\"";
	}
	
	return $ret;
}

/**
 * Checks the supplied parameter for plain-text only
 */
function do_plain($text){
	return htmlspecialchars($text);
}

/**
 * Does translation on a string
 * @param string $text Text to get translation of
 * @return string Translated equivalent
 */
function t($text){
	return $text;
}

/**
 * Called to trigger a 404
 */
function ssc_not_found(){
	header("HTTP/1.1 404 Not Found");
	
}

/**
 * Called to trigger a 403 Forbidden
 */
function ssc_not_allowed(){
	header("HTTP/1.1 403 Forbidden");
	
}

/**
 * Returns the content of a ini-formatted configuration file.
 * 
 * @param string $type Configuration file type.  Used to depict required fields
 * @param string $path Path to ini file
 */
function ssc_parse_ini_file($type, $path){
	// Check for valid path
	if (!file_exists($path))
		return;
		
	// Load in data
	$info = parse_ini_file($path);

	$info += array(	'dependancies' => '',
					'package' => '');
	
	if (!isset($info['name'], $info['version'], $info['description']))
		return;
	switch ($type){
	case 'theme':
		if (isset($info['head_count'], $info['title_count'], $info['side_count'], $info['foot_count']))
			return $info;
	}
	return;
}

/**
 * Returns an ISO code for language
 * @return string XHTML tag friendly tag value
 */
function ssc_lang(){
	return ssc_var_get('lang', 'en');
}

/**
 * Sets the site title
 * @param string $title New title
 */
function ssc_set_title($title){
	global $ssc_title;
	$ssc_title = $title;
}