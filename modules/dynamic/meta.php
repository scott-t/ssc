<?php
/**
 * Dynamic page module.
 *
 * Link in the appropriate rss feeds
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $database, $sscConfig_absPath, $sscConfig_webPath;

$database->setQuery(sprintf("SELECT id FROM #__dynamic WHERE nav_id = %d LIMIT 1",$_GET['pid']));
if($database->query() && $data = $database->getAssoc()){
	$path = $sscConfig_absPath.'/modules/dynamic/';
	if(file_exists($path.'rss-'.$data['id'].'.xml'))
		echo '<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="rss-',$data['id'],'.xml" />';
	if(file_exists($path.'atom-'.$data['id'].'.xml'))
		echo '<link rel="alternate" type="application/rss+xml" title="Atom 0.3" href="rss-',$data['id'],'.xml" />';
	
}