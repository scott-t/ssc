<?php
/**
 * Module Administration
 *
 * Perform administration on modules.  Install new ones, re-order the nav bar, remove existing...
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

echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/component.png" alt="" /><span class="title">Module Administration</span><hr class="admin" /><form action="',$sscConfig_adminURI,'" method="post"><div class="indent">';
	
if(isset($_POST['install'])){
	//install a module
	echo warn('Module install code not completed'),'<br />';
}elseif(isset($_POST['delete'])){
	echo warn('Module delete code not completed<br />'),'</br />';
}
//wrap us in a form element and display table
//  (form element parent to current div
echo '<table class="tab-admin" summary="List of installed modules"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Module Name</th><th>Description</th><th>Version</th></tr>';
	
	$database->setQuery('SELECT id, admin_text, admin_about, version, installed, filename FROM #__modules ORDER BY admin_text ASC');
	$database->query();
	
	//fill table
	while($data = $database->getAssoc()){
		echo '<tr><td>', $data['id'], '</td><td><input type="checkbox" value="',$data['id'],'" name="deleteID[]" /></td><td>', $data['admin_text'], '</td><td>', $data['admin_about'], '</td><td>',$data['version'],'</td></tr>';
	}
	
	echo '</table>';
	
	//upload form
	
	echo '<button type="submit" class="pad-top" name="delete" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></div></form><br /><br /><img class="panel-icon-img" src="',$sscConfig_adminImages,'/component.png" alt="" /><span class="title">Upload and install new module</span><hr class="admin" /><div class="indent"><br /><form action="',$sscConfig_adminURI,'" method="post" enctype="multipart/form-data"><fieldset><legend>Upload and install new module</legend><!--[if IE]><br /><![endif]--><div><label for="file">Module to upload:<input type="hidden" name="MAX_FILE_SIZE" value="', MAX_UPSIZE*1024*1024 ,'" /></label><input type="file" name="file" id="file" /></div><br /><div class="btn"><input type="submit" value="Install Module" name="install" id="install" /></div>';
	
	echo '</fieldset></form><br class="clear" /><br /></div>';

?>