<?php
/**
 * Dynamic page module.
 *
 * Generate an RSS feed for each of the dynamic pages.
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */

if($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR'])
	exit();

define('REL_PATH','../..');
define('_VALID_SSC', 1);
	
/**
 * Set up core environment.
 * Sets up DB connection, includes configuration details, etc
 */
require_once(REL_PATH.'/conf/config.core.php');

//find blog details 
$database->setQuery("SELECT #__dynamic.id, #__navigation.uri, title, description FROM #__dynamic, #__navigation WHERE #__navigation.id = nav_id ORDER BY id ASC");
if(!($result = $database->query()))
	exit();								//sql stuff up

require_once(REL_PATH.'/includes/sscEdit.php');

//get each blogs details	
while($data = $database->getAssoc($result)){
	$database->setQuery("SELECT #__dynamic_content.date, title, content, uri, display FROM (#__dynamic_content, #__users) WHERE #__users.id = user_id AND #__dynamic_content.blog_id = $data[id] ORDER BY #__dynamic_content.date DESC LIMIT 0,5");
	if(!$database->query() || $database->getNumberRows() == 0)
		continue;
		
	$fp = fopen($sscConfig_absPath . '/modules/dynamic/rss-' . $data['id'] . '.xml','w');
	fwrite($fp, <<< FEED
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>$data[title]</title>
    <link>$sscConfig_webPath$data[uri]</link>
    <description>$data[description]</description>
	<language>en</language>
	<ttl>720</ttl>
	<pubDate>
FEED
); // <?php
	$dat = $database->getAssoc();
	fwrite($fp,date("r").'</pubDate>
    <lastBuildDate>'.date("r",strtotime($dat['date'])).'</lastBuildDate>
');
    
	do{
	$dat['date'] = strtotime($dat['date']);
	$date =	date("Y/m/d",$dat['date']);
	fwrite($fp, <<< ITEM
    <item>
      <title>$dat[title]</title>
      <link>$sscConfig_webPath$data[uri]/$date/$dat[uri]/</link>
      <description>
ITEM
); //<?php

	$dat['content'] = preg_replace('/<[^<|>]+>?/','',sscEdit::parseToHTML($dat['content']));
	fwrite($fp,substr($dat['content'],0,250).(strlen($dat['content'])>250?"[...]":""));
	fwrite($fp,"</description>
      <pubDate>".date("r",$dat['date'])."</pubDate>
    </item>
");

	}while($dat = $database->getAssoc());
	fwrite($fp,"  </channel>
</rss>");
	
	fclose($fp);
	
}

$database->freeResult($result);