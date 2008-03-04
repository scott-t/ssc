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
		$prev = 0;
		$offset = 0;
		$start = 0;
		while (($start = strpos($body, "[[")) !== false) {
			$stop = strpos($body, "]]", $start + 2);
			$next = strpos($body, "[[", $start + 2);
			
			if ($next !== false && $next < $stop){
				// Nexted tags - perhaps not the most efficient but most nestings will
				// be simple ones anyway so shouldn't make that much a difference
				while ($next !== false && $next < $stop){
					// Grab most inner one
					$prev = $next;
					$next = strpos($body, "[[", $next + 2);
				}
				$start = $prev;
			}
			
			$diff = $stop - $start;
			$tag = substr($body, $start + 2, $diff - 2);
			ssc_debug(array('title' => 'tags', 'body'=> 'Parsing ' . $tag));
			$tag = sscText::_convert_tag($tag);
			ssc_debug(array('title' => 'tags', 'body'=> 'returned ' . $tag));
			$body = substr_replace($body, $tag, $start, $diff + 2);							
		}
		return $body;
	}
	
	/**
	 * Implements the different tags
	 * @param string $tag Tag to interpret
	 * @return string XHTML version
	 */
	function _convert_tag($tag){
		global $ssc_site_url, $ssc_site_path;
		$param = explode("|", $tag);
		$tag = str_replace("|", "&#124;", $tag);
		switch ($param[0]){
		case "url":
			if (empty($param[1])){
				// Need url - blank tag
				$tag = "&#91;&#91;$tag&#93;&#93;";
				break;
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
			if (empty($param[1])){
				// Need img path - blank tag
				$tag = "&#91;&#91;$tag&#93;&#93;";
				break;
			}
			
			array_shift($param);
			$path = array_shift($param); 
			
			// Do some path mix/matching
			if (strpos($path, "://") === false){
				// Relative path
				if (file_exists($ssc_site_path . "/images/$path")){
					// Default to image directory base-dir
					$path = $ssc_site_url . "/images/$path";
				}
				elseif (file_exists($ssc_site_path . '/' . $path)){
					// Relative to site root instead
					$path = $ssc_site_url . '/' . $path;
				}
				else{
					// Bad local path - ignore tag
					$tag = "&#91;&#91;$tag&#93;&#93;";
					break;
				}
				echo "aba";
				
			}
			
			$tag = "<img src=\"$path\"";
			while ($op = array_shift($param)){
				$o = explode("=", $op);
				if (empty($o[1]))
					continue; 	// Bad argument
					
				// Parse possible arguments
				switch ($o[0]){
				case "float":
					$tag .= " class=\"$o[1]\"";
					break;
				case "title":
					$tag .= " title=\"$o[1]\"";
					break;
				case "alt":
					$tag .= " alt=\"$o[1]\"";
					break;
				}
			}

			$tag .= " />";
			break;

		default:
			// Ignore non-standard tags
			$tag = "&#91;&#91;$tag&#93;&#93;";
		}
		return $tag;
	}
	
}
?>