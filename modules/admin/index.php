<?php 
/**
 * Administration page.  Checks for authorisational login, etc
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $sscConfig_webPath, $database;

$sscConfig_adminImages = $sscConfig_webPath . '/themes/admin';
$sscConfig_adminURI = $sscConfig_webPath . '/' . $_GET['q'];

if(strrpos($sscConfig_adminURI,'/') == (strlen($sscConfig_adminURI)-1)){
	$sscConfig_adminURI = substr($sscConfig_adminURI,0,strlen($sscConfig_adminURI)-1);
}

$tmp = explode('/',$_GET['q']);
//set up the expected parameters as before
if(isset($tmp[1])){
	$_GET['sub'] = $tmp[1];
	$num = count($tmp);
	for($i = 2; $i < $num;){
		if(isset($tmp[$i+1])){
			//this plus next set... match them up
			$_GET[$tmp[$i]] = $tmp[$i+1];
		}
		$i+= 2;
	}
}

/**
 * Maximum upload size
 */
define('MAX_UPSIZE', ini_get('upload_max_filesize'));

// see how many hack attempts we've had
// store result for later use
$database->setQuery(sprintf("SELECT id, ip, username, #__log.time FROM #__log WHERE #__log.time > '%s' AND success < '1' AND ip = '%s' LIMIT 3",date("Y-m-d H:i:s",time()-86400),$database->escapeString($_SERVER['REMOTE_ADDR'])));
$attack_result = $database->query();

