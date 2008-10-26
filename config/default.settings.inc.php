<?php
/**
 * Default program settings.
 * This file should be copied for use as a template for other domains as required.
 * @package SSC
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

$SSC_SETTINGS['db-engine']   = '';
$SSC_SETTINGS['db-host']     = '';
$SSC_SETTINGS['db-port']     = '';
$SSC_SETTINGS['db-user']     = '';
$SSC_SETTINGS['db-password'] = '';
$SSC_SETTINGS['db-database'] = '';
$SSC_SETTINGS['db-prefix']   = array(
										'default' => 'ssc'
									);

$SSC_SETTINGS['no-www']		= false;
$SSC_SETTINGS['lang']['tag'] = "en-au";

$SSC_SETTINGS['theme']['name'] = 'php';