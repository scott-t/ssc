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
	
	//rh-side bar
	echo '<div class="right">';
	$database->setQuery("SELECT tag, COUNT(content_id) AS cnt FROM #__dynamic_tags LEFT JOIN #__dynamic_relation ON tag_id = #__dynamic_tags.id GROUP BY #__dynamic_tags.id ORDER BY tag ASC");
	if($database->query() && $database->getNumberRows() > 0){
		echo 'Tags<br />';
		while($data=$database->getAssoc())
			echo '&nbsp;&nbsp;&nbsp;<a href="',$sscConfig_webPath,$uri,'/tag/',$data['tag'],'">',$data['tag'],'</a>  (',$data['cnt'],')<br />';
			
		echo '<br /><br />';
	}
	$database->setQuery("SELECT YEAR( date ) AS yr, COUNT( date ) AS cnt FROM #__dynamic_content GROUP BY YEAR( date ) ORDER BY yr DESC");
	if($database->query() && $database->getNumberRows() > 0){
		echo 'Archive<br />';
		while($data = $database->getAssoc())
			echo '&nbsp;&nbsp;&nbsp;<a href="',$sscConfig_webPath,$uri,'/archive/',$data['yr'],'">',$data['yr'],'</a>  (',$data['cnt'],')<br />';
	}
	
	echo '</div>';
	
	if(isset($str[0]) && $str[0] != '' && ($yr = intval($str[0])) > 0){

		//year/mon/day notation
		if(isset($str[3])){
			$database->setQuery(sprintf("SELECT #__dynamic_content.id, date, title, content, display FROM #__dynamic_content, #__users WHERE #__users.id = user_id AND date LIKE '%s%%' AND uri = '%s' LIMIT 1", date("Y-m-d",strtotime($yr."-".$str[1]."-".$str[2])), $database->escapeString($str[3])));
			if($database->query() && $data = $database->getAssoc()){
				//success
				$database->setQuery("SELECT tag FROM #__dynamic_relation, #__dynamic_tags WHERE tag_id = #__dynamic_tags.id AND content_id = " . $data['id']);
				$database->query();
				echo '<h2>', $data['title'], '</h2>Posted ', date("D, M d, Y \a\\t h:i a",strtotime($data['date'])), " by ", $data['display'],'<br />';
				if($database->getNumberRows()>0){
					echo 'Tagged: ';
				
					while($dat = $database->getAssoc())
						echo '<a href="',$sscConfig_webPath,$uri,'/tag/',$dat['tag'],'">',$dat['tag'],'</a> ';
						
					echo '<br />';
				}
				echo sscEdit::parseToHTML($data['content']);
			}else{
				echo error("There was a problem accessing this post"),'<br />';
				echo $database->getQuery();
			}
		}else{
			echo warn("Oops!  You have entered an incomplete URI!<br /><br /><a href=\"".$sscConfig_webPath.$uri."\">Return</a> to the main section"),'<br />';
		}
	}else{
		$content = true;
		if(isset($str[1]) && $str[1] != '' && $str[0] != ''){
			switch(strtolower($str[0])){
				case 'tag':
					$database->setQuery("SELECT id FROM #__dynamic_tags WHERE tag = '" . $database->escapeString($str[1])."'");
					$database->query();
					$data = $database->getAssoc();
					$from = ', #__dynamic_relation';
					$limit = 'LIMIT 20';
					$where = ' AND content_id = #__dynamic_content.id AND tag_id = ' . $data['id'];
					break;
				case 'archive':
					$limit = '';
					$from = '';
					$where = ' AND date LIKE \'' . intval($str[1]) . '%\'';
					$content = false;echo '<h2>',$str[1],' Archive</h2>';
					break;
			}
		}else{
			$limit = 'LIMIT 10';
			$from = '';
			$where = '';
		}
		$database->setQuery("SELECT #__dynamic_content.id, date, title, content, uri, display FROM #__dynamic_content, #__users$from WHERE #__users.id = user_id$where ORDER BY date DESC $limit");
		if(($res = $database->query()) && $database->getNumberRows() > 0){
			while($data = $database->getAssoc($res)){
				$data['date'] = strtotime($data['date']);
			
				echo "<h2><a href=\"",$sscConfig_webPath,$uri,'/',date("Y/m/d/",$data['date']),$data['uri'],"\">",$data['title'],'</a></h2>Posted ', date("D, M d, Y \a\\t h:i a",$data['date']), " by ", $data['display'], '<br />';
				$database->setQuery("SELECT tag FROM #__dynamic_relation, #__dynamic_tags WHERE tag_id = #__dynamic_tags.id AND content_id = " . $data['id']);
				$database->query();
				if($database->getNumberRows()>0){
					echo 'Tagged: ';
				
					while($dat = $database->getAssoc())
						echo '<a href="',$sscConfig_webPath,$uri,'/tag/',$dat['tag'],'">',$dat['tag'],'</a> ';
						
					echo '<br />';
				}
				if($content)
					echo sscEdit::parseToHTML($data['content']),'<br /><br />';
			}
		}else{
			echo message("There is currently nothing posted under the specified criteria");echo mysql_error();echo '<br />',$database->getQuery();
		}
	}

	echo '<div class="clear"></div>';
}else{ echo error("An unexpected error occurred. Please contact the webmaster with the link to this page<br />");}

?>