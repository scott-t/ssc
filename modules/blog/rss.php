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

// Get a list of available "blogs"
$result = $ssc_database->query("SELECT id, name, description FROM #__blog");
if (!$result)
	return;

// Loop through each
while ($data = $ssc_database->fetch_assoc($result)){

	$res_posts = $ssc_database->query("SELECT p.id, p.created, p.modified, urltext, title, body, displayname FROM #__blog_post p LEFT JOIN #__user u ON u.id = author_id WHERE blog_id = %d ORDER BY created DESC LIMIT 0,5", $data['id']);

	// Ignore empty blogs
	if(!$res_posts || ($ssc_database->number_rows() == 0))
		return;

	// Open file handles
	$bID = $data['id'];
	$fp = fopen($ssc_site_path . "/modules/blog/rss-$bID.xml",'w');
	$ap = fopen($ssc_site_path . "/modules/blog/atom-$bID.xml",'w');

	// Retrieve first lot of posts (for updated date, etc)
	$dat = $ssc_database->fetch_assoc($res_posts);
	
	// Write file headers
	// RSS
	fwrite($fp, <<< FEED
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>$data[name]</title>
    <link>$ssc_site_url</link>
    <atom:link href="$ssc_site_url/modules/blog/rss-$bID.xml" rel="self" type="application/rss+xml" />
    <description>$data[description]</description>
	<language>en</language>
	<ttl>720</ttl>
	<pubDate>
FEED
); // <?php		// <-- Fix GUI highlighting 

	// Add some dates
	fwrite($fp, date("r", $dat['modified']) . '</pubDate>
    <lastBuildDate>' . date("r", $dat['modified']) . '</lastBuildDate>
');

	// Atom
    fwrite($ap, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\" xml:base=\"$ssc_site_url/modules/blog/atom-$bID.xml\">
  <title type=\"text\">$data[name]</title>
  <subtitle type=\"text\">$data[description]</subtitle>
  <link rel=\"self\" type=\"application/atom+xml\" href=\"$ssc_site_url/modules/blog/atom-$bID.xml\" />
  <link rel=\"alternate\" type=\"text/html\" href=\"$ssc_site_url\" />
  <updated>" . date("c", $dat['modified']) . "</updated>
  <author>
    <name>Scott</name>
  </author>
  <id>$ssc_site_url/modules/dynamic/atom-$bID.xml</id>
");
   
	// Loop through all posts
	// Do loop due to the first read earlier on
	do {
		// Get date
		$date =	date("Y/m/d", $dat['created']);
		// Echo post
		fwrite($fp, <<< ITEM
    <item>
      <title>$dat[title]</title>
      <link>$ssc_site_url/$date/$dat[urltext]</link>
      <guid>$ssc_site_url/id/$dat[id]</guid>
      <description>
ITEM
); //<?php  // <-- Ensure GUI happy again

		// Get markup version for Atom
		$dat['body'] = sscText::convert($dat['body']);
		// Strip tags for a text-only preview
		$stripped = preg_replace('/<[^<|>]+>?/', '', $dat['body']);
		// In shortened version, cut it off a bit too
		$stripped = substr($stripped, 0, 350) . (strlen($stripped) > 350 ? "[...]" : "");
		// Write post to RSS
		fwrite($fp, $stripped);
		fwrite($fp, "</description>
      <pubDate>".date("r",$dat['created'])."</pubDate>
    </item>
");

		// Prepare date for Atom
	    $iso = date("c", $dat['created']);
		$isou = date("c", $dat['modified']);
		// Write to Atom
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
	} while ($dat = $ssc_database->fetch_assoc($res_posts));
	
	// Finish each file markup, and close the handles
	fwrite($fp,"  </channel>
</rss>");
	fclose($fp);
	fwrite($ap,"</feed>");
	fclose($ap);
}
