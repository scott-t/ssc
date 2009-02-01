<?php
/**
 * Set up the framework for working with module files
 * @package SSC
 * @subpackage Core
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Module enable status: Module is disabled
 */
define("SSC_MODULE_DISABLED", 0);

/**
 * Module enable status: Module is enabled and will be loaded on application startup
 */
define("SSC_MODULE_ENABLED", 1);

/**
 * Module enable status: Module is required for core operation
 */
define("SSC_MODULE_REQUIRED", 2);

/**
 * Module information.
 * @global array $SSC_MODULES
 */
$SSC_MODULES;

/**
 * Finds which module should handle the current navigation request
 */
function module_handler_init(){
	global $ssc_database;
	
	// Prepare application argument
	if (!isset($_GET['q']))
		$_GET['q'] = '';
	elseif (substr($_GET['q'], -1) == '/')
		$_GET['q'] = substr($_GET['q'], 0, -1);
	
	$result = $ssc_database->query("SELECT m.id, h.id hid, filename, path FROM #__module m, #__handler h WHERE h.handler = m.id AND '%s' LIKE CONCAT(path,'%%') ORDER BY path DESC LIMIT 1", $_GET['q']);
	if ($ssc_database->number_rows() == 0){
		ssc_not_found();
	}
	
	$data = $ssc_database->fetch_assoc($result);
	// Split path arguments		
	$_GET['path'] = $data['path'];
	if ($_GET['path'] != '') $_GET['path'] = '/' . $_GET['path'];
	$_GET['param'] = substr($_GET['q'], strlen($data['path']));
	if(!empty($_GET['param']) && $_GET['param'][0] == '/')
		$_GET['param'] = substr($_GET['param'],1);
	$_GET['handler'] = $data['filename'];
	$_GET['path-id'] = $data['hid'];
$_GET['mod-id'] = $data['id'];
	
}

/**
 * Call a hook on any loaded modules.  Specific modules may be hooked 
 * by passing the module name or names as an array to $modules
 * 
 * @param string $hook Hook to call
 * @param string|array $modules Module(s) to execute the hook on
 * @param array $args Optional arguments to send to the hook
 */
function module_hook($hook, $modules = NULL, $args = NULL){
	global $SSC_MODULES;
	
	$ret = array();
	
	ssc_debug(array('title'=>'module_hook', 'body'=>"Calling '$hook' hook on modules"));
	
	if (!isset($modules)){
		foreach ($SSC_MODULES as $value){
			$h = "$value[filename]_$hook";
			if (function_exists($h))
				$ret[] = call_user_func_array($h, $args);
		}
	}
	else {
		// Use suggested modules
		if (is_array($modules)){
			foreach ($modules as $value){
				$h = "$value[filename]_$hook";
				if (function_exists($h))
					$ret[]= call_user_func_array($h, $args);
			}
		}
		else {
			$h = "${modules}_$hook";
			if (function_exists($h))
				$ret = call_user_func_array($h, $args);
		}
	}
	
	// Flatten the results
	if (is_array($ret)){
		return array_merge_recursive($ret, array());
	}
	else{
		return $ret;
	}

}

/**
 * Retrieves the internal ID for the module
 * @param string $name Module short-name
 * @return int Module ID
 */
function module_id($name){
	global $SSC_MODULES;
	return (isset($SSC_MODULES[$name]) ? $SSC_MODULES[$name]['id'] : 0);
}

/**
 * Loop through and load up each module as needed
 */
function module_load(){
	global $ssc_site_path, $ssc_database, $SSC_MODULES;
	// Make sure we only run once to avoid excess HDD usage / include_once overhead
	static $has_run = 0;
	if ($has_run == 1)
		return;
		
	// Set up modules "superglobal"
	$SSC_MODULES = array();
	
	
	// Retrieve all enabled modules
	$result = $ssc_database->query("SELECT id, filename, weight FROM #__module WHERE status >= %d ORDER BY weight ASC", SSC_MODULE_ENABLED);

	// Load each module
	while ($data = $ssc_database->fetch_assoc($result)){
		$SSC_MODULES[$data['filename']] = $data;
		ssc_debug(array('title'=>'module_load','body'=>"Loading $data[filename].module.php"));
		include "$ssc_site_path/modules/$data[filename]/$data[filename].module.php";
	}

	// Initialise module
	module_hook("init");
	
	// Mark function as run
	$has_run = 1;
}
