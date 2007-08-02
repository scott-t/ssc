<?php
/**
 * Administration page.  Displays the control panel for admin user to interact with
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');
	
	
//output the welcome/logout
echo '<span class="header-l">Welcome '.$_SESSION['UserFullName'].'</span><span class="hide"> - </span><span class="header-r"><a href="'.$sscConfig_webPath.'/admin/logout">Logout</a></span><br /><br />';

//begin c-panel output
echo '<div class="panel">';

//if a selection been made...
if(isset($_GET['sub'])){
	//...show its subpanel
	echo '<div class="indent">';
	$database->setQuery(sprintf("SELECT filename FROM #__modules JOIN #__permissions ON module_id = #__modules.id WHERE #__permissions.group_id = '%d' AND filename = '%s' LIMIT 1",$_SESSION['UserGroup'], $database->escapeString($_GET['sub'])));
	$database->query();
	
	//check authorisation.  is the logged in user allowed?
	if(($database->getNumberRows() || $_SESSION['UserGroup']=='1') && file_exists($sscConfig_absPath . '/modules/' . $_GET['sub'] . '/admin.php')){
		include_once('./modules/'.$_GET['sub'].'/admin.php');
	}else{
		echo error('You are not authorized to view this page, or page does not exist');
	}
	
	echo '</div><br /><a href="',$sscConfig_webPath,'/admin">Admin Home</a>';
}else{
	//no choice - show panel
		
	//test data
	echo '<div class="panel-icon-area">';
		
	if($_SESSION['UserGroup'] == 1){
		$database->setQuery('SELECT filename, image, name, admin_text, admin_description FROM #__modules ORDER BY admin_text');
	}else{
		$database->setQuery(sprintf("SELECT filename, image, name, admin_text, admin_description FROM #__modules JOIN #__permissions ON #__permissions.module_id = #__modules.id WHERE #__permissions.group_id = '%d' ORDER BY admin_text",$_SESSION['UserGroup']));
	}
	
		//show buttons
		$database->query();
		while($data = $database->getAssoc()){
			echo '<div class="panel-icon"><div class="panel-icon-img"><a href="', $sscConfig_webPath, '/admin/', $data['filename'], '"><img src="', $sscConfig_adminImages, '/', $data['image'], '.png" alt="" /><br />', $data['admin_text'], '</a></div>', $data['admin_description'], '<br /></div>';
		}
		
		echo '</div><div class="panel-stats">Random other stuff</div><div class="clear">&nbsp;</div>';

} 
// cpanel closing div
echo '</div>';
?>