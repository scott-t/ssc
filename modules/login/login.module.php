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
	if (ssc_load_library('phpass')){
		session_set_save_handler('_login_sess_open', '_login_sess_close', '_login_sess_read', '_login_sess_write', '_login_sess_destroy', '_login_sess_clean');
	
		session_start();
	}
	else{
		die ("module bad");
	}
	
}

/**
 * Implements module_close
 */
function login_close(){
	session_write_close();
}

/**
 * Checks for authorisation to access the current page
 * @param string $module Module keyname to check access for
 * @return bool Whether or not access is allowed
 */
function login_check_auth($module){
	global $ssc_database, $ssc_user;
	// Permission storage
	static $perm;
	
	if ($ssc_user->gid == SSC_USER_ROOT)
		return true;
	
	if (!$perm){
		$perm = array();
		$result = $ssc_database->query("SELECT filename FROM #__permission p LEFT JOIN #__module m ON module_id = m.id WHERE group_id = %d", $ssc_user->gid);
		while ($data = $ssc_database->fetch_assoc($result)){
			$perm[] = $data['filename'];
		}
	}
	
	return in_array($module, $perm);
	
}

/**
 * Implements module_admin
 */
function login_admin(){
	$out = '';

	// Work out what we want to do 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
		$out = ssc_generate_form('login_profile');
		break;
		
	case 'page':
		// Allow for paging
		array_unshift($_GET['param'], 'page');
	case '':
		// Main admin page - list users and groups
		// 
		$out = ssc_admin_table("Users", 
			"SELECT u.id AS ID, username, fullname, g.name AS group_name, email 
			FROM #__user u LEFT JOIN #__group g ON g.id = u.gid 
			ORDER BY username ASC", null, 
			array('link' => 'username', 'linkpath' => '/admin/login/edit/',
					'perpage' => 30, 'pagelink' => '/admin/login/page/'));
		$out .= ssc_admin_table("Permission groups", 
			"SELECT id AS ID, name, description 
			FROM #__group g ORDER BY name ASC", null, 
			array('link' => 'name', 'linkpath' => '/admin/login/gedit/'));
		break;
	default:
		ssc_not_found();
		break;
	}
	
	return $out;
}

/**
 * Implements module_cron
 */
function login_cron(){
	global $ssc_database;
	// Delete 3 day old unaccessed accounts
	$ssc_database->query("DELETE FROM #__user WHERE created < %d AND accessed = 0", time() - (60 * 60 * 24 * 3));
$ssc_database->query("DELETE FROM #__session WHERE access < FROM_UNIXTIME('%d')", time() - (60 * 60 * 2));
$ssc_database->query("OPTIMIZE TABLE #__session");
}

/**
 * Implements module_content
 */
