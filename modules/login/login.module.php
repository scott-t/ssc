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
	global $ssc_site_path;
	
	// Include password encryption library
	require_once "$ssc_site_path/lib/phpass.lib.php";
	
	session_set_save_handler('_login_sess_open', '_login_sess_close', '_login_sess_read', '_login_sess_write', '_login_sess_destroy', '_login_sess_clean');
	
	session_start();
	
	if (!isset($_SESSION['username'], $_SESSION['userlevel'], $_SESSION['useragent'], $_SESSION['id'])){ 
		//session_regenerate_id();
		$_SESSION['id'] = 0; 
		$_SESSION['username'] = t('Guest');
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
 * Implements module_content
 */
function login_content(){
	global $ssc_site_url, $ssc_database;
	
	$out = '';
	
	if ($_GET['path'] != 'user'){
		ssc_not_found();
		return;
	}
	
	switch ($_GET['param']){
	case 'login':
		// Show login details
		$result = $ssc_database->query("SELECT accessed, ip FROM #__user WHERE id = %d LIMIT 1", $_SESSION['id']);
		if (!($data = $ssc_database->fetch_assoc($result))){
			ssc_add_message(SSC_MSG_CRIT, SSC_LANG_USER_BAD_USER);
			break;
		}

		// Output welcome
		$now = time();
		$out = sprintf(SSC_LANG_WELCOME_PAGE, $_SESSION['username'],  date(SSC_LANG_DATE_FORMAT, $data['accessed']), $data['ip'], date(SSC_LANG_DATE_FORMAT, $now), $ssc_site_url . '/admin', $_SERVER['HTTP_REFERER']);
		if (isset($_POST['form-id']))
			$ssc_database->query("UPDATE #__user SET accessed = %d, ip = '%s' WHERE id = %d", $now, $_SERVER['REMOTE_ADDR'], $_SESSION['id']); 
		break;
	
	case 'logout':
		// Log the user out
		ssc_add_message(SSC_MSG_INFO, t('Do Logout'));
		
	case '':
		
	default:
		ssc_not_found();
		break;
	}
	
	return $out;
}

/**
 * Implements module_content_mini
 */
function login_content_mini($type = 0){
	if ($_SESSION['userlevel'] == SSC_USER_GUEST){
		$form = array();
		$form['id'] = 'login-side';
		$form['action'] = '/user/login';
		$form['method'] = 'post';
		$form['fields'] = array();
		
		$form['fields'][0]['legend'] = t('User Login');
		$form['fields'][0]['user'] = array(
										'label' => t('Username') . ': ',
										'type' => 'text',
										'size' => 15,
										'maxlen' => 20
										);
		$form['fields'][0]['pass'] = array(
										'label' => t('Password') . ': ',
										'type' => 'password',
										'size' => 15
										);
		$form['fields'][0]['submit'] = array(
										'type' => 'submit',
										'value' => t('Log In')
										);
		ssc_generate_form($form);
	}
	else{
		echo t('Welcome'), $_SESSION['username'];
	}
	
}

/**
 * Implements module_form_handler
 */
function login_form_handler(){
	global $ssc_database;
	
	// Branch depending on form
	switch ($_POST['form-id']){
	case 'login-main':
	case 'login-side':
		$pass = new PasswordHash(8, true);
		
		// Attempt to find relevant username
		$result = $ssc_database->query("SELECT id, accessed, password, fname, gid FROM #__user WHERE name = '%s' LIMIT 1", $_POST['user']);
		if ($ssc_database->number_rows() < 1){
			// Bad user details.  Tell user
			ssc_add_message(SSC_MSG_CRIT, t('Invalid user name or password'));
			return;
		}
		
		// Check password
		$data = $ssc_database->fetch_assoc($result);
		if (!$pass->CheckPassword($_POST['pass'], $data['password'])){
			// Was wrong!
			ssc_add_message(SSC_MSG_CRIT, t('Invalid user name or password'));
			return;
		}
			
		// Correct details
		_login_kill_session();
		session_regenerate_id();
		$_SESSION['username'] = $data['fname'];
		$_SESSION['userlevel'] = $data['gid'];
		$_SESSION['id'] = $data['id'];
	}
}

/**
 * Logs the current user out
 */
function login_logout(){
	_login_kill_session();
	session_regenerate_id();
	$_SESSION['id'] = 0;
	$_SESSION['username'] = t('Guest');
	$_SESSION['userlevel'] = SSC_USER_GUEST;
	$_SESSION['useragent'] = md5($_SERVER['HTTP_USER_AGENT']);
}

/**
 * Destroys an active session
 */
function _login_kill_session(){
	// Empty variables
	$_SESSION = array();
	
	// Remove remote SID
	if (isset($_COOKIE[session_name()])){
		ssc_cookie(session_name(), '', -3600);
	}
	
	// Destroy the session
	session_destroy();
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
	
	switch ($SSC_SETTINGS['db-engine']){
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