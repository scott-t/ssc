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
 * Retrieve the correct site configuration.  Sites can only be set on a (sub-)domain basis. 
 */

function core_conf_file(){
	static $path = '';
	if ($path)
		return $path;						// So we don't go through this every timne

	$path = explode(".",$_SERVER['SERVER_NAME']);
	do{
		$filepath = implode(".", $path);
		unset($path[count($path)-1]);
		if($filepath == ""){
			$filepath = "default";
			break;
		}
	}while (!file_exists("$filepath.settings.php"));
	
	return "$filepath.settings.php";
}


