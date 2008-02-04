<?php
/**
 * This file an email interface for developers
 * @package SSC
 * @subpackage Libraries
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Email wrapper class
 */
class sscMail {

	/**
 	 * @var string Target email address
 	 */
	var $to;

	/**
 	 * @var array Headers for the email address
 	 */
	var $header;
	
	/**
 	 * @var string Email subject
 	 */
	var $subject;
	
	/**
	 * Constructor
	 */
	function __construct($to, $subject, $from = null){
		$this->header = array('X-Mailer' => SSC_VAR_UA);

		// To field
		if (!empty($to)){
			$this->to = filter_var($to, FILTER_VALIDATE_EMAIL);
		}

		// From field
		if (!empty($from)){
			$this->set_header('From', $from);
		}
		
		$this->subject = $subject;
	}

	/**
  	 * Destructor
  	 */
	function __destruct(){
	
	}
	
	/**
 	 * Set an email header
 	 * @param string $header Header name
 	 * @param string $content Content for the header
 	 * @return boolean TRUE on valid header, FALSE otherwise
 	 */
	function set_header($header, $content){
		// Try and prevent header injection
		if (strpos($content, "\n") !== false || strpos($content, ":") !== false){
			return false;
		}
		
		$this->header[$header] = $content;
		return true;
	}
	
	/**
 	 * Perform mail operation
 	 * @return boolean TRUE on mail accepted for delivery, FALSE otherwise
 	 */
	function send($message){
		// Ensure required fields set
		if (empty($this->to) || empty($this->subject) || empty($this->message) || )
			return false;

		// Set default 'From' header
		if (empty($this->header['From']))
			$this->set_header('From', ssc_get_var("admin_email", 'noreply@' . $_SERVER['SERVER_NAME']));
			
		$headers = '';
		foreach ($this->header as $key => $value){
			$headers .= "$key: $value\n";
		}
		
		return mail($this->to, $this->subject, $message, $headers);
	}
	
}
?>