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
	global $ssc_database;
	
	$result = $ssc_database->query("SELECT filename, args FROM #__module, #__sidebar WHERE #__module.id = #__sidebar.module AND location = %d ORDER BY #__sidebar.weight ASC", $count);
	if ($ssc_database->number_rows() < 1){
		echo '&nbsp;';
		return false;
	}
	while ($data = $ssc_database->fetch_assoc($result)){
		module_hook('content_mini', $data['filename'], explode(',', $data['args']));
	}
	return true;
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
	global $SSC_SETTINGS;
	
	// Display any built up errors
	_theme_show_messages();
	
	module_hook('content', $SSC_SETTINGS['runtime']['handler_name']);
}

/**
 * Used to generate message boxes at the header of each page
 * @private
 */
function _theme_show_messages(){
	global $SSC_SETTINGS;
	
	// Have there been any errors set?
	if (!isset($SSC_SETTINGS['runtime']['errorlist']))
		return;
		
	foreach ($SSC_SETTINGS['runtime']['errorlist'] as $error){
		echo '<div class="error box">', $error['msg'], '</div>';
	}
}

/**
 * Called when the theme footer is displayed.
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 */
function theme_footer($count){	
	if($count==1){
		echo 'GET: ';print_r($_GET);echo '<br />POST: ';
		print_r($_POST);}
	else
	// Show debug info
	core_debug_show();
}