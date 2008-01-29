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
 * Returns the HTML tags representing CSS and JS for the current page
 * @param string $ver Revision of the css/js to force page a refresh.
 * 				eg, "/themes/site.css?ver"
 * @return string HTML tags representing the CSS/JS
 */
function _theme_get_meta($ver = '1'){
	$out = '';
	$css = ssc_add_css();
	foreach ($css as $path){
		$out .= '<link type="text/css" rel="stylesheet" media="' . $path[1] . '" href="' . $path[0] . '?' . $ver . "\" />\n";
	}
	
	$js = ssc_add_js();
	foreach ($js as $path){
		$out .= '<script type="javascript" src="' . $path . '?' . $ver . "\"> </script>\n";
	}
	
	return $out;
}

/**
 * Loads up the specified theme for usage
 * @param string $theme Theme 'folder' name to be loaded
 * @return array Array containing the theme's .info data
 */ 
function theme_get_info($theme = null){
	global $ssc_site_path, $ssc_site_url;
	static $info = null;
	
	if ($theme){
		// Get the theme data
		$info = ssc_parse_ini_file('theme', "$ssc_site_path/themes/$theme/$theme.info");
		
		if (!$info){
			ssc_die(array('title' => 'Invalid Theme', 'body' => 'The selected theme is borked'));
		}
		
		if (isset($info['js']) && is_array($info['js'])){
			foreach ($info['js'] as $path){
				ssc_add_js("/themes/$theme/$path");
			}
		}
		if (isset($info['css']) && is_array($info['css'])){
			foreach ($info['css'] as $path){
				$tmp = explode("#", $path);
				ssc_add_css("/themes/$theme/$tmp[0]", $tmp[1]);
			}
		}
	}
	
	return $info;
}

/**
 * Rendering function
 * @param string $body Generated HTML for the primary page.
 * 				Passed byref so we don't need to copy an entire string  
 */
function theme_render(&$body){
	global $ssc_site_path, $ssc_site_url;
	
	$theme = ssc_var_get('theme_default', SSC_DEFAULT_THEME);
	$info = theme_get_info();
	
	for ($i = 0; $i < $info['mini_count']; $i++){
		$side[$i] = theme_side($i);
	}
	
	ob_start();
	echo 'GET: ';
	print_r($_GET);
	echo '<br />POST: ';
	print_r($_POST);
	$side[2] = ob_get_contents();
	ob_end_clean();
	
	$title = ssc_var_get('site_name', false);
	$meta = _theme_get_meta() . '<title>' . ssc_set_title() . ($title ? " | " . $title : '') ."</title>\n";
	$lang = ssc_var_get('language', 'en');
	$logo = ssc_var_get('theme_show_logo', false) ? ssc_var_get('theme_logo', '') : false;
	$title = ssc_var_get('theme_show_title', false) ? "<h1>$title</h1>" : false;
	$quip = ssc_var_get('theme_show_quip', false) ? ssc_var_get('theme_quip', '') : false;
	$breadcrumb = ssc_var_get('theme_breadcrumb', false);
	$messages = theme_messages();
	//$side = array();
	
	$body = '<h2>' . ssc_set_title() . '</h2>' . $body;
	
	// Show the page
	include "$ssc_site_path/themes/$theme/$theme.theme.php";

}	

/**
 * 
 */

/**
 * Called when HTML meta, CSS and JS tags may be output
 *
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
 *
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
	
	$result = $ssc_database->query("SELECT filename, args FROM #__module, #__sidebar WHERE #__module.id = #__sidebar.module AND location = %d ORDER BY #__sidebar.weight ASC", $count);
	if ($ssc_database->number_rows() < 1){
		return '&nbsp;';
	}
	$ret = array();
	while ($data = $ssc_database->fetch_assoc($result)){
		$dat = module_hook('content_mini', $data['filename'], $data['args']);
		if ($dat)
			$ret[] = theme_render_sidebar($dat);
	}
	
	return implode("\n", $ret);
}

/**
 * Called when the heading of a page is generated
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 *
function theme_header($count){
	return "breadcrumb";
}

/**
 * Called when the primary body needs to be output.
 *
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
 */
