<?php
/**
 * Navigation display
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Implementation of module_admin()
 */
function nav_admin(){
	$out = '';
	
	$action = array_shift($_GET['param']);
	
	switch ($action){
	case 'link':
		$out = ssc_generate_form('nav_edit_link');
		break;
		
	case 'widget':
		$out = ssc_generate_form('nav_edit_widget');
		break;
		
	case '':	// Base page
		global $ssc_database;
		ssc_set_title('Navigation widgets');
		$result = $ssc_database->query("SELECT args FROM #__sidebar WHERE module = 2 ORDER BY args ASC");
		if ($result && $ssc_database->number_rows() > 0){
			while ($data = $ssc_database->fetch_assoc($result)){
				$data = explode(',', $data['args']);
				// For each block
				$out .= _nav_edit_table($data[0]);
				/*$out .= ssc_admin_table($data[1], "SELECT n.id, p.title parent, n.title, n.desc description, n.url, COUNT(n.id) FROM
							#__navigation n, #__navigation p WHERE n.l BETWEEN p.l AND p.r
							AND p.bid = %d AND n.bid = %d GROUP BY n.id ORDER BY n.l", array($data[0], $data[0]),
							array('link' => 'title', 'linkpath' => '/admin/nav/link/'));/**/
				$out .= ssc_generate_form('nav_add_link', $data[0]);
			}
		}
		else{
			ssc_add_message(SSC_MSG_INFO, t('No navigation widgets exist yet.') . $ssc_database->error());
			$out = l(t('Create widget'), '/admin/nav/widget');
		}
		break;
		
	default:
		ssc_not_found();
		break;
	}
	
	return $out;
}

/**
 * Generates the forms used to do create nav's
 * @param int $bid Widget id number
 * @return string Markup
 */
