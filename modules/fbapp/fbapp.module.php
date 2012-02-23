<?php


// fbblog ssc module, module hook, blog module, post-publish hook
function fbapp_mod_blog_post_publish($blog_id, $id, $title){
	global $ssc_site_url, $ssc_database, $ssc_site_path;
	
	require_once 'facebook.php';
	$api_key = "9c476aaa4b1654c09ede303a7d140a36";
	$secret_key = ssc_var_get('fbapp_blog_secret', '');
	if ($secret_key == ''){
		ssc_add_message(SSC_MSG_CRIT, "Facebook user secret key has not been set up yet!");
		return;
	}
	
	$session_key = ssc_var_get('fbapp_blog_session', '');
	if ($session_key == ''){
		ssc_add_message(SSC_MSG_CRIT, "Facebook user session key has not been set yet!");
		return;
	}	
	$client = new FacebookRestClient($api_key, $secret_key, $session_key);
	
	if (!$client->users_getLoggedInUser()){
		ssc_add_message(SSC_MSG_CRIT, "Unable to get userid");
		return;
	}
	
	$dbres = $ssc_database->query("SELECT body FROM #__blog_post WHERE id = %d LIMIT 1", $id);
	if (!$dbres){
		ssc_add_message(SSC_MSG_CRIT, "Unable to retrieve posted item from database?!");
		return;
	}
	if (!($data = $ssc_database->fetch_assoc($dbres))){
		ssc_add_message(SSC_MSG_CRIT, "Unable to retrieve posted item from database?!");
		return;
	}
	
	$img = null;
	// Extract the first image
	$i = strpos($data['body'], '[[img');
	if ($i !== false){

		// Some basic error checking for a valid tag
		$j = strpos($data['body'], ']]', $i);
		$k = strpos($data['body'], '[[', $i + 3);
		if ($j !== false && ($j < $k || $k === FALSE)){

			$path = explode("|", substr($data['body'], $i, $j - $i));

			if (count($path) > 1){
				$path = $path[1];

				// Now match it up to the right path
				if (strpos($path, "://") === false){

					if ($path[0] == "/")
						$path = substr($path, 1);

					// Relative path
					if (file_exists($ssc_site_path . "/images/$path.jpg") || file_exists($ssc_site_path . "/images/$path.png") || file_exists($ssc_site_path . "/images/$path")){
						// Default to image directory base-dir
						$img = $ssc_site_url . "/images/$path";
					}
					elseif (file_exists($ssc_site_path . "/$path") || file_exists($ssc_site_path . "/$path.jpg") || file_exists($ssc_site_path . "/$path.png")){
						// Relative to site root instead
						$img = $ssc_site_url . '/' . $path;
					}
				}
			}
		}
	}
	
	// Hackish - TODO later for multiple blog paths, non-root based
	$uri = $ssc_site_url . "/id/$id";
	
	//$result = $client->feed_publishUserAction(30881549425, array("title"=>$title, "uri"=>$uri), '', '', 2);
	$attachment = array('name' => $title,
						'href' => $uri,
						/*'description' => ,*/
						'caption' => 'A blog post has just been made'
						);
	if ($img != null){
		$attachment['media'] = array(array(	'type' => 'image',
											'src' => $img,
											'href' => $uri));
	}
	$action_links = array(array('text' => 'Read this post',
								'href' => $uri));
	$target_id = null;//array();
	$uid = null;
	$result = $client->stream_publish(" has been blogging", $attachment, $action_links, $target_id, $uid);
	
	if ($result){
		ssc_var_set('fbapp_blog_lastid', $id);
	}
	else
	{
		ssc_add_message("Unable to post to FB");
	}
}


function fbapp_cron(){
	global $ssc_database, $ssc_site_url;

	$twitter = ssc_var_get("fbapp_twitterfeed", '');

	if ($twitter == '')
		return;

	$result = $ssc_database->query("SELECT updated FROM #__social_status ORDER BY updated DESC LIMIT 1");
	if (!$result)
		return;

	// Check for no rows - set never updated if possible, else get last update time
	if (!$data = $ssc_database->fetch_assoc($result))
		$data['updated'] = 0;

	if ($xml = simplexml_load_file($twitter)){
		foreach ($xml->channel->item as $item) {
			$timestamp = strtotime($item->pubDate);
			if ($timestamp == 0)
				return;		// D'oh
				
			if ($timestamp > $data['updated']){
				$i = strpos($item->title, ':');
				$who = substr($item->title, 0, $i);
				$what = substr($item->title, $i+2);
				$result = $ssc_database->query("INSERT INTO #__social_status (updated, url, status, who) VALUES (%d, '%s', '%s', '%s')",
						$timestamp, $item->guid, $what, $who);
			}
				
		}
	}	
}


function fbapp_widget($args){
	global $ssc_database, $ssc_site_url;
	$ret = '';
	$result = $ssc_database->query("SELECT updated, url, status, who FROM #__social_status ORDER BY updated DESC LIMIT 5");
	if (!$result)
		return;

	$pic = ssc_var_get("fbapp_twitterpic", '');
	if ($pic == '')
		return;

	while ($data = $ssc_database->fetch_assoc($result)){
		$ret .= '<div class="social_tweet"><img src="' . $pic . '" alt="" />' . 
			$data['status'] . '<br />' . l(date('g:ia d M', $data['updated']), $data['url']) . "</div><hr />\n";
	}

	return array('title'=>'Twitter Feed', 'body' => $ret . '<div class="social_tweet">' . l('Follow @st_au', 'http://twitter.com/st_au') . '</div>');
}