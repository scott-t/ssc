<?php
/**
 * Akismet communication module
 *
 * Object to communicate with the Akismet anti-spam service.  Based upon the WordPress plugin.
 *
 * @package SSC
 * @subpackage Libraries
 */

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/** 
 * Akismet anti-spam service
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
	
	/** @var string Internal variable representing the user agent of the plugin */
	var $userAgent;
	
	/**
	 * Constructor
	 * @param string URL to the blog being posted to
	 * @param string WordPress API key
	 * @return boolean Success of initialisation
	 */
	function __construct($blog, $api = ''){
		global $sscConfig_version, $sscConfig_app;

		// Need an api key
		if($api == '')
			return false;
	
		$this->apiKey = $api;
		$this->blog = urlencode($blog);
		$this->userAgent = SSC_VER_UA . " | SSC Akismet Library/0.5a";
			
		define("AKISMET_VERIFY",'/1.1/verify-key');
		define("AKISMET_CHECK",'/1.1/comment-check');
		define("AKISMET_SEND_SPAM",'/1.1/submit-spam');
		define("AKISMET_SEND_HAM",'/1.1/submit-ham');
		
		if(!$this->verifyKey())
			return false;
			
		$this->akismetAPIHost = $api.'.'.$this->akismetHost;
		return true;
	}
	
	/**
	 * Decide whether the supplied API key is valid
	 * @return bool True if valid key, false if otherwise
	 */
	function verifyKey(){
		$ret = $this->http_post("key=$this->apiKey&blog=$this->blog", $this->akismetHost, AKISMET_VERIFY);

		if($ret[1] == 'valid')
			return true;
		else
			return false;
	}
	
	/**
	 * Make a HTTP POST request
	 * @param string $request Request to send
	 * @param string $host Host to send request to
	 * @param string $path Path from host root directory for submission
	 * @return array Contents of received details from post
	 */
	function http_post($request, $host, $path){
		$http_request  = "POST $path HTTP/1.0\r\n" .
						 "Host: $host\r\n" . 
						 "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n" .
						 "Content-Length: " . strlen($request) . 
						 "\r\nUser-Agent: $this->userAgent\r\n\r\n$request";
		
		$ret = '';
		if (($socket = fsockopen($host, 80)) !== false){
			fwrite($socket, $http_request);
			while (!feof($socket))
				$ret .= fgets($socket, 1160); // Apparently 1 TCP packet long
			
			fclose($socket);
			
			$ret = explode("\r\n\r\n", $ret, 2);
		}
		
		return $ret;
	}
	
	/**
	 * Sets the content of the comment to be moderated
	 * @param string $comment Content of the comment
	 * @param string $type Type of content
	 */
	function setContent($comment, $type){
		$this->comment['comment_content'] = $comment;
		$this->comment['comment_type'] = $type;
	}
	
	/**
	 * Sets the writers details for the comment
	 * @param string $name Name used as the comment 'author'
	 * @param string $email Email address for the author
	 * @param string $site Website the author comes from
	 */
	function setAuthor($name, $email, $site){
		$this->comment['comment_author'] = $name;
		$this->comment['comment_author_email'] = $email;
		$this->comment['comment_author_url'] = $site;
	}
	
	/**
	 * Sets the details relating to the poster
	 * @param string $ip IP address for the comment author
	 * @param string $agent User agent belonging to the authors browser
	 */
	function setRemote($ip, $agent){
		$this->comment['user_ip'] =  preg_replace( '/[^0-9., ]/', '', $ip );
		if ($agent)
			$this->comment['user_agent'] = $agent;
	}
	
	/**
	 * Sets the link to the destination of the post
	 * @param string $perma Permalink for which the comment is being posted on
	 */
	function setBlog($perma){
		$this->comment['permalink'] = $perma;
	}
	
	/**
	 * Decides whether the comment is spam or not
	 * @return bool True if the comment is spam, false if otherwise
	 */
	function isSpam(){
		$query = "blog=$this->blog";
	
		foreach ($this->comment as $key => $value)
			$query .= '&' . $key . '=' . stripslashes($value);
			
		$ret = $this->http_post($query, $this->akismetAPIHost, AKISMET_CHECK);

		if($ret[1] == 'true')
			return true;
		else
			return false;
	}
	
	/**
	 * Used to inform Akismet it has incorrectly marked a particular comment
	 * @param string $action String representing action to perform
	 * 			Takes values 'markSpam' or 'markHam'
	 */
	function markIncorrect($action){
		// Parse action
		switch ($action){
		case 'markSpam':
			$do = AKISMET_SEND_SPAM;
			break;
		case 'markHam':
			$do = AKISMET_SEND_HAM;
			break;
		default:
			return;
		}
		
		$query = "blog=$this->blog";
	
		foreach ($this->comment as $key => $value)
			$query .= '&' . $key . '=' . stripslashes($value);
			

		$ret = $this->http_post($query, $this->akismetAPIHost, $do);
		print_r( $ret);
	}
}

