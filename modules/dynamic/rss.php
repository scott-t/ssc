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
	$ap = fopen($sscConfig_absPath . '/modules/dynamic/atom-'. $data['id'] . '.xml','w');
	fwrite($fp, <<< FEED
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>$data[title]</title>
    <link>$sscConfig_webPath$data[uri]</link>
    <atom:link href="$sscConfig_webPath$data[uri]" rel="self" type="application/rss+xml" />
    <description>$data[description]</description>
	<language>en</language>
	<ttl>720</ttl>
	<pubDate>
FEED
); // <?php
	$dat = $database->getAssoc();
	$dat['date'] = strtotime($dat['date']);
	fwrite($fp,date("r").'</pubDate>
    <lastBuildDate>'.date("r",$dat['date']).'</lastBuildDate>
');
    fwrite($ap, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.wc3.org/2005/Atom\" xml:lang=\"en\" xml:base=\"$sscConfig_webPath/modules/dynamic/atom-$data[id].xml\">
  <title type=\"text\">$data[title]</title>
".
  (strlen($data['description'])>=0?"  <subtitle type=\"text\">$data[description]</subtitle>":'')."
  <link rel=\"self\" type=\"applicatin/atom+xml\" href=\"$sscConfig_webPath/modules/dynamic/atom-$data[id].xml\" />
  <link rel=\"alternate\" type=\"text/html\" href=\"$sscConfig_webPath$data[uri]\" />
  <updated>".date("c",$dat['date'])."</updated>
  <author>
    <name>$sscConfig_siteName</name>
  </author>
  <id>$sscConfig_webPath/modules/dynamic/atom-$data[id].xml</id>
");
   
	do{
	$dat['date'] = strtotime($dat['date']);
	$date =	date("Y/m/d",$dat['date']);
	fwrite($fp, <<< ITEM
    <item>
      <title>$dat[title]</title>
      <link>$sscConfig_webPath$data[uri]/$date/$dat[uri]</link>
      <guid>$sscConfig_webPath$data[uri]/$date/$dat[uri]</guid>
      <description>
ITEM
); //<?php

	$dat['content'] = sscEdit::parseToHTML($dat['content']);
	$stripped = preg_replace('/<[^<|>]+>?/','',$dat['content']);
	$stripped = substr($stripped,0,350).(strlen($stripped)>350?"[...]":"");
	fwrite($fp,$stripped);
	fwrite($fp,"</description>
      <pubDate>".date("r",$dat['date'])."</pubDate>
    </item>
");
    $iso = date("c",$dat['date']);
	fwrite($ap,"  <entry>
    <id>$sscConfig_webPath$data[uri]/$date/$dat[uri]</id>
    <title type=\"text\">$dat[title]</title>
    <updated>$iso</updated>
    <published>$iso</published>
    <author>
      <name>$dat[display]</name>
    </author>
    <link rel=\"alternate\" type=\"text/html\" href=\"$sscConfig_webPath$data[uri]/$date/$dat[uri]\" />
    <summary type=\"text\">$stripped</summary>
    <content type=\"html\"><div xmlns=\"http://www.w3.org/1999/xhtml\">$dat[content]</div></content>
  </entry>
");
	}while($dat = $database->getAssoc());
	fwrite($fp,"  </channel>
</rss>");
	fclose($fp);
	fwrite($ap,"</feed>");
	fclose($ap);
}

$database->freeResult($result);