<?php
/**
 * offline.php
 * If website is off for maintenance or some sort of fatal error occured
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');
global $sscConfig_siteStatus, $sscConfig_errorMessage;
if(!$sscConfig_siteStatus){
	echo $sscConfig_offlineMessage;
}elseif(defined('_SCC_INSTALL_CHECK')){
	echo 'Please remove installation directory!';
}else{
	echo $sscConfig_errorMessage;
}