function login_content(){
	global $ssc_site_url, $ssc_database, $ssc_user;
	
	$out = '';
	
	if ($_GET['path'] != '/user'){
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
		
	case 'forgot':
		ssc_set_title('Reset a forgotten password');
		$out = ssc_generate_form('login_forgotten');
		break;
		
	case 'register':
		// Ensure user registrations are allowed
		if (!ssc_var_get('login_user_create', true))
			ssc_not_found();
		
		ssc_set_title('Register an account');
			
		// They are, so show from
		$out = ssc_generate_form('login_registration');
		break;
		
	case 'profile':
		$out = ssc_generate_form('login_profile');
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
		// Do we show the login box?
		if (!ssc_var_get('login_show_login', true))
			return;
			
		$menu[] = array('t' => t('Forgotten password'), 'p' => '/user/forgot', 'h' => t('Reset the password on an account'));
			
		if (ssc_var_get('login_user_create', true))
			$menu[] = array('t' => t('Create new account'), 'p' => '/user/register');
		
		$links = nav_widget($menu);
		return array('title' => t('User login'), 'body' => ssc_generate_form('login_form') . $links['body']);
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
 * Generate registration form
 */
function login_registration(){
	$form = array(	'#action' => '',
					'#method' => 'post');
	
	$form['user'] = array(	'#title' => t('Username'),
							'#type' => 'text',
							'#required' => true,
							'#maxlen' => 20,
							'#description' => t('Requested username.  Alphanumeric characters only')
							);
							
	$form['email'] = array(	'#title' => t('Email address'),
							'#type' => 'text',
							'#required' => true,
							'#maxlen' => 50,
							'#description' => t('Your registration details will be sent to this address.  This address will remain private.')
							);
							
	$form['submit'] = array('#type' => 'submit',
							'#value' => t('Create account'));
							
	return $form;
}

/**
 * Validate registration
 */
function login_registration_validate(){
	if (!ssc_var_get('login_user_create', true))
		return false;

	if (empty($_POST['user']) || empty($_POST['email'])){
		ssc_add_message(SSC_MSG_CRIT, t('Both username and email fields need to be filled in'));
		return false;
	}
	
	$email = $_POST['email'] = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
	
	if (empty($email) || !$email || strpos($email, "\n") !== false || strpos($email, ":") !== false){
		ssc_add_message(SSC_MSG_CRIT, t('The email address provided was invalid'));
		return false;
	}
	
	global $ssc_database;
	$result = $ssc_database->query("SELECT id FROM #__user WHERE username = '%s' OR email = '%s' LIMIT 1", $_POST['user'], $email);
	if ($ssc_database->number_rows()){
		ssc_add_message(SSC_MSG_CRIT, t('The provided username or email address has already been used to register.  Have you !forgotten your password?', array('!forgotten' => l('forgotten', '/user/forgot'))));
		return false;
	}
	
	return true;
}

/**
 * Registration submission
 */
function login_registration_submit(){
	global $ssc_site_url, $ssc_database;
	 
	if (!ssc_load_library('sscMail')){
		ssc_add_message(SSC_MSG_CRIT, t("An error creating your account has occurred"));
		return false;
	}
	
	$pass = substr(base64_encode(md5($_POST['user'] . mt_rand() . $_SERVER['SERVER_NAME'])), 0, 16);
	$hash = new PasswordHash(8, true);
	
	$mail = new sscMail($_POST['email'], t("#server account registration", array('#server' => $_SERVER['SERVER_NAME'])));
	
	if (!$mail){
		ssc_add_message(SSC_MSG_CRIT, t("An error creating your account has occurred"));
		return false;
	}
	
	$message = t("#user,\n\nThank you for registering at #server.\nYou can now log in using the following credentials\nat #url\n\n" .
						"  Username: #user\n" .
						"  Password: #pass\n\n" .
						"You will then be directed to a form where you may update your details and set a new\n" .
						"password.  If you do not login within 3 days of signing up, your account will be\n" .
						"deleted and you will need to recreate this account." . 
						"\n\nIf you receive this message in error, please ignore it and you will not receive\n" .
						"any further messages from us.",
				array(	"#user" => $_POST['user'],
						"#server" => $ssc_site_url,
						"#url" => $ssc_site_url . "/user/login",
						"#pass" => $pass));	
	
	$pass = $hash->HashPassword($pass);
				
	$result = $ssc_database->query("INSERT INTO #__user (username, fullname, displayname, email, gid, ip, useragent, created, accessed, password, data) VALUES ('%s', '', '', '%s', 0, '', '', %d, 0, '%s', '')", $_POST['user'], $_POST['email'], time(), $pass);
	if ($result){
		$sent = $mail->send($message);
		
		if ($sent)
			ssc_add_message(SSC_MSG_INFO, t("Success.  An email has been sent to your nominated address with further details."));
		else {
			ssc_add_message(SSC_MSG_CRIT, t("An error creating your account has occurred"));
			$ssc_database->query("DELETE FROM #__user WHERE id = %d LIMIT 1", $ssc_database->last_id());
		}
			
	}
	else{
		ssc_add_message(SSC_MSG_CRIT, t("An error creating your account has occurred"));
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
	
	if ($ssc_user->id > 0){
		ssc_add_message(SSC_MSG_WARN, t('You are already logged in as !name! To re-login, logout first.', array('!name' => $ssc_user->username)));
		return;
	}
	
	$pass = new PasswordHash(8, true);
	
	// Get user details
	$result = $ssc_database->query("SELECT id, password, ip, fullname, username, gid, accessed FROM #__user WHERE username = '%s' LIMIT 1", $_POST['user']);
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
	session_regenerate_id(true);
	
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
	
	$ssc_database->query("UPDATE #__user SET accessed = %d, ip = '%s', useragent = '%s' WHERE id = %d LIMIT 1", time(), $_SERVER['REMOTE_ADDR'], $ssc_user->useragent, $ssc_user->id);
	
	if ($_GET['q'] == 'user/login')
		ssc_redirect('');
}

/**
 * Form to reset a lost password
 */
function login_forgotten(){
	$form = array('#action' => '', '#method' => 'post');
	$form['name'] = array(	'#type' => 'text',
							'#title' => t('Username'),
							'#description' => t('Username for which you wish to recover password for.  An email will be sent to your nominated email account.'));
	$form['submit'] = array('#type' => 'submit',
							'#value' => t('Reset password'));
	
	return $form;
}

/**
 * Forgotten password recovery validation
 */
function login_forgotten_validate(){
	if (empty($_POST['name'])){
		ssc_add_message(SSC_MSG_CRIT, t('You need to fill in the username to recover details for.'));
		return false;
	}
	
	return true;
}

/**
 * Forgotten password recovery submission
 */
function login_fogotten_submit(){
	global $ssc_site_url, $ssc_database;
	 
	if (!ssc_load_library('sscMail')){
		ssc_add_message(SSC_MSG_CRIT, t("An error resetting your account password has occurred"));
		return false;
	}
	
	// Retrieve email for user
	$result = $ssc_database->query("SELECT id, username, email FROM #__user WHERE username = '%s' LIMIT 1", $_POST['name']);
	if (!($data = $ssc_database->fetch_object($result))){
		ssc_add_message(SSC_MSG_CRIT, t('The username specified does not exist'));
		return false;
	}
	
	// Set new password
	$pass = substr(base64_encode(md5($_POST['name'] . mt_rand() . $_SERVER['SERVER_NAME'])), 0, 16);
	$hash = new PasswordHash(8, true);
	
	$mail = new sscMail($_POST['email'], t("#server password reset", array('#server' => $_SERVER['SERVER_NAME'])));
	
	if (!$mail){
		ssc_add_message(SSC_MSG_CRIT, t("An error resetting your account password has occurred"));
		return false;
	}
	
	$message = t("#user,\n\nA password reset was placed at #server for your username,\nand as such, your password has been reset to the following details:\n\n" .
						"  Username: #user\n" .
						"  Password: #pass\n\n" .
						"You can use these details to log in and then change your password\n" .
						"from your profile page.\n\n" .
						"If you did not authorize this, you are still requird to use the password\n" .
						"above to login.",
				array(	"#user" => $_POST['user'],
						"#server" => $ssc_site_url,
						"#url" => $ssc_site_url . "user/login",
						"#pass" => $pass));	
	
	$pass = $hash->HashPassword($pass);
				
	$result = $ssc_database->query("UPDATE #__user SET password = '%s' WHERE id = %d", $pass, $data->id);
	if ($result){
		$sent = $mail->send($message);
		
		if ($sent)
			ssc_add_message(SSC_MSG_INFO, t("Success.  An email has been sent to your nominated address with further details."));
		else {
			ssc_add_message(SSC_MSG_CRIT, t("An error occurred sending the email.  Please contact an administrator."));
		}
			
	}
	else{
		ssc_add_message(SSC_MSG_CRIT, t("An error resetting your account password has occurred"));
	}
}

/**
 * User profile edit
 */
function login_profile(){
	global $ssc_database;
	
	// Are we superprofile editing?
	if ($_GET['path'] == '/admin'){
		$uid = (int)array_shift($_GET['param']);
		if ($uid == 0){
			// New user
			$ssc_user = new StdClass();
			// Set up neat default values
			$ssc_user->fullname = t('New user');
			$ssc_user->gid = 0;
			$ssc_user->id = 0;
		}
		else{
			// Existing - need to attempt retrieval
			$result = $ssc_database->query("SELECT id, username, fullname, displayname, email, gid FROM #__user WHERE id = %d LIMIT 1", $uid);
			if (!$result)
				ssc_add_message(SSC_MSG_CRIT, t('Error retrieving user details'));
				
			$ssc_user = $ssc_database->fetch_object($result);
			if (!$ssc_user){
				ssc_not_found();
			}
		}
	}
	else {
		// Just self-editing
		global $ssc_user;
	}

	ssc_set_title($ssc_user->fullname);
	
	$form = array(	'#action' => '',
					'#method' => 'post');
	
	$fieldset = array(	'#type' => 'fieldset',
						'#title' => t('User details'),
						'#parent' => true);
	
	$fieldset['uid'] = array(	'#type' => 'hidden',
								'#value' => $ssc_user->id);
	$fieldset['user'] = array(	'#type' => 'text',
								'#value' => $ssc_user->username,
								'#maxlen' => 20,
								'#required' => true,
								'#title' => t('Username'),
								'#description' => t('Username used to log in with'));
	$fieldset['disp'] = array(	'#type' => 'text',
								'#value' => $ssc_user->displayname,
								'#maxlen' => 20,
								'#required' => true,
								'#title' => t('Display name'),
								'#description' => t('Name to display when shown on main page'));
	$fieldset['full'] = array(	'#type' => 'text',
								'#value' => $ssc_user->fullname,
								'#maxlen' => 30,
								'#required' => true,
								'#title' => t('Full name'),
								'#description' => t('Full name for administration uses'));
	$fieldset['email'] = array(	'#type' => 'text',
								'#value' => $ssc_user->email,
								'#maxlen' => 50,
								'#size' => 30,
								'#required' => true,
								'#title' => t('Email address'),
								'#description' => t('Required for administration uses'));
	
	// Populate list
	$options = array(-1 => 'Guest');
	$result = $ssc_database->query("SELECT id, name FROM #__group WHERE id > 0 ORDER BY name ASC");
	while ($data = $ssc_database->fetch_assoc($result)){
		$options[$data['id']] = $data['name'];
	}
	
	// Admin only the permission
	if ($_GET['path'] == '/admin')
		$fieldset['grp'] = array(	'#type' => 'select',
									'#value' => $options,
									'#selected' => $ssc_user->gid,
									'#title' => t('Permission group'),
									'#description' => t('Group for the user to belong to'));
		
	$submit = array('#type' => 'submit',
					'#value' => t('Save'));
	//$fieldset['sub'] = $submit;
	$form['details'] = $fieldset;
	
	$fieldset = array(	'#type' => 'fieldset',
						'#title' => t('Update password'),
						'#parent' => true);
	
	// Choose whether we need users password or admin password	
	if ($_GET['path'] == '/admin')
		$fieldset['admin'] = array(	'#type' => 'password',
									'#title' => t('Admin password'),
									'#description' => t('Administrator password for verification'),
									'#required' => true);
	else
		$fieldset['old'] = array(	'#type' => 'password',
									'#title' => t('Current password'),
									'#description' => t('Current password for verification'),
									'#required' => true);
		
	$fieldset['n1'] = array(	'#type' => 'password',
								'#title' => t('New password'),
								'#description' => t('Password to change for user'),
								'#required' => true);
	
	$fieldset['n2'] = array(	'#type' => 'password',
								'#title' => t('Repeat new password'),
								'#description' => t('Repeat to avoid typos'),
								'#required' => true);
	$form['pass'] = $fieldset;
	$form['sub'] = $submit;
	return $form;
}

/**
 * Profile validation 
 */
function login_profile_validate(){
	global $ssc_user, $ssc_database;

	// Drop silently if guest
	if ($ssc_user->gid == SSC_USER_GUEST)
		return false;
	
	// Are we accessing via admin page?
	$admin = (($_GET['path'] == '/admin') && login_check_auth("login"));
	$_POST['uid'] = intval($_POST['uid']);
	// ********* Check required fields ************
	//
	if (!empty($_POST['n1'])){
		// No admin confirmation
		if ($admin && empty($_POST['admin'])){
			ssc_add_message(SSC_MSG_CRIT, t('To set a user\'s password, you must enter your own admin password to confirm the action'));
			return false;
		}
		//  No user confirmation
		elseif (!$admin && empty($_POST['old'])){ 
			ssc_add_message(SSC_MSG_CRIT, t('To set a new password, you must enter your current password'));
			return false;
		} 
		
		// No repeat confirmation
		if (empty($_POST['n2'])){
			ssc_add_message(SSC_MSG_CRIT, t('You must enter a repeat the password to confirm it'));
			return false;
		}
		
		// Passwords don't match
		if ($_POST['n2'] != $_POST['n1']){
			ssc_add_message(SSC_MSG_CRIT, t('The entered passwords did not match'));
			return false;
		}
		
		$result = $ssc_database->query("SELECT password FROM #__user WHERE id = %d LIMIT 1", $ssc_user->id);
		if (!($data = $ssc_database->fetch_assoc($result))){
			if ($admin)
				ssc_add_message(SSC_MSG_CRIT, t('Admin password was not correct'));
			else
				ssc_add_message(SSC_MSG_CRIT, t('Current password was not correct'));
			return false;
		}
		$hash = new PasswordHash(8, true);
		if (!$hash->CheckPassword(($admin ? $_POST['admin'] : $_POST['old']), $data['password'])){
			if ($admin)
				ssc_add_message(SSC_MSG_CRIT, t('Admin password was not correct'));
			else
				ssc_add_message(SSC_MSG_CRIT, t('Current password was not correct'));
			return false;
		}
	}
	elseif(!empty($_POST['n2'])) {
		ssc_add_message(SSC_MSG_CRIT, t('You must enter the new password in both boxes to change'));
		return false;
	}
	
	if (!empty($_POST['admin']) || !empty($_POST['old'])){
		// Both new's empty
		if (empty($_POST['n1']) && empty($_POST['n2'])){
			if ($admin)
				ssc_add_message(SSC_MSG_WARN, t('Admin password not required unless setting a new password'));
			else
				ssc_add_message(SSC_MSG_WARN, t('Current password not required unless setting a new password'));
		}	
	}
	
	// Check email
	$_POST['email'] = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
	if (empty($_POST['email'])){
		ssc_add_message(SSC_MSG_CRIT, t('The email address provided was invalid'));
		return false;
	}
	
	// Required fields
	if (empty($_POST['user']) || empty($_POST['disp']) || empty($_POST['full'])){
		ssc_add_message(SSC_MSG_CRIT, t('Username, display name and full name each need to be filled in'));
		return false;
	}
	
	if ($admin){
		// Verify user we are changing
		if ($_POST['uid'] > 0){
			// Only required for changing existing
			$result = $ssc_database->query("SELECT id, gid FROM #__user WHERE id = %d LIMIT 1", $_POST['uid']);
			if ($ssc_database->number_rows() != 1){
				// Impossible error under normal circumstances
				ssc_add_message(SSC_MSG_CRIT, "Invalid UID number");
				return false;
			}
			$data = $ssc_database->fetch_assoc($result);
			// Check if any other superusers
			if ($data['gid'] == SSC_USER_ROOT && intval($_POST['grp']) != SSC_USER_ROOT){
				$result = $ssc_database->query("SELECT id FROM #__user WHERE gid = %d LIMIT 2", SSC_USER_ROOT);
				if ($ssc_database->number_rows() < 2){
					ssc_add_message(SSC_MSG_CRIT, t('There needs to be at least 1 superuser at all times'));
					return false;
				}
			}
		}
		else{
			if (empty($_POST['admin']) || empty($_POST['n2']) || empty($_POST['n1'])){
				ssc_add_message(SSC_MSG_CRIT, t('You must fill out the password information to create a new user'));
				return false;
			}
		}
		
		
	}
	else {
		// Not admin profile edit
		unset($_POST['grp']);
		// Check we are only changing ourselves
		if ($_POST['uid'] != $ssc_user->id){
			// Impossible error under normal circumstances
			ssc_add_message(SSC_MSG_CRIT, "Invalid UID number");
			return false;
		}
	}
	return true;
}

/**
 * Profile edit saving
 */
function login_profile_submit(){
	global $ssc_database, $ssc_user;
	$admin = (($_GET['path'] == '/admin') && login_check_auth("login"));
	
	if (!empty($_POST['n2'])){
		$hash = new PasswordHash(8, true);
		$pass = $hash->HashPassword($_POST['n2']);
	}
	else{
		$pass = null;
	}

	// Ready to submit
	if ($_POST['uid'] <= 0 && $admin){
		// New user
		$result = $ssc_database->query("INSERT INTO #__user SET
		username = '%s', fullname = '%s', displayname = '%s', email = '%s',
		gid = %d, password = '%s', created = %d", $_POST['user'], $_POST['full'], 
		$_POST['disp'], $_POST['email'], $_POST['grp'], $pass, time());
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, t('There was an error submitting this form'));
			return;
		}
		
		$id = $ssc_database->last_id();
		ssc_add_message(SSC_MSG_INFO, t('User details saved'));
		ssc_redirect("/admin/login/edit/$id");
	}
	else{
		// Update existing
		if ($admin){
			if ($pass){
				$result = $ssc_database->query("UPDATE #__user SET
				username = '%s', fullname = '%s', displayname = '%s', email = '%s',
				gid = %d, password = '%s' WHERE id = %d", $_POST['user'], $_POST['full'], 
				$_POST['disp'], $_POST['email'], $_POST['grp'], $pass, $_POST['uid']);
				if ($result){
					ssc_add_message(SSC_MSG_INFO, t('User details saved'));
				}
				else {
					ssc_add_message(SSC_MSG_CRIT, t('There was an error submitting this form'));
				}
			}
			else {
			 	$result = $ssc_database->query("UPDATE #__user SET
				username = '%s', fullname = '%s', displayname = '%s', email = '%s',
				gid = %d WHERE id = %d", $_POST['user'], $_POST['full'], 
				$_POST['disp'], $_POST['email'], $_POST['grp'], $_POST['uid']);
				if ($result){
					ssc_add_message(SSC_MSG_INFO, t('User details saved'));
				}
				else {
					ssc_add_message(SSC_MSG_CRIT, t('There was an error submitting this form'));
				}
			}
		}
		else{
			if ($pass){
				$result = $ssc_database->query("UPDATE #__user SET
				username = '%s', fullname = '%s', displayname = '%s', email = '%s',
				password = '%s' WHERE id = %d", $_POST['user'], $_POST['full'], 
				$_POST['disp'], $_POST['email'], $pass, $ssc_user->id);
				if ($result){
					ssc_add_message(SSC_MSG_INFO, t('User details saved'));
				}
				else {
					ssc_add_message(SSC_MSG_CRIT, t('There was an error submitting this form'));
				}
			}
			else {
			 	$result = $ssc_database->query("UPDATE #__user SET
				username = '%s', fullname = '%s', displayname = '%s', email = '%s'
				WHERE id = %d", $_POST['user'], $_POST['full'], 
				$_POST['disp'], $_POST['email'], $ssc_user->id);
				if ($result){
					ssc_add_message(SSC_MSG_INFO, t('User details saved'));
				}
				else {
					ssc_add_message(SSC_MSG_CRIT, t('There was an error submitting this form'));
				}
			}
		}
	}
}

/**
 * Logs the current user out
 *
function login_logout(){
	global $ssc_user;
	_login_kill_session();
	session_regenerate_id(true);
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
	$user->displayname = t('Guest');
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
	if ($result = $ssc_database->query("SELECT s.data, s.uid id, u.useragent, u.username, u.fullname, u.displayname, u.gid, u.email FROM #__session s LEFT JOIN #__user u ON s.uid = u.id WHERE s.id = '%s' LIMIT 1", $id)){
		// Invalid session id
		if (!($ssc_user = $ssc_database->fetch_object($result))){
			$ssc_user = _login_anonymous();
			return '';
		}
	
		$data = $ssc_user->data;
		unset($ssc_user->data);
		// Check if logged in user
		if (!$ssc_user->id){
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
		$ret = $ssc_database->query("REPLACE INTO #__session (id, data, uid) VALUES ('%s', '%s', %d)", $id, $data, $ssc_user->id);
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