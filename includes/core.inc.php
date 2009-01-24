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
define('SSC_INIT_EXTENSION', 3);
define('SSC_INIT_FULL', 4);

/**
 * Date formats
 */
define('SSC_DATE_SHORT', 'j-m-y');
define('SSC_DATE_LONG', 'l F jS Y');
define('SSC_DATE_MED', 'D, M d, Y');

/**
 * Time formats
 */
define('SSC_TIME_FULL', 'g:i:s A T');
define('SSC_TIME_SHORT', 'g:ia');

/**
 * Version numbers - Major trunk change
 */
define('SSC_VER_MAJOR', 3);
/**
 * Version numbers - Minor featureset update
 */
define('SSC_VER_MINOR', 0);
/**
 * Version numbers - Bugfix/stability update
 */
define('SSC_VER_REV', '0a');
/**
 * Version numbers - Major trunk change
 */
define('SSC_VER_FULL', SSC_VER_MAJOR . '.' . SSC_VER_MINOR . '.' . SSC_VER_REV);
define('SSC_VER_UA', 'SSC/' . SSC_VER_FULL);

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
 * URL domain information.  Will never contain a terminating '/'.
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
 * Application variables.  Stored in a DB and read in at each page load
 */
$SSC_VARS;

/**
 * Clean up
 */
function ssc_close(){
	global $ssc_database;
	
	if (function_exists('module_hook'))
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
	
	if ($path[0] == 'www')
		array_shift($path);
		
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

	$ssc_site_path = substr($_SERVER['SCRIPT_FILENAME'], 0, -10);

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

	// Fill in environment information
	$ssc_site_url = $_SERVER['SERVER_NAME'];
	if ($SSC_SETTINGS['no-www'] || strpos($ssc_site_url,"www.")===0)
		$ssc_site_url = "http://" . $ssc_site_url . substr($_SERVER['SCRIPT_NAME'], 0, -10);
	else
		$ssc_site_url = "http://www." . $ssc_site_url . substr($_SERVER['SCRIPT_NAME'], 0, -10);

	ssc_debug(array(
			'title' => 'ssc_conf_init',
			'body'  => "Running from $ssc_site_url in path $ssc_site_path"
			));
	
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

		case SSC_INIT_EXTENSION:
			ssc_var_init();
			ssc_extension_init();
			break;
			
		case SSC_INIT_FULL:
			ssc_frontend_init();
			break;
	
	}
}

/**
 * Boot up extensions
 */
function ssc_extension_init(){
	global $ssc_site_path;
	// Load image libraries
	include_once "$ssc_site_path/includes/core.image.inc.php";
	
	// Load up all enabled modules
	require_once "$ssc_site_path/includes/core.module.inc.php";
	module_load();
	
	// Prepare environment
	module_handler_init();	
	
	// Check inputs
	ssc_magic_check();
	ssc_form_check();
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
	
	// Set up the theme
	require_once "$ssc_site_path/includes/core.theme.inc.php";
	$theme = ssc_var_get('theme_default', SSC_DEFAULT_THEME);	
	$file = "$ssc_site_path/themes/$theme/$theme.";
	if (!file_exists($file . 'theme.php') && !file_exists($file . 'info'))
		ssc_die(array(
					'title' => 'Bad theme',
					'body' => 'Specified theme ' . $theme . ' is not installed'
				));
				
	theme_get_info($theme);
	
	ssc_add_js("/includes/core.js");
}

/**
 * Generates a form based on the structure of the passed array
 * @param string $name Form name including 'module_' prefix
 * @param mixed $args,... Arguments to be passed to form generator
 */
function ssc_generate_form($name, $args = null){
	global $ssc_site_url;
	
	// Check for core components
	if (!function_exists($name))
		return;
		
	// Are there any args?
	if (isset($args)){
		$args = func_get_args();
		// Pop off the function name
		array_shift($args);
		$form = call_user_func_array($name, $args);	
	}
	else{
		$form = $name();
	}

	$form += array('#type' => 'form',
				   '#parent' => true,
				   'form-id' => array('#type' => 'hidden',
				   		 			  '#value' => $name),
				   '#formname' => str_replace('_', '-', $name));
	
	// Grab output
	$out = ssc_generate_html($form);
	return $out;

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
		if (module_hook('validate', $module[0])){
			module_hook('submit', $module[0]);
		}
	}
}

/**
 * Stores a list of messages to show to the user
 * @param int $type Message importance level
 * @param string $msg Message to be stored
 */
function ssc_add_message($type, $msg){
	if (!isset($_SESSION['message']))
		$_SESSION['message'] = array();
		
	ssc_debug(array('type'=>'add_message', 'body' => $msg));
	$_SESSION['message'][] =  array($type, $msg);
}

/**
 * Retrieves the messages to show to the user
 * @return array Array of messages accumulated on page load
 */
function ssc_get_message(){
	return isset($_SESSION['message']) ? $_SESSION['message'] : null;
}

/**
 * Cleares the message log
 */
