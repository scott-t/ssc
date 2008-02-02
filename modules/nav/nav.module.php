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
 * Implementation of module_widget()
 * @param mixed $block Will contain either an integer based on which database "block"
 * 				to display or an array structure indicating a navigation tree
 * @return string Marked up nav block
 */
function nav_widget($block){
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
		$result = $ssc_database->query("SELECT node.url, node.desc, node.title, (COUNT(parent.title) - 1) AS depth
				FROM #__navigation AS node,
				#__navigation AS parent
				WHERE node.l BETWEEN parent.l AND parent.r AND node.bid = %d AND parent.bid = %d
				GROUP BY node.title
				ORDER BY node.l;", $block, $block);
		
		echo $ssc_database->error();
		
		if (!$ssc_database->number_rows())
			return;
		
		$out .= '<li>';
			
		$prev_depth = 0;
		$i = 0;
		// Loop through
		while ($data = $ssc_database->fetch_object($result)){
				
			// Prepare tooltip
			if (!empty($data->desc))
				$op = array('attributes' => array('title' => $data->desc));
			else
				$op = array();
			
			// Are we a child of previous?
			if ($data->depth > $prev_depth){
				// New level
				$prev_depth++;
				$out .= "  <ul>\n    <li>";
			}
			elseif ($data->depth < $prev_depth){
				// We're dropping levels instead
				$out .= '</li>';
				do {
					$prev_depth--;
					$out .= "</ul></li>\n";
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
		while ($prev_depth > $data->depth) {
			$prev_depth--;
			$out .= " </li></ul>\n";
		}
		$out .= '</li>';
	}
	$out .= '</ul>'; 
	return array('body' => $out);
}
