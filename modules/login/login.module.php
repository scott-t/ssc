<?php
/**
 * Module to control user authentication
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * User Level: -1 Guest
 */
define('SSC_USER_GUEST', -1);

/**
 * User Level: 1 Root Super User
 */
define('SSC_USER_ROOT', 1);

/**
 * Implements module_init hook
 */

function login_init(){
	core_debug(array('title'=>'login module', 'body'=>'init hook'));
	
	session_set_save_handler('_login_sess_open', '_login_sess_close', '_login_sess_read', '_login_sess_write', '_login_sess_destroy', '_login_sess_clean');
	
	session_start();
	
	if (!isset($_SESSION['username'], $_SESSION['userlevel'], $_SESSION['useragent'])){ 
		session_regenerate_id();
		$_SESSION['username'] = 'Guest';
		$_SESSION['userlevel'] = SSC_USER_GUEST;
		$_SESSION['useragent'] = md5($_SERVER['HTTP_USER_AGENT']);
	} elseif ($_SESSION['useragent'] != md5($_SERVER['HTTP_USER_AGENT'])){
		login_logout();
	}
}

/**
 * Implements module_close
 */
function login_close(){
	session_write_close();
}

/**
 * Logs the current user out
 */
function login_logout(){
	session_destroy();
	session_regenerate_id();
	$_SESSION['username'] = 'Guest';
	$_SESSION['userlevel'] = SSC_USER_GUEST;
	$_SESSION['useragent'] = md5($_SERVER['HTTP_USER_AGENT']);
}

/**
 * Session open handler
 */
function _login_sess_open(){
	global $ssc_database;

	return false;
}

/**
 * Session close handler
 */
function _login_sess_close(){
	global $ssc_database;

	return true;
}

/**
 * Session read handler
 * @param string $id Session identifier
 * @return string Session data
 */
function _login_sess_read($id){
	global $ssc_database;

	if ($result = $ssc_database->query("SELECT data FROM #__session WHERE id = '%s' LIMIT 1", $id)){
		if ($ssc_database->number_rows()){
			$data = $ssc_database->fetch_assoc($result);
			return $data['data'];
		}
	}
	
	return '';
}

/**
 * Session write handler
 * @param string $id Session identifier
 * @param string $data Session data
 */
function _login_sess_write($id, $data){
	global $ssc_database, $SSC_SETTINGS;
	
	switch ($SSC_SETTINGS['database']['engine']){
	case 'mysqli':
	case 'mysql':
		return $ssc_database->query("REPLACE INTO #__session (id, data) VALUES ('%s', '%s')", $id, $data);
		break;
	default:
		return false;
	}
}

/**
 * Session destroy handler
 * @param string $id Session identifier
 */
function _login_sess_destroy($id){
	return $ssc_database->query("DELETE FROM #__session WHERE id = '%s' LIMIT 1", $id);
}

/**
 * Session clean handler
 * @param int $max Maximum age for a session
 */
function _login_sess_clean($max){
	return $ssc_database->query("DELETE FROM #__session WHERE access < '%d'", time() - $max);
}