<?php
/**
 * sscEdit - A simple text editor
 * Placeholder object for implementing a text editor.  Can be expanded/replaced in future with a full blown editor as needed.
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * @subpackage TextEdit
 * @package SSC
 */
class sscEdit{
	
	/**
	 * Primary constructor
	 */
	function sscEdit(){
	
	}

	/**
	 * Code to drop the editor into webpage
	 * @param string Name/ID for the editor to take
	 * @param string Optional text for existing content (not preformatted)
	 */
	static function placeEditor($name, $str='', $col=35, $row=20){
		echo '<textarea cols="',$col,'" rows="',$row,'" name="',$name,'" id="',$name,'" >',$str,'</textarea>';
	}

	/**
	 * Outputs the format help for ssc code (aka wiki code)
	 * @param int Level to create headings at (eg, <h3></h3>)
	 *
	 */
	static function placeHelp($h = 3){
		echo '<p>To create content, simply type into the text box. Extra formatting options are available for use however.</p>';
		echo "<h$h>Section Headings</h$h><p>Headings can be created by surrounding a heading text with a number of equals signs.  There are 3 heading levels.";
		echo " Primary headings are created by ==Heading==.  Secondary and tertiary headings use 3 and 4 equals signs respectively.</p>";
		echo "<h$h>Monospace Blocks</h$h><p>These are blocks using a monospaced font.  By having a space at the beginning of each line, one of these blocks is created</p>";
		echo "<h$h>Lists</h$h><p>Bulleted lists can also be created by starting a new line with the asterisk.  Nested lists can be created by using one or more asterisks in a row</p>";
		echo "<h$h>URL / Links</h$h><p>Links are created by using using [[url]] tags.  The tag has one needed parameter and one optional in the format [[url|http://my.url/page|optional text]].  The first parameter specifies the page to link to while the second specifies what the link should say</p>";
		echo "<h$h>Images</h$h><p>Images can be inserted using the [[img]] tag.  This tag has one required parameter and 3 optional parameters in the format [[img|/picture/name.jpg|alternate text|position|title]].  The first specifies the link relative to the SSC installation of the image including the extension and is CASE SENSITIVE. Only on-site images are supported at this time.</p><p>The second parameter specifies the alternative text if the picture is not displayed.  Text used here should only be used if it makes sense when shown/read in order of the page (eg, 'some text [[img|/pic.png|here]]' rather than 'some text [[img|/pic.png|yellow word]]').</p><p>The third parameter indicates the position of the image and has 4 options.</p><ul><li>'left' - image will jump to the left of the page</li><li>'right' - image will jump to the right of page</li><li>'cleft' - image will line up with left of page and will show below other images on the left</li><li>'cright' - similar except on right of page</li></ul><p>Be careful with 'left' and 'right' as they may not display as expected on smaller screens</p><p>The fourth parameter allows an optional title to be set for the image</p>";
	}
	
