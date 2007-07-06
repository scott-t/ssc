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

global $database;
//echo $_GET['q'];

if(!isset($_GET['q'])){
$_GET['q'] = '';
}
$pos = strrpos($_GET['q'],"/");
if($pos > 0){
	//grab prev one if end is a "/"
	if(strlen($_GET['q']) == ($pos + 1)){
		$pos = strrpos(substr($_GET['q'],0,-1),"/");
		$access = substr($_GET['q'],$pos,-1);
	}else{
	$access = substr($_GET['q'],$pos+1);
	}
	$access = ucwords(str_replace('-',' ',$access));
}else{
	$access = $_GET['cont'];
}
$database->setQuery(sprintf("SELECT content, title FROM #__static WHERE access LIKE '%s' LIMIT 1",$database->escapeString($access)));
if($database->query() && $data = $database->getAssoc()){
	echo '<h1>',$data['title'],'</h1>';
	include_once('./includes/sscEdit.php');
	echo sscEdit::parseToHTML($data['content']);

}else{ echo error("An unexpected error occurred. Please contact the webmaster with the link to this page</br >".$database->getErrorMessage());}


?>