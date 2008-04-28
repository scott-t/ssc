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
		$inpara = false;
		$body = '';
		$count = count($bulk);
		// Loop through each line
		for ($i = 0; $i < $count; $i++){
			// Check first character
			if (empty($bulk[$i])){
				if ($inpara){
					$body .= '</p><p>';
				}
				else{
					$body .= '<p>';
					$inpara = true;
				}
				continue;
			}
				
			switch (substr($bulk[$i], 0, 1)){
			case ' ':	// Space - denotes pre-formatted stuff for code
				if ($inpara){
					$body .= '</p>';
					$inpara = false;
				}
				$body .= '<pre>';
				do {
					$body .= $bulk[$i] . '\n';
					$i++;
				} while(isset($bulk[$i]) && $bulk[$i][0] == ' ');
				$body .= '</pre>';
				$i--;
				break;
				
			case '*':	// Asterisk - denotes bulleted list
				if ($inpara){
					$body .= '</p>';
					$inpara = false;
				}
				$body .= sscText::_do_list($bulk, $i);
				break;
								
			default:
				if (strpos($bulk[$i], '<h') !== false){
					if ($inpara){
						$inpara = false;
						$body .= '</p>';
					}
				}
				else{
					if (!$inpara){
						$body .= '<p>';
						$inpara = true;
					}
				}
				$body .= $bulk[$i];
			}
		}
		if ($inpara)
			$body .= '</p>';

		while(strpos($body, '<p></p>') !== false){
			$body = str_replace('<p></p>', '', $body);
		}
			
		return $body;
	}
	
	/**
	 * Generate a list from sscText
	 * @param array $bulk Lines representing body content
	 * @param int $i Index in content array representing next line
	 * @param int $level Level of indentation 
	 * @return string XHTML markup equivalent
	 */
	static function _do_list(&$input, &$i, $level = 0){
		$out = '<ul>';
		while (isset($input[$i]) && substr($input[$i], $level, 1) == '*'){
			while (isset($input[$i]) && substr($input[$i], $level+1, 1) == '*'){
				$out .= '<li>' . sscText::_do_list($input, $i, $level+1) . '</li>';
			}
			if (substr($input[$i] ,$level, 1) == '*'){
				$out .= '<li>' . substr($input[$i], $level + 1) . '</li>';
				$i++;
			}
		}
	
		return $out . '</ul>';
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
			$tag = sscText::_convert_tag($tag);
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
				if (file_exists($ssc_site_path . "/images/$path.jpg") || file_exists($ssc_site_path . "/images/$path.png") || file_exists($ssc_site_path . "/images/$path")){
					// Default to image directory base-dir
					$path = $ssc_site_url . "/images/$path";
				}
				elseif (file_exists($ssc_site_path . "/$path") || file_exists($ssc_site_path . "/$path.jpg") || file_exists($ssc_site_path . "/$path.png")){
					// Relative to site root instead
					$path = $ssc_site_url . '/' . $path;
				}
				else{
					// Bad local path - ignore tag
					$tag = "&#91;&#91;$tag&#93;&#93;";
					break;
				}
				
			}
		
			$tag = "<img src=\"$path\"";
			$donealt = false;
			while (count($param)){
				$op = array_shift($param);
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
					$donealt = true;
					break;
				}
			}
			if (!$donealt){
				$tag .= ' alt=""';
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