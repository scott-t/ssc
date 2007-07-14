<?php
/**
 * Events module
 *
 * List events
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $sscConfig_absPath, $database;

include($sscConfig_absPath . '/modules/events/events.functions.php');
$database->setQuery("SELECT `key`, value FROM #__module_config WHERE `key` LIKE 'events_%' ORDER BY id ASC");
$database->query();
for($i = 0; $i < 8; $i++){
	$data=$database->getAssoc();
	$d[$data['key']] = $data['value'];
}
echo '<h1>',$d['events_title'],'</h1>';
if($d['events_title_recent'] != ''){
	echo '<h2>',$d['events_title_recent'],'</h2>';
	showEvent($d['events_recent_start'],$d['events_current_start']);
}
if($d['events_title_current'] != ''){
	echo '<h2>',$d['events_title_current'],'</h2>';
	showEvent($d['events_current_start'],$d['events_current_end']);
}
if($d['events_title_future'] != ''){
	echo '<h2>',$d['events_title_future'],'</h2>';
	showEvent($d['events_current_end'],$d['events_future_end']);
}
?>
