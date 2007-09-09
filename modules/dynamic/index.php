<?php
/**
 * Dynamic page module.
 *
 * Based upon code from the static text module
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $database, $sscConfig_absPath, $sscConfig_webPath;

$database->setQuery(sprintf("SELECT #__dynamic.id, title, uri FROM #__dynamic, #__navigation WHERE nav_id = %d AND nav_id = #__navigation.id LIMIT 1",$_GET['pid']));
if($database->query() && $data = $database->getAssoc()){
	if($data['title'] != '')
		echo '<h1>',$data['title'],'</h1>';
	include_once($sscConfig_absPath.'/includes/sscEdit.php');
	//split the query string
	$uri = $data['uri'];
	$str = explode("/",substr($_GET['q'],strlen($uri)));
	if(isset($str[0]) && $str[0] != ''){
		
		$yr = intval($str[0]);
		if($yr > 0){	
			//year/mon/day notation
			if(isset($str[3])){
				$database->setQuery(sprintf("SELECT date, title, content, display FROM #__dynamic_content, #__users WHERE #__users.id = user_id AND date LIKE '%s%%' AND uri = '%s' LIMIT 1", date("Y-m-d",strtotime($yr."-".$str[1]."-".$str[2])), $database->escapeString($str[3])));
				if($database->query() && $data = $database->getAssoc()){
					//success
					echo '<h2>', $data['title'], '</h2>Posted ', date("D, M d, Y \a\\t h:i a",strtotime($data['date'])), " by ", $data['display'], '<br /><br />';
					echo sscEdit::parseToHTML($data['content']);
				}else{
					echo error("There was a problem accessing this post"),'<br />';
					echo $database->getQuery();
				}
			}else{
				echo warn("Oops!  You have entered an incomplete URI!<br /><br /><a href=\"".$sscConfig_webPath.$uri."\">Return</a> to the main section"),'<br />';
			}
		}else{
			if($str[0] == 'archive'){
				echo warn("Post archive is currently unavailable"),'<br />';
			}else{
				echo warn("Oops!  You have probably tried to access this page via purely the post title.  Try a <a href=\"http://www.google.com\">search</a> of it or return to the main <a href=\"".$sscConfig_webPath.$data['uri']."\">section</a> instead"),'<br />';
			}
		}
	}else{
		$database->setQuery("SELECT date, title, content, uri, display FROM #__dynamic_content, #__users WHERE #__users.id = user_id ORDER BY date DESC LIMIT 10");
		if($database->query()){
			while($data = $database->getAssoc()){
				$data['date'] = strtotime($data['date']);
			
				echo "<h2><a href=\"",$sscConfig_webPath,$uri,'/',date("Y/m/d/",$data['date']),$data['uri'],"\">",$data['title'],'</a></h2>Posted ', date("D, M d, Y \a\\t h:i a",$data['date']), " by ", $data['display'], '<br />';
				echo sscEdit::parseToHTML($data['content']),'<br /><br />';
			}
		}else{
			echo error("There was a problem accessing the post database");
		}
	}

	echo '<!--[if !IE 6]><div class="clear" /></div><![endif]-->';
	echo '<br class="clear" />';
}else{ echo error("An unexpected error occurred. Please contact the webmaster with the link to this page<br />");}

?>