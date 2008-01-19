<?php
/**
 * Control for the themes
 * @package SSC
 * @subpackage Core
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Theme controller - Vertical navigation bar
 */
define('SSC_THEME_NAV_VERTICAL', 1);

/**
 * Theme controller - Navigation breadcrumb trail
 */
define('SSC_THEME_NAV_TRAIL', 1);

/**
 * Theme controller - Horizontal navigation bar
 */
define('SSC_THEME_NAV_HORIZONTAL', 1);

/**
 * 
 */
function theme_load(){
	global $SSC_SETTINGS;
		
	include "{$SSC_SETTINGS['theme']['path']}/{$SSC_SETTINGS['theme']['name']}.theme.php";
}

/**
 * Called when HTML meta, CSS and JS tags may be output
 */
function theme_meta(){
	module_hook('meta');
	echo '<title>content filler</title>';
}

/**
 * Called when a page title should be generated, for example the site name in a header
 * @param int $count Header level.  Use 1 for primary title and 2 for a quip or subtitle.
 */
function theme_title($count){
	echo "SSC";
}

/**
 * Called when one of 'n' sidebar locations is created.
 * @param int $count Number of the sidebar.  Used to decide which module
 * 					'mini' output versions should be shown
 */
function theme_side($count){
	echo "Navigation side $count";
}

/**
 * Called when the heading of a page is generated
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 */
function theme_header($count){
	echo "breadcrumb";
}

/**
 * Called when the primary body needs to be output.
 */
function theme_body(){
	$handle = fopen(__FILE__, "r");
	echo htmlentities(fread($handle, filesize(__FILE__)));
	fclose($handle);
}

/**
 * Called when the theme footer is displayed.
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 */
function theme_footer($count){	
	if($count==1)
		echo "second nav again?";
	else
	// Show debug info
	core_debug_show();
}