function _nav_edit_table($bid){
	global $ssc_database;
	
	$result = $ssc_database->query("SELECT n.id, n.title, n.description, n.url, COUNT(n.id) lvl FROM
							#__navigation n, #__navigation p WHERE n.l BETWEEN p.l AND p.r
							AND p.bid = %d AND n.bid = %d GROUP BY n.id ORDER BY n.l", $bid, $bid);
	
	if (!$result || $ssc_database->number_rows() == 0){
		//blank
		return '';
	}
	
	$items = array();	// Store the "parent" dropdown
	$parent = array(0);	// Item->parent relationship temp storage
	$p = -1;
	$struct = array();	// Store menu widget hierarchy
	$ptr =& $struct;	// Pointer to structure
	$id = -1;
	$prev = array();	// Pointers to previous hierarchy nodes
	$lvl = 1;			// Current level
	while ($data = $ssc_database->fetch_object($result)){
		
		if ($data->lvl > $lvl){
			// Taking a step IN
			$prev[$lvl] =& $ptr;
			$ptr =& $ptr[$id];
			$lvl++;
			$parent[$data->lvl] = $data->id;	// Save current parent			
			$p = $parent[$lvl - 1];
		}
		else if($data->lvl < $lvl){
			do {
				// Step back
				$ptr =& $prev[--$lvl];
			} while ($data->lvl < $lvl);
			$parent[$lvl] = $data->id;
			$p = $parent[$lvl - 1];
		}
		else{
			$parent[$data->lvl] = $data->id;	// Save current parent
		}
		
		// (Now?) Within the same level
		$id = $data->id;
		
		if ($lvl == 1){
			$p = $id;
			$parent[1] = $id;
		}
		$data->pid = $p;		
		
		$ptr[$id]['#data'] = $data;
		$items[$id] = $data->title;
	}	

	// Now to parse this link structure
	$struct['#items'] = $items;
	$out = '<form action="" method="post"><table class="admin-table center-input"><tr><th>ID</th><th>Parent</th><th>Title</th><th>Description</th><th>Path</th></tr>';
	$out .= _nav_edit_table_parser($struct, true);
	$out .= '</table><div><input type="hidden" name="form-id" value="_nav_edit_table" />' . theme_render_input(array('#type' => 'submit', '#value' => 'Save', '#name' => 'sub')) . '</div></form>';
	return $out;
}

/**
 * _nav_edit_table() helper
 * @param array $struct Structured hierarchial tree
 * @return string things
 */
function _nav_edit_table_parser(&$struct, $clear = false){
	static $row = 0;
	static $items;
	if (!$items || $clear)
		$items = $struct['#items'];
		
	$out = '';
	
	foreach ($struct as $key => $child){
		if ($key == '#data'){
			$name = "nav-row[$child->id]";
			$e['#selected'] = $child->pid;
			$e['#name'] = "${name}[pid]";	// Now this is pushing the extreme?
			$e['#value'] =& $items;
			$e['#elementonly'] = true;
			$out .= '<tr class="row' . ($row++ % 2) . '"><td>' . $child->id . '</td><td>';
			$out .= theme_render_select($e);
			$out .= '</td><td>' . _nav_input($child->title, 'title', $name) . '</td><td>';
			$out .= _nav_input($child->description, 'desc', $name, 15) . '</td><td>' . _nav_input($child->url, 'url', $name, 15) . '</td></tr>'; 
		}
		elseif ($key != '#items'){
			$out .= _nav_edit_table_parser($child);
		}
	}
	return $out;
}

/**
 * _nav_edit_table() helper
 * @param string $val Value to give element
 * @param string $name Name to assign to element
 * @param string $prefix Name prefix
 * @param int $len Size of the input box
 * @return string things
 */
function _nav_input($val, $name, $prefix, $len = 10){
	$out = '<input type="text" name="' . $prefix . '[' . $name . ']" size="' . $len . '" value="' . $val . '" />';
	return $out;
}

function _nav_edit_table_validate(){
	global $ssc_database;
	// Check privileges
  	if (!login_check_auth("nav"))
    	return false;

	// Check for empty fields
	$del = array();
	foreach ($_POST['nav-row'] as $id => &$vals){
		$pid = $_POST['nav-row'][$id]['pid'] = intval($vals['pid']);

		if (empty($vals['title'])){
			array_push($del, $id);
			continue;
		}
		else{
			if ($pid < 1){
				ssc_add_message(SSC_MSG_CRIT, t('Invalid row parent'));
				return false;
			}
			if (empty($vals['url'])){
				ssc_add_message(SSC_MSG_CRIT, t('All link urls need to be filled in'));
				return false;
			}
		}
	}
	
	// Check deletions
	while (count($del) > 0){
		$did = array_pop($del);
		// Search for children
		foreach ($_POST['nav-row'] as $id => &$vals){
			if ($vals['pid'] == $did && $id != $did && !empty($vals['title'])){
				ssc_add_message(SSC_MSG_CRIT, t('Unable to delete link to \'!path\' as not all children are marked for deletion',
										array('!path' => $_POST['nav-row'][$did]['url'])));
				continue 2;
			}
		}
		// Safe to delete
		unset($_POST['nav-row'][$did]);
		$ssc_database->query("DELETE FROM #__navigation WHERE id = %d LIMIT 1", $did);
	}
	
	// WARNING: Ugly code follows till end of function
	
	$stack = array();
	$count = 1;
	$keys = array_keys($_POST['nav-row']);
	while (count($keys) > 0){
		$id = array_shift($keys);
		$vals =& $_POST['nav-row'][$id];
		// Skip ones already with a "left" and hence "right"
		if (!empty($vals['l']))
			continue;
			
		$pid = $vals['pid'];
		if ($id != $pid){
			// A "child" node of some sort - we'll get to these anyway
			continue;
		}
		
		// Should be only with root nodes here
		if (count($stack) != 0){
			ssc_add_message(SSC_MSG_CRIT, t('Error assigning values to navigation tree'));
			return false;
		}
			
		// Set stack
		$stack = array();
		$vals['l'] = $count++;
		// While the stack isn't empty yet...
		do {
			// Loop through each row to find a child
			foreach ($_POST['nav-row'] as $i => $key){
				// New child?
				if ($key['pid'] == $pid && $i != $id && empty($key['l'])){
				 	// Repeat
					array_push($stack, $pid);
					$pid = $i;
					$_POST['nav-row'][$i]['l'] = $count++;
					reset($_POST['nav-row']);
				}
			}
			
			// No new children - add the "right" value
			$_POST['nav-row'][$pid]['r'] = $count++;
			// And loop until we finish the branch
		} while(($pid = array_pop($stack)) !== null);
	}
	
	// Check we have all left/right values assigned
	foreach ($_POST['nav-row'] as $row){
		if (empty($row['l']) || empty($row['r'])){
			ssc_add_message(SSC_MSG_CRIT, t('Error assigning values to navigation tree'));
			return false;
		}
	}
	return true;
}

function _nav_edit_table_submit(){
	global $ssc_database;
	
	foreach ($_POST['nav-row'] as $id => $vals){
		$ssc_database->query("UPDATE #__navigation SET l = %d, r = %d, url = '%s', title = '%s', description = '%s' WHERE id = %d LIMIT 1", $vals['l'], $vals['r'], $vals['url'], $vals['title'], $vals['desc'], $id);
		echo $ssc_database->error();
	}
}

/**
 * Navigation link edit form
 */
function nav_add_link($bid){
	$form = array(	'#method' => 'post',
					'#action' => '');
	
	$form['wid'] = array(	'#type' => 'hidden',
							'#value' => $bid);
	$form['add'] = array(	'#parent' => true,
							'#type' => 'fieldset',
							'#title' => 'Add new link');
	$form['add']['title'] = array(	'#type' => 'text',
									'#required' => true,
									'#title' => t('Link name'),
									'#description' => t('Text to display on the navigation widget'));
	$form['add']['desc'] = array(	'#type' => 'text',
									'#title' => t('Description'),
									'#description' => t('Extra information to display on link mouse-hover'));
	$form['add']['url'] = array('#type' => 'text',
								'#required' => true,
								'#title' => t('Link path'),
								'#description' => t('Link relative to site base or full url (including http://)'));

	$form['add']['sub'] = array('#value' => t('Add link'),
								'#type' => 'submit');
	
	return $form;
}

/**
 * Edit link validation
 */
function nav_add_link_validate(){
	// Check privileges
  	if (!login_check_auth("nav"))
    	return false;
    	
	if (empty($_POST['title']) || empty($_POST['url']) || empty($_POST['wid'])){
		ssc_add_message(SSC_MSG_CRIT, t('Both link title and path need to be entered'));
		return false;
	}
	
	if (empty($_POST['desc']))
		$_POST['desc'] = '';
		
	return true;
}

/**
 * Edit link submission
 */
function nav_add_link_submit(){
	global $ssc_database;
	$result = $ssc_database->query("SELECT r FROM #__navigation WHERE bid = %d ORDER BY r DESC LIMIT 1", $_POST['wid']);
	if (!$data = $ssc_database->fetch_assoc($result))
		return;
		
	$result = $ssc_database->query("INSERT INTO #__navigation SET url = '%s', title = '%s', description = '%s', l = %d, r = %d, bid = %d", $_POST['url'], $_POST['title'], $_POST['desc'], $data['r']+1, $data['r']+2, $_POST['wid']);
	if ($result){
		ssc_add_message(SSC_MSG_INFO, t('Link was added'));
	}
	else{
		ssc_add_message(SSC_MSG_CRIT, t('Link unable to be added'));
	}
}

/**
 * Navigation block edit form
 */
function nav_edit_widget(){

}

/**
 * Nav block edit validation
 */
function nav_edit_widget_validate(){

}

/**
 * Nav block edit submission
 */
function nav_edit_widget_submit(){

}

/**
 * Implementation of module_widget()
 * @param mixed $block Will contain either an integer based on which database "block"
 * 				to display or an array structure indicating a navigation tree
 * @return string Marked up nav block
 */
function nav_widget($block, $title = ''){
	$out = "<ul>\n";
	if (is_array($block)){
		// Array version - this is gonna need recursion
		foreach ($block as $link){
			$out .= "  <li>\n    " 
				 	. l($link['t'], $link['p'], (!empty($link['h']) ? 
				 				array('attributes' => array('title' => $link['h'])) : array())) 
				 	. "</li>\n";
			if (!empty($link['c']))
				$out .= "    <li>" . nav_widget($link['c']) . "</li>\n";
		}
	}
	else{
		// Grab data from DB
		global $ssc_database;
		$result = $ssc_database->query("SELECT node.id, node.url, node.description, node.title, (COUNT(node.id) - 1) AS depth
				FROM #__navigation AS node,
				#__navigation AS parent
				WHERE node.l BETWEEN parent.l AND parent.r AND node.bid = %d AND parent.bid = %d
				GROUP BY node.id
				ORDER BY node.l", $block, $block);
		
		echo $ssc_database->error();
		
		if (!$ssc_database->number_rows())
			return;
		
		// Get path to current
		$path = $ssc_database->query("SELECT parent.id FROM #__navigation AS node,
				#__navigation AS parent
				WHERE node.l BETWEEN parent.l AND parent.r
				AND node.url = '/%s' AND parent.bid = %d AND node.bid = %d
				ORDER BY parent.l", $_GET['q'], $block, $block);
		
		$tree = array();
		
		while ($data = $ssc_database->fetch_object($path)){
			$tree[] = $data->id;
		}

		$out .= '<li>';
		$prev_depth = 0;
		$i = 0;
		
		// Parent listings
		$parent = array(0, 0);
		$p =& $parent[1];
		
		// Loop through
		while ($data = $ssc_database->fetch_object($result)){
				
			// Prepare tooltip
			if (!empty($data->desc))
				$op = array('attributes' => array('title' => $data->description));
			else
				$op = array();
			
			// Are we a child of previous?
			if ($data->depth > $prev_depth){
				// New level
				// Do we accept new level?  Yes if root node or if parent in path
				if ($p == 0 || array_search($p, $tree) !== false){
					// Level accepted
					$prev_depth++;
					$out .= "  <ul>\n    <li>";
					array_unshift($parent, $data->id);
				}
				else {
					// Not accepted so ignore it
					do {
						$data = $ssc_database->fetch_object($result);
					} while ($data && $data->depth > $prev_depth);
					
					// Check if there is another link
					if ($data){
						// Yes - prepare to show it
						$out .= "</li>\n<li>";
					}
					else{
						// No - cut and run
						break;
					}
				}	
			}
			elseif ($data->depth < $prev_depth){
				// We're dropping levels instead
				$out .= '</li>';
				do {
					$prev_depth--;
					$out .= "</ul></li>\n";
					array_shift($parent);
				} while ($prev_depth > $data->depth);
				$out .= "<li>";
			}
			else{
				// Same level so repeat level
				if ($i)
					$out .= "</li>\n<li>";
				else
					$i++;
			}
			
			$out .= l($data->title, $data->url, $op);
		}

		// We finish with more levels to close?
		while ($data && $prev_depth > $data->depth) {
			$prev_depth--;
			$out .= " </li></ul>\n";
		}
		$out .= '</li>';
	}
	$out .= '</ul>'; 
	return array('body' => $out, 'title' => $title);
}

