<?php
/**
 * Akismet comms module
 *
 * Object to communicate with the Akismet anti-spam service.  Based upon the WordPress plugin.
 * 
 * @author Scott Thomas
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * 
 */

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/** @subpackage sscAkismet
 *  @package SSC
 */
class sscAkismet{
	
	/** @var array Internal array for holding comment details */
	var $comment;
	
	/** @var string Internal variable containing akismet host */
	var $akismetHost = 'rest.akismet.com';
	
	/** @var string Internal variable containing akismet host */
	var $akismetAPIHost;
	
	/** @var string Blog URL */
	var $blog;
	
	/** @var string WordPress API key */
	var $apiKey;
	
	var $userAgent;
	/**
	 * Constructor
	 * @param string URL to the blog being posted to
	 * @param string WordPress API key
	 * @return boolean Success of initialisation
	 */
	function sscAkismet($blog, $api = ''){
		global $sscConfig_version, $sscConfig_app;
	
		if($api == '')
			return false;
			
		$this->apiKey = $api;
		$this->blog = urlencode($blog);
		$this->userAgent = "$sscConfig_app/$sscConfig_version | SSC Akismet Plugin/0.3a";
			
		define("AKISMET_VERIFY",'/1.1/verify-key');
		define("AKISMET_CHECK",'/1.1/comment-check');
		define("AKISMET_SEND_SPAM",'/1.1/submit-spam');
		define("AKISMET_SEND_HAM",'/1.1/submit-ham');
		
		if(!$this->verifyKey())
			return false;
			
		$this->akismetAPIHost = $api.'.'.$this->akismetHost;
		return true;
	}
	
	function verifyKey(){
		$ret = $this->http_post("key=$this->apiKey&blog=$this->blog", $this->akismetHost, AKISMET_VERIFY);

		if($ret[1] == 'valid')
			return true;
		else
			return false;
	}
	
	function http_post($request, $host, $path){
		$http_request  = "POST $path HTTP/1.0\r\n" .
						 "Host: $host\r\n" . 
						 "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n" .
						 "Content-Length: " . strlen($request) . 
						 "\r\nUser-Agent: $this->userAgent\r\n\r\n$request";
						 
		$ret = '';
		if(($socket = fsockopen($host, 80)) !== false){
			fwrite($socket, $http_request);
			while(!feof($socket))
				$ret .= fgets($socket,1160); //aparently 1 tcp packet long
			
			fclose($socket);
			
			$ret = explode("\r\n\r\n", $ret, 2);
		}
		
		return $ret;
	}
	
	function setContent($comment, $type){
		$this->comment['comment_content'] = $comment;
		$this->comment['comment_type'] = $type;
	}
	
	function setAuthor($name, $email, $site){
		$this->comment['comment_author'] = $name;
		$this->comment['comment_author_email'] = $email;
		$this->comment['comment_author_url'] = $site;
	}
	
	function setRemote($ip, $agent){
		$this->comment['user_ip'] =  preg_replace( '/[^0-9., ]/', '', $ip );
		$this->comment['user_agent'] = $agent;
	}
	
	function setBlog($perma){
		$this->comment['permalink'] = $perma;
	}
	
	function isSpam(){
		$query = "blog=$this->blog";
	
		foreach($this->comment as $key=>$value)
			$query.='&'.$key.'='.stripslashes($value);
			
		$ret = $this->http_post($query,$this->akismetAPIHost,AKISMET_CHECK);

		if($ret[1] == 'true')
			return true;
		else
			return false;
	}
}

?>