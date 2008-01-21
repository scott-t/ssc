<?php
/**
 * Australian English language file
 * @package SSC
 * @subpackage Language
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

// Language definitions
define("SSC_LANG_USER_GUEST", 'Guest');
define("SSC_LANG_USER_DOLOGIN", 'Login');
define("SSC_LANG_USER_LOGIN", 'User Login');
define("SSC_LANG_USER_USERNAME", 'Username');
define("SSC_LANG_USER_PASSWORD", 'Password');
define("SSC_LANG_USER_BAD_USER", 'Invalid username or password');

define("SSC_LANG_WELCOME", 'Welcome, ');

define("SSC_LANG_WELCOME_PAGE", '<h1>Welcome, %s</h1>
<p>You last logged in to your account on %s from %s.<br /><br />The time now is currently %s.</p>
<p>Continue to the <a href="%s">admin</a> page or your return to your <a href="%s">original</a> location.</p>');

define("SSC_LANG_NOT_FOUND", 'The page you requested does not exist');

define("SSC_LANG_DATE_FORMAT", 'D M j, Y g:i:s a');