if($database->getNumberRows() >= 3){
	$database->setQuery(sprintf("SELECT id FROM #__log WHERE #__log.time > '%s' AND success = '-1' AND ip = '%s' LIMIT 1",date("Y-m-d H:i:s",time()-86400),$database->escapeString($_SERVER['REMOTE_ADDR'])));
	$database->query();
	
	// three attempts and no email yet?!
	if($database->getNumberRows() < 1){
		//email our friendly admin
		$database->setQuery("SELECT email,fullname FROM users WHERE group_id = 1 AND email != ''");
		$database->query();
		//generate admin "To:" list
		$to = '';
		while($data = $database->getAssoc()){
			$to.=$data['email'].',';
		}
		$to = substr($to,0,strlen($str)-1);
		
		//list attempts
		$attempts = '';
		$attempts_txt = '';
		while($data = $database->getAssoc($attack_result)){
			$attempts_txt.='-"'.$data['username'].'" @ '.$data['time']."\r\n";
			$attempts.='<li>"'.$data['username'].'" @ '.$data['time'].'</li>';
			$id = $data['id'];
			$ip = $data['ip'];
		}
		//no longer need to keep
		$database->freeResult($attack_result);
		
		$database->setQuery("UPDATE log SET success ='-1' WHERE id = '$id' LIMIT 1");
		$database->query() or die(mysql_query());

		//generate email
		$random_hash = md5(date('r', time())); 
		$headers = "From: $sscConfig_mailFrom\r\n";//@".$_SERVER['SERVER_NAME']."\r\n";//Reply-To: noreturn@example.com\r\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\""; 
		$body = <<< EMAIL
--PHP-alt-$random_hash
Content-Type: text/plain; charset="iso-8859-1";Content-Transfer-Encoding: 7bit\r\n

Dear Admins\r\n\r\n
DO NOT respond to this email.  This is an automated message to inform you that a computer with IP address $ip has attempted multiple unsuccessful logins during the past 24 hours.\r\n\r\n
Attempted usernames were:\r\n
-$attempts_txt

--PHP-alt-$random_hash
Content-Type: text/html; charset="iso-8859-1";Content-Transfer-Encoding: 7bit\r\n

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><body>Dear Admins<br><br>DO NOT respond to this email. This is an automated message to inform you that a computer with IP address $ip (details <a href="http://wq.apnic.net/apnic-bin/whois.pl">here</a> or <a href="http://ws.arin.net/cgi-bin/whois.pl">here</a>) has attempted multiple unsuccessful logins during the past 24 hours.<br><br>Attempted usernames were:<br><ul><li>$attempts</li></ul></body></html>

--PHP-alt-$random_hash--
			
EMAIL;
		//ensure formatted nicely
		$body = wordwrap($body,70);
		mail($to,"LBYC Suspect Attempted Logins",$body,$headers);
	}//end if no email sent

	//suggest to user to bugger off
	echo error('This IP address has had too many unsuccessful login attempts in the last 24 hours.  Access will be restored within the next 24 hours. Admin\'s have been alerted<br /><br />If this has occurred by error, please contact one of the admin\'s');

}else{
	//hasn't hit limit yet
	$database->freeResult($attack_result);
	
	/*
	$len = strlen($_SERVER['QUERY_STRING']);
	if(substr($_SERVER['QUERY_STRING'],$len-1,1)=="/"){
		$_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'],0,$len-1);
	}*/
	
	//check for logout
	if(isset($_GET['sub']) && $_GET['sub'] == 'logout'){
		include_once($sscConfig_absPath . '/modules/admin/logout.php');
	} 
	
	//login successful - include control panel
	if (isset($_SESSION['LoginSuccess'])){
		 include_once($sscConfig_absPath . '/modules/admin/admingo.php'); 
	}else{
		//show login form
		
		//assume not allowed
		$auth = false;
		
		//form submitted?
		if(isset($_POST['submit'])){
			$loginUsr = $database->escapeString($_POST['usr']);
			$loginPwd = $database->escapeString($_POST['pwd']);
	
			$database->setQuery(sprintf("SELECT fullname, username, password, group_id, last_access, id FROM #__users WHERE username = '%s' LIMIT 1", $loginUsr));
			$database->query();
			
			$data = $database->getAssoc();
		
			if($database->encodeString($loginUsr.$loginPwd) == $data['password']){
				//passwords match
				$auth = true;
				$_SESSION['UserFullName'] = $data['fullname'];
				$_SESSION['UserGroup'] = $data['group_id'];
				$_SESSION['UserName'] = $data['username'];
				$_SESSION['LoginSuccess'] = true;
			}else{
				//bad combo
				echo error('Username or password incorrect');
			}
			//log to DB
			$database->setQuery("INSERT INTO #__log (ip, success, username) VALUES ('" . $database->escapeString($_SERVER['REMOTE_ADDR']) . "', '" . $auth . "', '" . $loginUsr . "')");
			$database->query();
		}
		
		//display result to user...
		//...either welcome screen
		if($auth === true){
			echo '<div class="panel"><div class="center"><br /><h1>Welcome ', $_SESSION['UserFullName'], '</h1><br /><br />Your account was last accessed on ', date('l, jS F Y \a\\t g:i:sa', strtotime($data['last_access'])-1800), ' CST<br /><br />';
			
			//set new login success...
			$database->setQuery('UPDATE #__users SET last_access = \'' . date('Y-m-d H:i:s') . '\' WHERE id='. $data['id']);
			$database->query() or die(error('UNEXPECTED ERROR: SQL Error'));
			
			echo '<a href="', $sscConfig_webPath, '/admin">Continue</a> to the main admin page';
			//did we have a pre-intended path?
			if(isset($_GET['sub']))
			{
				echo ' or to your <a href="',$_SERVER['REQUEST_URI'],'">intended location</a>';
			}
			
			echo '<br /><br /></div></div>';
		}else{
			//... or bad/nonexistant username/password
			echo '<br /><div class="login"><div class="login-text"><img src="',$sscConfig_adminImages,'/admin.png" alt="Admin Image" /><br /><br />Login to gain access to the administration controls<br />';
			echo '</div><div class="login-form"><h1>Admin Login</h1><form action="', $sscConfig_adminURI, '" method="post"><fieldset><div><label for="usr">Username: </label><input size="30" type="text" id="usr" name="usr" alt="Login Username" /></div><br /><div><label for="pwd">Password: </label><input id="pwd" type="password" size="30" name="pwd" alt="Password" /></div><br /><div class="btn"><input type="submit" name="submit" value="Login" /></div></fieldset></form></div><br /></div>';
		} 
	}
}?>