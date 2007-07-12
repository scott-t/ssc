<?php
/**
 * Events admin page
 *
 * Administration on current/future/past events
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/event.png" alt="" /><span class="title">Events</span><hr class="admin" /><div class="indent">';
if(isset($_POST['submit'])){
	
	
}

//display event list
$database->setQuery("SELECT id, dt, name, description, uri, type FROM #__events ORDER BY dt ASC");
$database->query();
//set out table
if($database->getNumberRows())
{
	echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Events"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Date</th><th>Event Name</th><th>Extra Details</th><th>Show link</th><th>Link for details</th></tr>';
	while($data = $database->getAssoc()){
		echo '<tr><td>', $data['id'], '</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td>', $data['dt'], '</td><td><a href="', $sscConfig_adminURI, '/edit/', $data['id'], '">', $data['name'], '</a></td><td>', $data['description'], '</td><td><img src="',$sscConfig_adminImages, ($data['type'] & 1?'/done.png" alt="Yes" />':'/delete.png" alt="No" />'),'</td><td>', $data['uri'],'</td></tr>';
	}
	echo '</table></form>';
}else{
	echo message("There are currently no events set up");
}


echo '</div>';
?>