	/**
	 * Parses the text from the "edited" text ready for display
	 * @param string Content to parse
	 * @return string Parsed text
	 */
	static function parseToHTML($text){
		global $sscConfig_webPath;
		$text=htmlspecialchars($text);
			
		//replace headings.  Allow H2 - H4
		for($i = 4; $i > 1; $i--){
			$h = str_repeat('=',$i);
			$text = preg_replace( "/{$h}(.+){$h}/", "\n<h$i>\\1</h$i>\n", $text);
		}
		
		//go [] searching
		$offset = 0;
		while($offset = strpos($text,"[",$offset)){
			$newOffset = strpos($text,"]",$offset) + 2;
			$sub = substr($text,$offset, $newOffset - $offset);
			$tmp = explode("|",$sub);
			//decide what to do
			switch(strtolower($tmp[0])){
				case "[[url":
					if(isset($tmp[1])){
						
						if(isset($tmp[2])){
						    $sub = "<a href=\"$tmp[1]\">".substr($tmp[2],0,-2).'</a>';
						}else{ $tmp[1] = substr($tmp[1],0,-2); 
							$sub = "<a href=\"$tmp[1]\">".$tmp[1].'</a>';
						}
					}
					break;
				case "[[img":
					//only allow onsite for the time being?
					if(isset($tmp[1])){
						//first should ALWAYS be the link
						echo '<img src="', $sscConfig_webPath, $tmp[1],'"';
						if(isset($tmp[2])){
							//2nd is the alt text
							echo " alt=\"",$tmp[2],"\"";
							if(isset($tmp[3])){
								//3rd is (clear) floatability
								switch(strtolower($tmp[3])){
									case "left":
										echo " class=\"float\"";
										break;
									case "right":
										echo " class=\"right\"";
										break;
									case "cleft":
										echo " class=\"cfloat\"";
										break;
									case "cright":
										echo " class=\"cright\"";
										break;
								}
								
								if(isset($tmp[4])){
									//4th is title
									echo " title=\"",$tmp[4],"\"";
								}
							}
						}
						echo ' />';
					}
					//$sub = " !!do image parsing!! ";
					break;
			}
			$text = substr_replace($text,$sub,$offset,$newOffset-$offset);
			$offset = $newOffset;
		}
		
		// parse paras and lists
		
		//should be handled in cleanInput
		if(strpos($text,"\n")<0){
			$text = str_replace("\r","\n",$text);
		}else{
			$text = str_replace("\r","",$text);
		}
		
		$tmp = explode("\n",$text);
		$output = '';
		$inpara = false;$blanks=0;
		for($i = 0; $i < count($tmp);$i++){
			//echo substr($tmp[$i], 0, 1),'-',$tmp[$i],'<br />';
			switch(substr($tmp[$i], 0, 1)){
				case ' ':
					if($inpara){$output .= '</p>';$inpara=false;}
					$output .= '<pre>';
					while(isset($tmp[$i]) && substr($tmp[$i], 0, 1) == ' '){
						$output .= $tmp[$i]."\n";$i++;
					}
					$output .= '</pre>';
					$i--;
					$blanks=0;
					break;
					
				case '*': 
					if($inpara){$output .= '</p>';$inpara=false;}
					$output .= sscEdit::doList(0,$tmp, $i);
					$blanks=0;
					break;
				case "":
					if($inpara){
							$output.='</p><p>';
					}else{
							$inpara=true;$output.='<p>';
					}
					while(isset($tmp[$i]) && strlen($tmp[$i])==0){
						$i++;
					}$i--;
					break;
				default:
					//heading.  only auto-genned tag not alowed inside P
					if(strstr($tmp[$i],'<h')){
						if($inpara){
							$inpara=false;$output .='</p>';
						}
						$output.=$tmp[$i];
					}else{
					 if(!$inpara){$output .='<p>' . $tmp[$i];$inpara=true;}else{$output .= $tmp[$i].' ';}
				    } $blanks=0;
					break;
			}
		}
		if($inpara){$output.='</p>';}
		
		$output = str_replace("<p></p>","",$output);
		
		return $output;
	}
	
	static function doList($level, &$lines, &$i){
		$output = '<ul>';
		while(isset($lines[$i]) && substr($lines[$i], $level, 1) == '*'){
			while(isset($lines[$i]) && substr($lines[$i], $level+1, 1) == '*'){
				$output .= '<li class="sub-li">'.sscEdit::doList($level+1, $lines, $i).'</li>';
			}
			if(substr($lines[$i], $level, 1) == '*'){
			$output.='<li>'.substr($lines[$i],$level+1).'</li>';
			$i++;
			}
		}
		$output .= '</ul>';
		return $output;
		
	}

	
	/**
	 * Cleans up the supplied 'SSC' code to be slightly nicerly formatted (for the parser)
	 * @param string SSC Code
	 * @return string Cleaned version
	 */
	/*function cleanInput($input){
		//initially, just tidy up any excessive enter key hitting (unless inside a <pre> section)
		if(strpos($input,"\n")<0){
			$input = str_replace("\r","\n",$input);
		}else{
			$input = str_replace("\r","",$input);
		}
		$input = explode("\n",$input);
		$return = '';
		$len = count($input);
		$prevChar = '';
		echo $len, '<br />';
		for ($i = 0; $i < $len; $i++){
			//now to loop...
			if($input[$i] == ''){
				//blank line
			}
			$return .= $input[$i];
		}
		
		return $return;
	}*/
}

?>