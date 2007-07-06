<?php
/**
 * Directory processing
 * Add, remove directories and files
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Delete a directory and all files / subdirectories contained within
 * @param string Directory to remove
 * @return boolean Whether or not successful removal
 */
function rmdirRecursive($dir){

	if(is_dir($dir) && $dir_res = opendir($dir)){
		//dir was valid... now remove
		while(($file = readdir($dir_res)) !== false){
			//with exception of parent directory
			if($file == '..' || $file == '.'){ continue; }
			
			echo 'delete ',$file,'<br />';
			//now delete stuffs
			if(is_dir($file)){
				rmdirRecursive($file);
			}else{
				unlink($dir.'/'.$file);
			}
		}
		rmdir($dir);
		return true;
	}
	
	return false;

}


?>