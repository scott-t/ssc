<?php
/**
 * Navigation Administration
 *
 * Perform administration on navigation methods.  Re-order the nav bar, show/hide items
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Module install status constants
 */
define('MOD_HIDDEN',-1);
define('MOD_CORE',0);

echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/nav.png" alt="" /><span class="title">Navigation</span><hr class="admin" /><div class="indent">';
	
if(isset($_POST['submit'])){

	//first loop thru hidden.
//	echo '<p>';
	//presume shown
	$database->setQuery('UPDATE #__navigation SET hidden = 0');
	$database->query();
	$database->clearError();
	if(isset($_POST['hide-id'])){
		$len = count($_POST['hide-id']);
		for($i = 0; $i < $len; $i++){
			$database->setQuery(sprintf("UPDATE #__navigation SET hidden = 1 WHERE id = %d LIMIT 1",$_POST['hide-id'][$i]));
			$database->query();
		}
		
		if($database->getErrorNumber()){
			echo error('There was an error while setting hidden status'),'<br />';
		}else{
			echo message('Visibility was set successfully<br /><br /><a href="'.$sscConfig_adminURI.'">Refresh</a> to view changes'),'<br />';
		}
		$database->clearError();
	}
		
	//now set weighting
	$len = count($_POST['idnum']);
	for($i = 0; $i < $len; $i++){
		$database->setQuery(sprintf("UPDATE #__navigation SET position = %d WHERE id = %d LIMIT 1",$_POST['weight-id'][$i],$_POST['idnum'][$i]));
		$database->query();
	}
	
	if($database->getErrorNumber()){
		echo error('There was an error while setting order weighting'),'<br />';
	}else{
		echo message('Navigation order was set successfully<br /><br /><a href="'.$sscConfig_adminURI.'">Refresh</a> to view changes'),'<br />';
	}
}

//wrap us in a form element and display table
echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="List of navigation items"><tr><th>ID</th><th>Item Name</th><th>Module Name</th><th>Hidden</th><th><span class="popup" title="A weighting of one will place the element at the top, 100 will place at the bottom">Weighting</span></th><th>Description</th></tr>';
	
	$database->setQuery('SELECT #__navigation.id, #__navigation.name, admin_text, hidden, position, admin_about FROM #__navigation, #__modules WHERE module_id = #__modules.id ORDER BY position ASC');
	$database->query();
		
	//fill table
	$total = $database->getNumberRows();
	while($data = $database->getAssoc()){
		echo '<tr><td>', $data['id'], '</td><td>', $data['name'], '</td><td>', $data['admin_text'], '</td><td><input type="checkbox" name="hide-id[]" value="',$data['id'],'" ';
		
		//are we hidden?
		if($data['hidden'] == 1){ echo 'checked="checked" ';}
		
		echo '/></td><td><input type="hidden" name="idnum[]" value="',$data['id'],'" /><select name="weight-id[]">';
		
		//weighting choice
		for($i = 1; $i <= 100; $i++){
			echo '<option value="',$i,'"';
			//this the one we up to?
			if($i == $data['position']){
				//yes?  make it selected
				echo ' selected="selected"';
				//and enter a tight loop without the if for remainder of loop
				while($i < 100){
					echo '>',$i,'</option><option value="';
					$i++;
					echo $i,'"';
				}
				
			}
			echo '>',$i,'</option>';
		}
		
		echo '</select></td><td>', $data['admin_about'], '</td></tr>';
	}
	
	echo '</table><p><input type="submit" name="submit" value="Update navigation" /></p></form>';
	
	//******************************
	
	echo '</div>';

?>