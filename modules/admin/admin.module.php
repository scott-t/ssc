<?php
/**
 * Module to perform administration over the application
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Implementation of module_content
 */
function admin_content(){
	global $ssc_user, $ssc_database;
	
	$out = '';
	
	if ($_GET['path'] != 'admin' || $ssc_user->gid == SSC_USER_GUEST){
		ssc_not_found();
		return;
	}
	
	switch ($_GET['param']){
	
	case '':
		ssc_set_title("Administration");
		$out = _admin_base_content();
		break;
	default:
		// Check for sub-page.  args can be claimed from $_GET[param]
		$_GET['param'] = explode("/", $_GET['param']);
		$_GET['admin_page'] = array_shift($_GET['param']);
		if (!login_check_auth($_GET['admin_page']))
			ssc_not_allowed();
		else
			$out = module_hook('admin', $_GET['admin_page']);
		
		if (empty($out))
			ssc_not_found();
			
		break;
	}
	
	return $out;
}

/**
 * Generates the base admin listing
 */
function _admin_base_content(){
	global $ssc_user, $ssc_database, $ssc_site_path, $ssc_site_url;
	
	$out = '';
	
	// Get list of modules
	if ($ssc_user->gid == SSC_USER_ROOT){
		$result = $ssc_database->query("SELECT filename FROM #__module ORDER BY filename ASC");
	}
	else{
		$result = $ssc_database->query("SELECT filename FROM #__permission p LEFT JOIN #__module m ON module_id = m.id WHERE group_id = %d", $ssc_user->gid);
	}
	
	// For storage
	$list = array();
	// Get the info about each module
	while ($mod = $ssc_database->fetch_object($result)){
		$info = ssc_parse_ini_file('module', "$ssc_site_path/modules/$mod->filename/$mod->filename.info");
		$list[$info['package']][$mod->filename] = $info;
	}
	
	// Show the list
	$op = array('html' => true, 'attributes' => array('class' => 'admin-block'));
	foreach ($list as $type => $mod){
		if ($type == '')
			$type = t('Uncategorized'); 
			
		// Category title
		$out .= "<h3>$type</h3>";
		foreach ($mod as $file => $module){
			// Hide "admin" admin options
			if ($file == 'admin')
				continue;
				
			$block = "<img src=\"$ssc_site_url/images/$file.png\" alt=\"\" /><span><span>$module[name]</span><span>$module[description]</span></span>";
			$out .= l($block, "/admin/$file", $op);
		}
	}
	return $out;
}

/**
 * Common formatting for a table
 * @param string $title Page title
 * @param string $sql SQL query dictating the page
 * @param array $sql_args Array containing the arguments for the query
 * @param array $table_args Array containing arguments for formatting the table
 * @return string Markup for the table
 */
function ssc_admin_table($title, $sql, $sql_args = null, $table_args = null){
	global $ssc_database, $ssc_site_url;

	if (isset($table_args['perpage'])){
		// Work out page
		$perpage = $table_args['perpage'];
		$page = array_search('page', $_GET['param']) + 1;
		if (isset($_GET['param'][$page])){
			$page = (int)($_GET['param'][$page]);
			if ($page < 1)
				$page = 1;
		}
		else {
			$page = 1;
		}
		
		$args = array($page, $table_args['perpage'], $sql);
		if ($sql_args)
			$args = array_merge($args, $sql_args);
			
		$args = call_user_func_array(array($ssc_database, 'query_paged'), $args);
		$result = $args['result'];
		$out = "<div class=\"admin-block\"><img src=\"$ssc_site_url/images/{$_GET['admin_page']}.png\" alt=\"\" /><h3>$title";
		if ($args['next'] || $args['previous'])
			$out .= " - page $page</h3></div>";
		else
			$out .= '</h3></div>';
	}
	else {
		$perpage = 32000;
		$out = "<div class=\"admin-block\"><img src=\"$ssc_site_url/images/{$_GET['admin_page']}.png\" alt=\"\" /><h3>$title</h3></div>";
		$args = array($sql);
		if ($sql_args)
			$args = array_merge($args, $sql_args);

		$result = call_user_func_array(array($ssc_database, 'query'), $args);
	}
	
	// Valid SQL and at least one result
	if ($result && $ssc_database->number_rows()){
		$out .= '<table class="admin-table"><tr>';
		$data = $ssc_database->fetch_assoc($result);
		// Print headings
		foreach ($data as $head => $val)
			$out .= '<th>' . ucwords(str_replace('_', ' ', $head)) . '</th>';
			
		$out .= '</tr>';
		$row = 0;
		$i = 1;
		// Output rows.  Do-while because already retrieved first row for headers
		do {
			$id = null; 
			$out .= "\n<tr class=\"row" . ($row++ % 2) . '">';
			foreach ($data as $head => $val){
				if (!$id)
					$id = $val;
				
				if ($head == $table_args['link']){
					$out .= '<td>' . l($val, $table_args['linkpath'] . $id) . '</td>';
				}
				else {
					$out .= "<td>$val</td>";
				}
			}
				
			$out .= '</tr>';
		} while (($data = $ssc_database->fetch_assoc($result)) && ($i++ < $perpage));

		$out .= '</table>';
		
		// Page navigation
		if (isset($table_args['perpage'])){
			$out .= '<div class="paging"><span>';
			// Is there a previous page?
			if ($page > 1)
				$out .= l(t('Previous page'), $table_args['pagelink'] . ($page - 1));

			$out .= '</span> <span>';
			
			// Next page?
			if ($args['next'])
				$out .= l(t('Next page'), $table_args['pagelink'] . ($page + 1));
				
			$out .= '</span></div>';
		}
	}else
		$out .= $ssc_database->error();
	
	return $out;
}