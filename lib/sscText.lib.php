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

		// Headings
		for($i = 4; $i > 2; $i--){
			$h = str_repeat('=', $i-1);
			$body = preg_replace( "/{$h}(.+){$h}/", "\n<h$i>\\1</h$i>\n", $body);
		}

		// Prepare to parse paragraphs
		
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
		
		// Parsing of paragraphs (normal and specialised), lists, etc
		
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
		$tagstack = 0;
		$offset = 0;
		$tagstart = -2;
		while (($next = strpos($body, "[[", $tagstart+2)) !== false) {
			$tagstart = strpos($body, "[[");
			$tagstop = strpos($body, "]]");
			$next = strpos($body, "[[", $tagstart+2);
			if ($next === false || $next > $tagstop){
				// No next tag or more tags but not nested
				$diff = $tagstop - $tagstart;
				$tag = substr($body, $tagstart + 2, $diff - 2);
				$tag = sscText::_convert_tag($tag);
				$body = substr_replace($body, $tag, $tagstart, $diff + 2);				
			}
			else {
				// Nexted tags
				while ($next < $tagstop){
					$tagstack = $next;
					$next = strpos($body, "[[", $next + 1);
				}
				$tagstart = $tagstack;
				$diff = $tagstop - $tagstart;
				$tag = substr($body, $tagstart + 2, $diff - 2);
				$tag = sscText::_convert_tag($tag);
				$body = substr_replace($body, $tag, $tagstart, $diff + 2);	
			}
			
		} 
		
		return $body;
	}
	
	/**
	 * Implements the different tags
	 * @param string $tag Tag to interpret
	 * @return string XHTML version
	 */
	function _convert_tag($tag){
		global $ssc_site_url;
		$param = explode("|", $tag);
		switch ($param[0]){
		case "url":
			if (empty($param[1])){
				// Need url - blank tag
				return "";
			}
			
			if (empty($param[2])){
				// Empty text - use url
				$param[2] = $param[1];
			}
			
			// Condition parameter
			if (strpos($param[1], "://") === false){
				// Assume rel-path
				$param[1] = $ssc_site_url . $param[1];
			}
			
			$tag = '<a href="' . $param[1] . '">' . $param[2] . '</a>';
			break;
		
		case "img":
			$tag = "IMAGE PARSER";
			break;

		default:
			// Ignore non-standard tags
			$tag = "&#91;&#91;$tag&#93;&#93;";
		}
		return $tag;
	}
	
}
?>