<?php
/**
 * Dynamic page module.
 *
 * Generate an RSS feed for each of the dynamic pages.
 * @package SSC
 * @subpackage Module
 * @copyright Copyright (c) Scott Thomas
 */

defined('_VALID_SSC') or die('Restricted access');

global $ssc_database, $ssc_site_url, $ssc_site_path;


if (!ssc_load_library('sscText')){
	echo "Unable to load library";
	return;
}

// What blog are we in-lining for?
$bID = intval($_GET['path-id']);


$result = $ssc_database->query("SELECT id, name, description FROM #__blog WHERE id = %d LIMIT 1", $bID);
$data = $ssc_database->fetch_assoc($result);

if (isset($_GET['param'][0])) {
	$tag = array_shift($_GET['param']);
	$result = $ssc_database->query("SELECT id FROM #__blog_tag WHERE tag = '%s' LIMIT 1", $tag);
	if (!$result || ($ssc_database->number_rows() == 0)) {
		ssc_not_found();
		return;
	}
	
	$dat = $ssc_database->fetch_assoc($result);
	$tagName = "/$tag";
	$tag = $dat['id'];
	
	$res_posts = $ssc_database->query("SELECT p.id, p.created, p.modified, urltext, title, body, displayname FROM #__blog_post p LEFT JOIN #__user u ON u.id = author_id LEFT JOIN #__blog_relation t ON t.post_id = p.id WHERE blog_id = %d AND p.is_draft = 0 AND t.tag_id = %d ORDER BY created DESC LIMIT 0,5", $bID, $tag);
}
else {
	$res_posts = $ssc_database->query("SELECT p.id, p.created, p.modified, urltext, title, body, displayname FROM #__blog_post p LEFT JOIN #__user u ON u.id = author_id WHERE blog_id = %d AND p.is_draft = 0 ORDER BY created DESC LIMIT 0,5", $bID);
	$tagName = '';
}

// Ignore empty blogs
if(!$res_posts || ($ssc_database->number_rows() == 0)) {
	ssc_not_found();
	return;
}

// Open file handles
$bID = $data['id'];

// Retrieve first lot of posts (for updated date, etc)
$dat = $ssc_database->fetch_assoc($res_posts);

// Write file headers
// RSS
/*
echo <<< FEED
<?xml version="1.0" encoding="UTF-8"? >
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
	echo date("r", $dat['modified']) , '</pubDate>
    <lastBuildDate>' , date("r", $dat['modified']) , '</lastBuildDate>
';
*/
	// Atom
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\" xml:base=\"$ssc_site_url/atom$tagName\">
  <title type=\"text\">$data[name]</title>
  <subtitle type=\"text\">$data[description]</subtitle>
  <link rel=\"self\" type=\"application/atom+xml\" href=\"$ssc_site_url/atom$tagName\" />
  <link rel=\"alternate\" type=\"text/html\" href=\"$ssc_site_url\" />
  <updated>" . date("c", $dat['modified']) . "</updated>
  <author>
    <name>Scott</name>
  </author>
  <id>$ssc_site_url/atom$tagName</id>
";

	// Loop through all posts
	// Do loop due to the first read earlier on
	do {
		// Get date
		$date =	date("Y/m/d", $dat['created']);
		// Echo post
	/*	echo <<< ITEM
    <item>
      <title>$dat[title]</title>
      <link>$ssc_site_url/$date/$dat[urltext]</link>
      <guid>$ssc_site_url/id/$dat[id]</guid>
      <description>
ITEM
; */ // <?php  // <-- Ensure GUI happy again

		// Get markup version for Atom
		$dat['body'] = sscText::convert($dat['body']);
		// Strip tags for a text-only preview
		$stripped = preg_replace('/<[^<|>]+>?/', '', $dat['body']);
		
		// Now shorten it
		$stripped = substr($stripped, 0, 350);
		if (strlen($stripped) == 350){
			// And indicate there's more if appropriate
			$stripped = substr($stripped, 0, strrpos($stripped, ' ')) . ' [...]';
		}

		// Write post to RSS
	/*	echo $stripped;
		echo "</description>
      <pubDate>".date("r",$dat['created'])."</pubDate>
    </item>
";*/

		// Prepare date for Atom
	    $iso = date("c", $dat['created']);
		$isou = date("c", $dat['modified']);
		// Write to Atom
		echo "<entry>
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
";
//<![CDATA[ ... ]]>
	} while ($dat = $ssc_database->fetch_assoc($res_posts));
	
	// Finish each file markup, and close the handles
/*	echo "  </channel>
</rss>";*/
echo "</feed>";
/*	fwrite($ap,"</feed>");
	fclose($ap);*/
