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

global $database, $sscConfig_absPath, $sscConfig_webPath, $sscConfig_wordpressAPI;

$database->setQuery(sprintf("SELECT #__dynamic.id, title, uri, comments FROM #__dynamic, #__navigation WHERE nav_id = %d AND nav_id = #__navigation.id LIMIT 1",$_GET['pid']));
if($database->query() && $data = $database->getAssoc()){
	
	if($data['comments'])
		$blogComments = true;
	else
		$blogComments = false;
		
	if($data['title'] != '')
		echo '<h1>',$data['title'],'</h1>';

	include_once($sscConfig_absPath.'/includes/sscEdit.php');
	
	//split the query string
	$uri = $data['uri'];
	$permalink = '/'.$_GET['q'];
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
				if($blogComments){
					if(isset($_POST['g']) && $_SERVER['HTTP_REFERER'] == $sscConfig_webPath.$permalink)
					{
						if(isset($_POST['n'],$_POST['s'],$_POST['e'],$_POST['c']) && $_POST['n'] != '' && $_POST['e'] != '' && $_POST['c'] != ''){
							//submit button pushed...
							require_once($sscConfig_absPath.'/includes/sscAkismet.php');
	
							$akismet = new sscAkismet($sscConfig_webPath.$uri, $sscConfig_wordpressAPI);
							if($akismet === false)
								echo "Problem submitting comment<br />";
							else{
								
								$akismet->setContent($_POST['c'],'comment');
								$akismet->setAuthor($_POST['n'],$_POST['e'],$_POST['s']);
								$akismet->setRemote($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
								$akismet->setBlog($permalink);
								
								if($_POST['s'] != '' && strstr($_POST['s'],'http://')!==0 && strstr($_POST['s'],'https://')!==0)
									$_POST['s']="http://".$_POST['s'];
								
								$_POST['n'] = $database->escapeString($_POST['n']);
								$_POST['e'] = $database->escapeString($_POST['e']);
								$_POST['s'] = $database->escapeString($_POST['s']);
								$_POST['c'] = $database->escapeString($_POST['c']);
 
								if($akismet->isSpam()){
									$spam = 1;
								}else{
									$spam = 0;
								}
								$database->setQuery(sprintf("INSERT INTO #__dynamic_comments (post_id, name, email, site, comment, date, spam, ip) VALUES (%d, '%s', '%s', '%s', '%s', '%s', $spam, '%s')",$data['id'], $_POST['n'], $_POST['e'], $_POST['s'], $_POST['c'],  date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR']));
								if($database->query())
									echo message('Your comment has been submitted'.($spam?' however was marked as spam and been submitted for moderation':'')),'<br />';
							}
																
						}else{
							echo warn("Not all required fields were filled in!"),'<br />';
						}
					}
				
					echo '<br /><h2>Comments</h2>';
					$database->setQuery("SELECT name, site, comment, date FROM #__dynamic_comments WHERE post_id = " . $data['id'] . " && spam = 0 ORDER BY date ASC");
					if($database->query() && $database->getNumberRows() > 0){
						while($data = $database->getAssoc()){
							echo $data['comment'], '<br /><br />Posted ', date("D, M d, Y \a\\t h:i a",strtotime($data['date'])), " by ";
							if($data['site'] !=  '')
								echo '<a href="',$data['site'],'">', $data['name'],'</a>';
							else
								echo $data['name'];
							echo '<br /><hr /><br />';
						}
					}else{
						echo 'There are currently no comments<br /><br />';
					}
					echo '<form method="post" action="',$sscConfig_webPath,$permalink,'"><fieldset><legend>Make a comment</legend><!--[if IE]><br /><![endif]-->';
					echo '<div><label for="n">Name: </label><input type="text" name="n" id="n" maxlength="30" /></div><br />';
					echo '<div><label for="e"><span class="popup" title="Will not be shown">Email: </span></label><input type="text" maxlength="50" id="e" name="e" /></div><br />';
					echo '<div><label for="s"><span class="popup" title="Optional">Site:</span></label><input type="text" maxlength="80" name="s" id="s" /></div><br />';
					echo '<!--<div><label for="r"><span class="popup" title="Requires cookies">Remember Me?</span></label><input type="checkbox" id="r" name="r" value="1" /></div><br />-->';
					echo '<div><label for="c">Comment:</label><textarea cols="40" rows="10" name="c" id="c"></textarea></div><br />';
					echo '<div class="btn"><input type="submit" value="Add Comment" name="g" id="g" /></div></fieldset></form>';
				}
				
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