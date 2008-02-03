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
	global $ssc_site_url, $ssc_database, $ssc_user;
	
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
		$ssc_user = _login_anonymous;
		ssc_add_message(SSC_MSG_INFO, t('Do Logout'));
		ssc_redirect('/');
		break;
	case '':
		
	default:
		ssc_not_found();
		break;
	}

	return $out;
}

/**
 * Implements module_widget
 */
function login_widget($type){
	global $ssc_user;

	if ($_GET['q'] == 'user/login')
		return;
		
	if ($ssc_user->gid == SSC_USER_GUEST){
		return array('title' => t('User login'), 'body' => ssc_generate_form('login_form'));
	}
	else{
		$menu = array();
		$menu[] = array('t' => t('Edit profile'), 'p' => '/user/profile');
		$menu[] = array('t' => t('Log out'), 'p' => '/user/logout');
		$links = nav_widget($menu);
		return array('body' => t('Welcome, !name', array('!name' => $ssc_user->fullname)) . $links['body']);
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
 * @return bool Whether or not the form values are valid
 */
function login_form_validate(){
	if (empty($_POST['user']) || empty($_POST['pass'])){
		ssc_add_message(SSC_MSG_CRIT, t("Please fill in both username and password"));
		return false;
	}
	
	return true;
}	

/**
 * Form valided.  Log the user in
 */
function login_form_submit(){
	// Perform user login
	global $ssc_database, $ssc_user;
	
	if ($ssc_user->uid > 0){
		ssc_add_message(SSC_MSG_WARN, t('You are already logged in as !name! To re-login, logout first.', array('!name' => $ssc_user->username)));
		return;
	}
	
	$pass = new PasswordHash(8, true);
	
	// Get user details
	$result = $ssc_database->query("SELECT id uid, password, ip, fullname, username, gid, accessed FROM #__user WHERE username = '%s' LIMIT 1", $_POST['user']);
	if (!$result){
		return;
	}
	if (!($user = $ssc_database->fetch_object($result))){
		ssc_add_message(SSC_MSG_CRIT, t('Invalid user name or password'));
		return;
	}
	
	// Username is good - check password
	if (!$pass->CheckPassword($_POST['pass'], $user->password)){
		ssc_add_message(SSC_MSG_CRIT, t('Invalid user name or password'));
		return;
	}
	
	// Password good too - valid credentials
	session_regenerate_id();
	
	ssc_add_message(SSC_MSG_INFO, t('Welcome, !user.<br />You last logged in on !date at !time from !ip', 
								array(	'!user' => $user->fullname,
										'!date' => date(ssc_var_get('date_long', SSC_DATE_LONG), $user->accessed),
									 	'!time' => date(ssc_var_get('time_full', SSC_TIME_FULL), $user->accessed),
										'!ip' => $user->ip)));
	unset($user->password);
	unset($user->accessed);
	unset($user->ip);
	$ssc_user = $user;
	$ssc_user->useragent = md5($_SERVER['HTTP_USER_AGENT']);
	
	$ssc_database->query("UPDATE #__user SET accessed = %d, ip = '%s', useragent = '%s' WHERE id = %d LIMIT 1", time(), $_SERVER['REMOTE_ADDR'], $ssc_user->useragent, $ssc_user->uid);
}


/**
 * 
 
function login_form_handler(){
	global $ssc_database;
	
	$pass = new PasswordHash(8, true);
	$i = 1 / 0;
	echo $i;
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
	global $ssc_user;
	_login_kill_session();
	session_regenerate_id();
	$ssc_user = _login_anonymous();
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
 * Anonymous user data population
 * @return object Anonymous user
 */
function _login_anonymous($sid = ''){
	// Create new user object
	$user = new stdClass();

	ssc_debug(array('title'=>'auth', 'body' => 'anonyuser'));
	
	// Populate
	$user->id = SSC_USER_GUEST;
	$user->username = t('Guest');
	$user->fullname = t('Guest');
	$user->gid = SSC_USER_GUEST;
	$user->useragent = '';
	
	// Return
	return $user;
}

/**
 * Session open handler
 */
function _login_sess_open(){
	//global $ssc_database;

	return true;
}

/**
 * Session close handler
 */
function _login_sess_close(){
	//global $ssc_database;

	return true;
}

/**
 * Session read handler
 * @param string $id Session identifier
 * @return string Session data
 */
function _login_sess_read($id){
	global $ssc_database, $ssc_user;
	
	// Cookieless users / bots
	if (empty($_COOKIE[session_name()])){
		$ssc_user = _login_anonymous();
		// "Empty" session data to avoid saving at other end of script
		return '';
	}
	
	// Proper user
	if ($result = $ssc_database->query("SELECT s.data, s.uid, u.useragent, u.username, u.fullname, u.gid FROM #__session s LEFT JOIN #__user u ON s.uid = u.id WHERE s.id = '%s' LIMIT 1", $id)){
		// Invalid session id
		if (!($ssc_user = $ssc_database->fetch_object($result))){
			$ssc_user = _login_anonymous();
			return '';
		}
	
		$data = $ssc_user->data;
		unset($ssc_user->data);
		// Check if logged in user
		if (!$ssc_user->uid){
			// Not logged in?
			$ssc_user = _login_anonymous();
			return $data;
		}
		
		// Validate logged in user
		if ($ssc_user->useragent != md5($_SERVER['HTTP_USER_AGENT'])){
			// Session hijack?
			ssc_debug(array('title'=>'Session Management', 'body' => 'Session hijacking? <br />Wanted ' . $ssc_user->useragent . ' but got ' . md5($_SERVER['HTTP_USER_AGENT'])));
			$ssc_user = _login_anonymous();
			return '';
		}	
			
		// Seems to be valid
		return $data;

	}
	
	// Fallthough, probably from bad DB
	$ssc_user = _login_anonymous();
	return '';
}

/**
 * Session write handler
 * @param string $id Session identifier
 * @param string $data Session data
 */
function _login_sess_write($id, $data){
	global $ssc_database, $SSC_SETTINGS, $ssc_user;
		
	// Don't store cookieless browsers
	if (empty($_COOKIE[session_name()]) || empty($data))
		return true;
					
	$ret = false;
	switch ($SSC_SETTINGS['db-engine']){
	case 'mysqli':
	case 'mysql':
		$ret = $ssc_database->query("REPLACE INTO #__session (id, data, uid) VALUES ('%s', '%s', %d)", $id, $data, $ssc_user->uid);
		break;
	default:
		return false;
	}
	
	return $ret;
}

/**
 * Session destroy handler
 * @param string $id Session identifier
 */
function _login_sess_destroy($id){
	global $ssc_database;
	return $ssc_database->query("DELETE FROM #__session WHERE id = '%s' LIMIT 1", $id);
}

/**
 * Session clean handler
 * @param int $max Maximum age for a session
 */
function _login_sess_clean($max){
	global $ssc_database;
	return $ssc_database->query("DELETE FROM #__session WHERE access < '%d'", time() - $max);
}