function theme_messages(){
	static $classes = array(
						SSC_MSG_CRIT => 'message-crit',
						SSC_MSG_WARN => 'message-warn',
						SSC_MSG_INFO => 'message-info');
						
	$messages = ssc_get_message();
	ssc_clear_message();
	
	if (empty($messages))
		return '';

	$out = '';
		
	foreach ($messages as $message){
		$out .= '<div class="message ' . $classes[$message[0]] . '"><span>';
		$out .= do_plain($message[1]) . '</span></div>';
	}
	return $out;
}

/**
 * Called when the theme footer is displayed.
 * @param int $count Number of the header.  This may be possible where multiple
 * 					header sections exist but are not used purely for decoration,
 * 					or heading text
 
function theme_footer($count){
	ob_start();
		
	if($count==1){
		}
	else
	// Show debug info
	ssc_debug_show();
	
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}

/**
 * Form themeing function
 * @param array $structure Form structure
 * @return string HTML construction
 */
function theme_render_form($structure){
	$structure += array('#action' => '',
						'#method' => 'post');
	
	$out = '<form action="' . $structure['#action'] . '" method="' . $structure['#method'] . 
		   '" ' . (isset($structure['#attributes']) ? ssc_attributes($structure['#attributes']) : '') . 
		   '><div>';
	
	$out .= $structure['#value'] . '</div></form>';
	
	return $out;
}

/**
 * Form themeing function - fieldset
 * @param array $structure Form structure
 * @return string HTML construction
 */
function theme_render_fieldset($structure){
	$out = '<fieldset>' . (isset($structure['#title']) ? '<legend>' . $structure['#title'] . '</legend>' : '');
	$out .= $structure['#value'] . '</fieldset>';
	return $out;
}
/**
 * Form element wrappers
 * @param array $structure Element structure
 * @return string HTML construction
 */
function theme_render_form_element($structure){
	$structure += array('#required' => false);
	$out = '<div class="form-item">';
	
	$required = ($structure['#required'] == true ? '<span class="form-required" title="' . t("This item is required") . '">*</span>' : '');
	
	if (isset($structure['#title'])){
		$out .= '<label ' . (isset($structure['#id']) ? 'for="' . $structure['#id'] . '"' : '') . '>' . 
			t('!title: !required', array('!title' => $structure['#title'],
										 '!required' => $required)) . '</label>';
		
	}
	
	$out .= $structure['#value'];
	$out .= '<div class="form-desc">' . $structure['#description'] . '</div>';
	$out .= '</div>';
	return $out;
}


/**
 * Renders a simple xhtml element
 * @param array $structure Element structure
 * @return string HTML construction
 */
function theme_render_input($structure){
	$out  = '<input type="' . $structure['#type'] . '" value="' . $structure['#value'] . '"';
	$out .= ' name="' . $structure['#name'] . '"';
	$out .= (isset($structure['#id']) ? ' id="' . $structure['#id'] . '"' : '');
	$out .= (isset($structure['#size']) ? ' size="' . $structure['#size'] . '"' : ''); 
	$out .= (isset($structure['#maxlength']) ? ' maxlength="' . $structure['#maxlength'] . '"' : ''); 
	$out .= ' />';
	return $out;
}

/**
 * Render the body of the page
 * @param string $body Primary bodily content
 * @return string Markup wrapped version of the body
 *
function theme_render_content($body){
	$out = '';
	
	return $out;
}

/**
 * Add a wrapper to a side bar block
 * @param array $sidebar Marked up version of the side bar
 * @return string Theme wrapped sidebar
 */
function theme_render_sidebar($sidebar){
	$out = '<div class="side"><div class="side-title">';
	$out .= $sidebar[0] . '</div><div class="side-body">' . $sidebar[1]; 
	$out .= '</div></div>';
	return $out;
}