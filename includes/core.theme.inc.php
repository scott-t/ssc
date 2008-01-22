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
 * Default theme
 */
define('SSC_DEFAULT_THEME', 'php');

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
	/**
	 * Wrapper for themes
	 */
	function theme_do_render($type, $count = 1){
		theme_render($type, $count);
	}

	/**
	 * Rendering function
	 * @param string $type Data or type. Passed byref so we don't need to copy an entire string  
	 */
	function theme_render($type = '', $count = null){
		static $data;
		global $ssc_site_path, $ssc_site_url, $ssc_title;

		if ($type == ''){
			return;
		}
		
		if (!isset($count)){ 
			$data = $type;
					
			if (!isset($ssc_title))
				$ssc_title = 'Smooth Sailing CMS';
				
			$data['meta'] .= "<title>$ssc_title</title>"; 

			$theme = ssc_var_get('theme_default', SSC_DEFAULT_THEME);
			$theme_url = "$ssc_site_url/themes/$theme";
			$theme_path = "$ssc_site_path/themes/$theme";
			 
			include "$theme_path/$theme.theme.php";
			
			return;
		}
		
		$count--;

		if (is_string($data[$type])){
			echo $data[$type];
			return;
		}
		
		if (isset($data[$type][$count])){		
			echo $data[$type][$count];
			return;
		}
		
		
		
		return '';
		
	}
	
	
	global $ssc_site_path;
	
	$info = ssc_parse_ini_file('theme', "$ssc_site_path/themes/" . ssc_var_get('theme_default', SSC_DEFAULT_THEME). '/' . ssc_var_get('theme_default', SSC_DEFAULT_THEME) . '.info');
	
	// Now we build the theme up
	$data = array();
	// Meta
	$data['meta'] = theme_meta();
	
	// Headers
	$count = intval($info['head_count']);
	for ($i = 0; $i < $count; $i++)
		$data['header'][] = theme_header($i);
	
	// 'Title' blocks
	$count = intval($info['title_count']);
	for ($i = 0; $i < $count; $i++)
		$data['title'][] = theme_title($i);
		
	// Sidebar blocks
	$count = intval($info['side_count']);
	for ($i = 0; $i < $count; $i++)
		$data['side'][] = theme_side($i);
		
	// Body
	$data['body']= theme_body();
		
	// Footer blocks
	$count = intval($info['foot_count']);
	for ($i = 0; $i < $count; $i++)
		$data['footer'][] = theme_footer($i);
		
	
	return $data;
}

/**
 * Called when HTML meta, CSS and JS tags may be output
 */
function theme_meta(){
	ob_start();
	module_hook('meta');
	$data = ob_get_contents();
	ob_end_clean();
	return $data;
}

/**
 * Called when a page title should be generated, for example the site name in a header
 * @param int $count Header level.  Use 1 for primary title and 2 for a quip or subtitle.
 */
function theme_title($count){
	return "SSC";
}

/**
 * Called when one of 'n' sidebar locations is created.
 * @param int $count Number of the sidebar.  Used to decide which module
 * 					'mini' output versions should be shown
 */
function theme_side($count){
	global $ssc_database;
	
	ob_start();
	
	$result = $ssc_database->query("SELECT filename, args FROM #__module, #__sidebar WHERE #__module.id = #__sidebar.module AND location = %d ORDER BY #__sidebar.weight ASC", $count);
	if ($ssc_database->number_rows() < 1){
		return '&nbsp;';
	}
	while ($data = $ssc_database->fetch_assoc($result)){
		module_hook('content_mini', $data['filename'], $data['args']);
	}
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}

/**
 * Called when the heading of a page is generated
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 */
function theme_header($count){
	return "breadcrumb";
}

/**
 * Called when the primary body needs to be output.
 */
function theme_body(){
	global $SSC_SETTINGS;
	
	ob_start();
	
	// Display any built up errors
	_theme_show_messages();
	
	module_hook('content', $SSC_SETTINGS['runtime']['handler_name']);
	
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
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
	ob_start();
		
	if($count==1){
		echo 'GET: ';print_r($_GET);echo '<br />POST: ';
		print_r($_POST);}
	else
	// Show debug info
	ssc_debug_show();
	
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}