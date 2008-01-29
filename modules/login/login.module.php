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
		ssc_set_title('User login');
		$out = ssc_generate_form('login_form', 'main');
		break;
	
	case 'logout':
		// Log the user out
		ssc_add_message(SSC_MSG_INFO, t('Do Logout'));
		break;
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
function login_content_mini($type){
	if ($_GET['q'] == 'user/login')
		return;
		
	if ($_SESSION['userlevel'] == SSC_USER_GUEST){
		return array('User login', ssc_generate_form('login_form'));
	}
	else{
		return t('Welcome, !name', array('!name' => $_SESSION['username']));
	}
	
}

/**
 * Generate login form's
 */
function login_form($type = 'mini'){
	$form = array();
	
	// Build base form
	$form['#action'] = '';
	$form['#method'] = 'post';	
	$form['user'] = array(
									'#title' => t('Username'),
									'#type' => 'text',
									'#required' => true,
									'#maxlen' => 20
									);
	$form['pass'] = array(
									'#title' => t('Password'),
									'#type' => 'password',
									'#required' => true,
									);
	$form['submit'] = array(
									'#type' => 'submit',
									'#value' => t('Log In')
									);
	
	switch($type){
	case 'mini':
		$form['user']['#size'] = 15;
		$form['pass']['#size'] = 15;
		break;
	case 'main':
		$form['user']['#description'] = t('Your username');
		$form['pass']['#description'] = t('Your password associated with your username');
		break;
	}
	return $form;
	
}

/**
 * Validate the form for invalid values
 */
function login_form_validate($values){
	
}

/**
 * Form valided.  Log the user in
 */
function login_form_submit($values){
	/*
	 	out .= t('<h1>Welcome, !name</h1><p>You last logged in to your account on !date from !ip.<br /><br />The time now is currently !datenow.</p><p>Continue to the <a href="!admin">admin</a> page or your return to your <a href="!refer">original</a> location.</p>', 
	 		array('!name' => $_SESSION['username'],
	 			  '!date' =>  date(SSC_LANG_DATE_FORMAT, $data['accessed']),
	 			  '!ip' => $data['ip'],
	 			  '!datenow' => date(SSC_LANG_DATE_FORMAT, $now),
	 			  '!admin' => $ssc_site_url . '/admin',
	 			  '!refer' => $_SERVER['HTTP_REFERER']));*/
}


/**
 * Implements module_form_handler
 */
function login_form_handler(){
	global $ssc_database;
	
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