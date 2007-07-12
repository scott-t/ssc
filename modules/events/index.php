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

global $sscConfig_absPath;

include($sscConfig_absPath . '/modules/events/events.functions.php');

echo '<h1>Events</h1><h2>Recent Events</h2>';
showEvent(null,"Last week");
echo '<h2>Current Events</h2>';
showEvent("Last Week","Next Week");
echo '<h2>Upcoming Events</h2>';
showEvent("Next Week")

?>
