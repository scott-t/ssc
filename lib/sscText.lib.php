<?php
/**
 * Interprets wiki-text markup
 * @package SSC
 * @subpackage Libraries
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Wiki text converter
 */
class sscText {

	/**
	 * Constructor
	 */
	function __construct($to, $subject, $from = null){
		
	}

	/**
  	 * Destructor
  	 */
	function __destruct(){
	
	}
	
	/**
	 * Convert a piece of text from "wiki" markup to XHTML markup for display
	 * @param string $body Text to convert
	 * @return string XHTML markup
	 */
	static function convert($body){
		// Ensure only plain text
		$body = check_plain($body);

		// Any parsing to do?  Quick check
		if (strpos($body, '[[') !== false)
			$body = sscText::_parse_tags($body);
		
		if (strpos($body, "\r") !== false){
			// Are \r's
			if (strpos($body, "\n") !== false){
				// \n's too
				// Assume windows \r\n format
				$body = str_replace("\r", '', $body);
			}
			else{
				// No \n's
				$body = str_replace("\r", "\n", $body);
			}
		}
		
		$bulk = explode("\n", $body);
		$n = count($bulk);
			
		return $body;
	}
	
	/**
 	 * Parse wiki tags
 	 * @param string $body Markup to de-tag
 	 * @return string XHTML markup equivalent
 	 * @private
 	 */
	static function _parse_tags($body){
		// Possible tags here.  Lets get cracking
		$tagstack = array();
		$offset = 0;
		do{
			$tagstart = strpos($body, "[[");
			$tagstop = strpos($body, "]]");
			$next = strpos($body, "[[", $tagstart);
			if ($next === false){
				// No next tag.  
				$sub = explode("|", substr($body, $tagstart, $tagstart - $tagstop));
				print_r($sub);
			}
			
			//$next < $tagstop)
		} while (0);
		
		return $body;
	}
	
	
}
?>