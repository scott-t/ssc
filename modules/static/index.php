<?php
/**
 * Static text display module
 *
 * Show pre-genereated 'static' pages
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $database, $sscConfig_absPath;

$database->setQuery(sprintf("SELECT content, title FROM #__static WHERE nav_id = %d LIMIT 1",$_GET['pid']));
if($database->query() && $data = $database->getAssoc()){
	echo '<h1>',$data['title'],'</h1>';
	include_once($sscConfig_absPath.'/includes/sscEdit.php');
	echo sscEdit::parseToHTML($data['content']);
	echo '<!--[if !IE 6]><div class="clear" /></div><![endif]-->';
	echo '<br class="clear" />';

}else{ echo error("An unexpected error occurred. Please contact the webmaster with the link to this page</br >".$database->getErrorMessage());}

?>