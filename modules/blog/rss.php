<?php
/**
 * Dynamic page module.
 *
 * Generate an RSS feed for each of the dynamic pages.
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */

defined('_VALID_SSC') or die('Restricted access');

global $ssc_database, $ssc_site_url, $ssc_site_path;


if (!ssc_load_library('sscText')){
echo "Unable to load library";
return;
}


$result = $ssc_database->query("SELECT p.id, p.created, p.modified, urltext, title, body, displayname FROM #__blog_post p LEFT JOIN #__user u ON u.id = author_id WHERE blog_id = 3 ORDER BY created DESC LIMIT 0,5");


	if(!$result || $ssc_database->number_rows() == 0)
return;
		
	$fp = fopen($ssc_site_path . '/modules/blog/rss-1.xml','w');
	$ap = fopen($ssc_site_path . '/modules/blog/atom-1.xml','w');
	fwrite($fp, <<< FEED
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Scott T: The Blog</title>
    <link>$ssc_site_url</link>
    <atom:link href="$ssc_site_url/modules/blog/rss-1.xml" rel="self" type="application/rss+xml" />
    <description>One post at a time...</description>
	<language>en</language>
	<ttl>720</ttl>
	<pubDate>
FEED
); // <?php
	$dat = $ssc_database->fetch_assoc($result);
	fwrite($fp,date("r",$dat['modified']).'</pubDate>
    <lastBuildDate>'.date("r",$dat['modified']).'</lastBuildDate>
');
    fwrite($ap, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\" xml:base=\"$ssc_site_url/modules/blog/atom-1.xml\">
  <title type=\"text\">Scott T: The Blog</title>
  <subtitle type=\"text\">One post at a time...</subtitle>
  <link rel=\"self\" type=\"application/atom+xml\" href=\"$ssc_site_url/modules/blog/atom-1.xml\" />
  <link rel=\"alternate\" type=\"text/html\" href=\"$ssc_site_url\" />
  <updated>".date("c",$dat['modified'])."</updated>
  <author>
    <name>Scott</name>
  </author>
  <id>$ssc_site_url/modules/dynamic/atom-1.xml</id>
");
   
	do{

	$date =	date("Y/m/d",$dat['created']);
	fwrite($fp, <<< ITEM
    <item>
      <title>$dat[title]</title>
      <link>$ssc_site_url/$date/$dat[urltext]</link>
      <guid>$ssc_site_url/id/$dat[id]</guid>
      <description>
ITEM
); //<?php

	$dat['body'] = sscText::convert($dat['body']);
	$stripped = preg_replace('/<[^<|>]+>?/','',$dat['body']);
	$stripped = substr($stripped,0,350).(strlen($stripped)>350?"[...]":"");
	fwrite($fp,$stripped);
	fwrite($fp,"</description>
      <pubDate>".date("r",$dat['created'])."</pubDate>
    </item>
");
    $iso = date("c",$dat['created']);
$isou = date("c",$dat['modified']);
	fwrite($ap,"  <entry>
    <id>$ssc_site_url/id/$dat[id]</id>
    <title type=\"text\">$dat[title]</title>
    <updated>$isou</updated>
    <published>$iso</published>
    <author>
      <name>$dat[displayname]</name>
    </author>
    <link rel=\"alternate\" type=\"text/html\" href=\"$ssc_site_url/$date/$dat[urltext]\" />
    <summary type=\"text\">$stripped</summary>
    <content type=\"xhtml\"><div xmlns=\"http://www.w3.org/1999/xhtml\">$dat[body]</div></content>
  </entry>
");
	}while($dat = $ssc_database->fetch_assoc($result));
	fwrite($fp,"  </channel>
</rss>");
	fclose($fp);
	fwrite($ap,"</feed>");
	fclose($ap);


