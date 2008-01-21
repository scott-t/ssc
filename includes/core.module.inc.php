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
 * 
 * @return string File name of the module handling the request
 */
function module_find_handler(){
	global $ssc_database, $SSC_SETTINGS;
	
	// Prepare application argument
	if (!isset($_GET['q']))
		$_GET['q'] = '';
	elseif (substr($_GET['q'], -1) == '/')
		$_GET['q'] = substr($_GET['q'], 0, -1);
	
	$result = $ssc_database->query("SELECT #__module.id, filename, path FROM #__module, #__handler WHERE #__handler.id = #__module.id AND '%s' LIKE CONCAT(path,'%%') ORDER BY path DESC LIMIT 1", $_GET['q']);
	if ($ssc_database->number_rows() == 0){
		$SSC_SETTINGS['runtime']['handler_id'] = 0;
		$SSC_SETTINGS['runtime']['handler_name'] = 'module_error';
		$SSC_SETTINGS['runtime']['error'] = 404;
		return 'module_error';
	}
	
	$data = $ssc_database->fetch_assoc($result);
	$SSC_SETTINGS['runtime']['handler_id'] = $data['id'];
	$SSC_SETTINGS['runtime']['handler_name'] = $data['filename'];
	
	// Split path arguments		
	$_GET['path'] = $data['path'];
	$_GET['param'] = substr($_GET['q'], strlen($data['path'])+1);
	return $data['filename'];
	
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
	
	core_debug(array('title'=>'module_hook', 'body'=>"Calling '$hook' hook on modules"));
	
	if (!isset($modules)){
		foreach ($SSC_MODULES as $value){
			$h = "$value[filename]_$hook";
			if (function_exists($h))
				call_user_func($h, $args);
		}
	}
	else {
		// Use suggested modules
		if (is_array($modules)){
			foreach ($modules as $value){
				$h = "$value[filename]_$hook";
				if (function_exists($h))
					call_user_func($h, $args);
			}
		}
		else {
			$h = "${modules}_$hook";
			if (function_exists($h))
				call_user_func($h, $args);
		}
	}

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
	$result = $ssc_database->query("SELECT name, filename, weight FROM #__module WHERE status >= %d ORDER BY weight ASC", SSC_MODULE_ENABLED);

	// Load each module
	while ($data = $ssc_database->fetch_assoc($result)){
		$SSC_MODULES[] = $data;
		core_debug(array('title'=>'module_load','body'=>"Loading '$data[name]' ($data[filename].module.php)"));
		include "$ssc_site_path/modules/$data[filename]/$data[filename].module.php";
	}

	// Find the module responsible for the current URI
	module_find_handler();
	
	// Initialise module
	module_hook("init");
	
	// Mark function as run
	$has_run = 1;
}

/**
 * Called when a module responsible for page content has an invalid parameter or no module is
 * available for handling the requested URI.  Invokes a contingency plan to keep operating
 * but handle the error and quit normally.
 * 
 * @param int $error Status error relating to reason for dieing
 */
function module_error_content($error){
	global $SSC_SETTINGS;
	if (isset($SSC_SETTINGS['runtime']['error'])){
		$error = $SSC_SETTINGS['runtime']['error'];
	}
	
	echo 'Called at Status error ' . $error;
}

/**
 * Causes an error to be tripped
 * @param int $error Status error
 * @param string $message Description for dieing
 * @param bool $change Whether or not the core should take over the problem
 */
function module_error($error, $message, $change = true){
	global $SSC_SETTINGS;
	
	$SSC_SETTINGS['runtime']['error_msg'] = $message;
	
	if ($change){
		module_error_content($error);
	}
	else{
		$SSC_SETTINGS['runtime']['error'] = $error;
	}
}