function ssc_clear_message(){
	$_SESSION['message'] = array();
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
		if ($sysbase == 1)
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
	if (strpos($path, '://') !== false){
		$op['ext'] = true;
	}
	else{
		$path = $ssc_site_url . $path;
	}
	
	return '<a href="' . $path . '" ' . (!empty($op['attributes']) ? ssc_attributes($op['attributes']) : '') . '>' . ($op['html'] ? $title : check_plain($title)). '</a>';
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
function check_plain($text){
	return htmlspecialchars($text);
}

/**
 * Does translation on a string
 * @param string $text Text to get translation of
 * @param string $vars Variables to be replaced
 * @return string Translated equivalent
 */
function t($text, $vars = array()){
	if (empty($vars))
		return $text;
		
	foreach ($vars as $key => $val){
		switch ($key[0]){
		case '#':
			// Plaintext
			$vars[$key] = check_plain($val);
		case '!':
			break;
		}
	}
	return strtr($text, $vars);
}

function ssc_abbr($abbr, $expanded){
	return "<abbr title=\"$expanded\">$abbr</abbr>";
	}

/**
 * Called to trigger a 404
 */
function ssc_not_found(){
	header("HTTP/1.1 404 Not Found");
	$body = "404 Not Found";
	theme_render($body);
	ssc_close();
	exit;
}

/**
 * Called to trigger a 403 Forbidden
 */
function ssc_not_allowed(){
	header("HTTP/1.1 403 Forbidden");
	$body = "403 Forbidden";
	theme_render($body);
	ssc_close();
	exit;
}

/**
 * Called to redirect the current page
 */
function ssc_redirect($path = '', $response_code = 302){
	global $ssc_site_url;
	ssc_close();
	header("Location: $ssc_site_url$path", true, $response_code);
	exit;
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

		if (isset($info['mini_count']))
			return $info;
	
	case 'module':
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
 * Form processing
 * @param string $form_name Name of the form in the form of 'module_formname' representing the
 * 					function to call to generate said form
 *
function ssc_form_handler($form_name){

	//return ssc_generate_html($form_name());
}

/**
 * Display a structured array of html elements
 * @param array $structure Array of html elements and element properties
 */
function ssc_generate_html(&$structure){
	$out = '';
	
	// Get keys
	$keys = array_keys($structure);
	rsort($keys);

	if (isset($structure['#parent'])){
		// Generate the field content
		foreach ($structure as $tag => $value){
			if ($tag[0] == '#')
				continue;
			
			$value['#name'] = $tag;
			if (empty($value['#id']))
				$value['#id'] = $structure['#formname'] . "-$tag";
			$value['#formname'] = $structure['#formname'];
			$out .= ssc_generate_html($value);
			unset($structure[$tag]);
		}
		
		$structure['#value'] = $out;
		$out = '';
	}
	
	$hook = 'theme_render_' . $structure['#type'];
	if (function_exists($hook)){
		$out = $hook($structure);
	}
	else {
		switch ($structure['#type']){
		case 'text':
		case 'password':
		case 'file':
			$structure['#value'] = theme_render_input($structure);
			$out = theme_render_form_element($structure);
			break;
		case 'hidden':
		case 'submit':
		case 'reset':
			$out = theme_render_input($structure);
			break;
		default:
			$out = $structure['#value'];
		}
	}
	
	return $out;
}


/**
 * Runs page related queries to handle form submissions, etc
 */
function ssc_execute(){
	// Find the module responsible for the current URI
	return module_hook('content', $_GET['handler']);
}

/**
 * Get/set a javascript file to be loaded.
 * Seed with the jQuery library to begin with (included as default)
 * @param string $path Path to the JS file relative to the base
 * @return array Array containing JS paths
 */
function ssc_add_js($path = null){
	global $ssc_site_url;
	static $js = array(
		'3f22f5a3c5558c07f40887ba34a5f282' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js');
	
	if (isset($path)){
		$js[md5($path)] = $ssc_site_url . $path;
	}
	return $js;
}

/**
 * Get/set a CSS file to be loaded
 * @param string $path Path to the CSS file relative to the base
 * @param string $media Target media for the CSS file
 * @return array Array containing CSS paths
 */
function ssc_add_css($path = null, $media = 'all'){
	global $ssc_site_url, $ssc_site_path;
	static $css = array();
	
	if (isset($path) && !array_key_exists(md5($path), $css)){
		if (strpos($path, '.theme.css') === false){
			$css[md5($path)] = array($ssc_site_url . $path, $media);
		}
		else {
			$p = str_replace('.theme.css', '.css', $path);
			if (ssc_var_get('theme.rebuild', false) || !file_exists($ssc_site_path . $p)){
				$file = file_get_contents($ssc_site_path . $path);
				if (strpos($file, '$') !== false){
					$file = str_replace('$base_url$', $ssc_site_url, $file);
					$file = str_replace('$theme_url$', "$ssc_site_url/themes/" .ssc_var_get('theme_default', SSC_DEFAULT_THEME), $file);
				}
				
				file_put_contents($ssc_site_path . $p, $file);
				chmod($ssc_site_path . $p, 0644);
			}
			
			$css[md5($path)] = array($ssc_site_url . $p, $media);
					
		}
	}
	return $css;
}

/**
 * Get/set the page title
 * @param string $title Title to set the page to
 */
function ssc_set_title($title = null){
	static $t = 'SSC';
	
	if ($title)
		$t = $title;
	
	return $t;
}

/**
 * Loads up a library from the lib folder
 * @param string $lib Library name to load
 * @return boolean True if library was included, false if otherwise
 */
function ssc_load_library($lib){
	global $ssc_site_path;
	static $loaded = array();
	
	// Check if already loaded
	if (array_search($lib, $loaded) === false){
		// Need to load
		$path = $ssc_site_path . "/lib/$lib.lib.php";
		if (!file_exists($path))
			return false;
			
		include $path;
		$loaded[] = $lib;
	}
	
	return true;
}