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
 * Module information.
 * @global array $SSC_MODULES
 */
$SSC_MODULES;

/**
 * Call a hook on any loaded modules.  Specific modules may be hooked 
 * by passing the module name or names as an array to $modules
 * 
 * @param string $hook Hook to call
 * @param string|array $modules Module(s) to execute the hook on
 */
function module_hook($hook, $modules = NULL){
	global $SSC_MODULES;
	
	if (!isset($modules)){
		foreach ($SSC_MODULES as $value){
			$hook = "$value[filename]_init";
			if (function_exists($hook))
				call_user_func($hook);
		}
	}
	else {
		// Use suggested modules
		if(isarray($modules))
			foreach ($modules as $value){
				$hook = "$value[filename]_init";
				if (function_exists($hook))
					call_user_func($hook);
			}
		}
		else {
			$hook = "${modules}_init";
			if (function_exists($hook))
				call_user_func($hook);
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
	$result = $ssc_database->query("SELECT name, filename, weight FROM #__module WHERE status = %d ORDER BY weight ASC", SSC_MODULE_ENABLED);

	// Load each module
	while ($data = $ssc_database->fetch_assoc($result)){
		$SSC_MODULES[] = $data;
		include "$ssc_site_path/modules/$data[filename].module.php";
	}

	// Initialise module
	module_hook("init");
	
	// Mark function as run
	$has_run = 1;
}