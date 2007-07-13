<?php 
/**
 * config.core.php
 * Sets up the core SSC environment
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Include all the configuration variables
 */
require_once('config.vars.php');

/**
 * Database object include.
 */
 
require_once($sscConfig_absPath . '/includes/database.mysql.php');

/*
 * Create the database object
 */
$database = new sscDatabase($sscConfig_sqlHost, $sscConfig_sqlUser, $sscConfig_sqlPass, $sscConfig_sqlDB, $sscConfig_sqlDBPrefix );

/**
 * Do environment setup.  Sets headers and loads included page
 */
function core_start(){
	global $sscConfig_webFolder, $sscConfig_absPath, $database;
	/*
	 * Start session
	 */
	ini_set('session.cookie_path', $sscConfig_webFolder);
	session_start();
	
	/*
	 * Ensure error reporting off
	 */
	//if($_SERVER['SERVER_NAME'] == "scott-t"){
		error_reporting(E_ALL | E_STRICT);
		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', true);
	/*}else{
		error_reporting(0);
		ini_set('error_reporting', 0);
		ini_set('display_errors', false);
	}*/
	
	/*
	 * Magic quotes off.  DB will handle escaping
	 */
	ini_set('magic_quotes_gpc','Off');
	
	header("Content-Type:text/html");
	/*
	 * Ensure selected page exists, else 404
	 */
	 if(!isset($_GET['q'])){
		//if not set, we go home
		header("HTTP/1.0 404 Not Found");
		$_GET['cont'] = 'Home';
		$_GET['file'] = 'home';
	}else{
		//06-Jul-07
		//rewrite this to grab full uri from table
		//eg. try /my/module/mod-specific/params
		// 1st... match /my/module/mod-specific/params
		// 2nd... try /my/module/mod-specific
		// 3rd... try /my/module 
		// etc
		
		// on hit, load required module (via mod_id) and pass it nav_id as the argument.  it can work out the rest
		$tmp = $_GET['q'];
		
		//first we'll just check if its admin...
		if(strpos($tmp,'admin') === 0){
			$_GET['cont'] = 'Administration';
			$_GET['file'] = 'admin';
		
		}else{
			
			while(1){		//infinte loop?  can't believe i'm doing this...
				$database->setQuery("SELECT #__navigation.id, #__navigation.name, filename FROM #__navigation, #__modules WHERE module_id = #__modules.id AND uri = '/" . $database->escapeString($tmp) . "' LIMIT 1");
				if($database->query()){
					if($data = $database->getAssoc()){
						//got a result
						//assume to be right since it matched the uri
						$_GET['cont'] = $data['name'];
						$_GET['file'] = $data['filename'];
						$_GET['pid']  = $data['id'];
						break;
					}else{
						//no matches.  drop back to parent
						if(strpos($tmp, '/') !== false){
							//can drop
							$tmp = substr($tmp,0,strrpos($tmp, '/'));
						}else{
							//no parent to drop back to
							//assume 404 from an sql problem
							header("HTTP/1.0 404 Not Found");
							$_GET['cont'] = 404;
							$_GET['file'] = 'error';
							break;	//exit loop
						}
					}
				}	
				else{
					//assume 404 from an sql problem
					header("HTTP/1.0 404 Not Found");
					$_GET['cont'] = 404;
					$_GET['file'] = 'error';
					break;	//exit loop
				}
			}
		}
	}
}
/*
 *  Main SSC content fillers
 */

/**
 * Places body of document when called
 */
function sscPlaceContent(){
	global $sscConfig_absPath;
	require_once($sscConfig_absPath . '/modules/' . $_GET['file'] . '/index.php'); //'/themes/'. $sscConfig_theme . '/index.php');
}


/**
 * Generate the navigation bar when called. 
 * @param bool $image If true, sets $sscConfig_themeWeb/nav.png as image inside <a> tag
 * @see $sccConfig_themeWeb
 */
function sscPlaceNavigation($image = false){
	global $database, $sscConfig_themeWeb, $sscConfig_webPath;
	
	echo '<ul id="bar">';
	
	$database->setQuery('SELECT name, uri FROM #__navigation WHERE hidden = 0 ORDER BY position ASC, name ASC');
	if(!$database->query()){
	echo $database->getErrorNumber(), " - ", $database->getErrorMessage();
	}
	
	while ($dat = $database->getAssoc()){
		echo '<li><a href="',$sscConfig_webPath, $dat['uri'], '" >';
		if($image){ echo '<img src="', $sscConfig_themeWeb, '/nav.png" alt="" />';
		}
		echo '<span>', $dat['name'], '</span></a></li>';
	}
	
	echo '</ul>';
}


/**
 * Generate footer and does general cleanup, free variables, etc
 * @see sccDatabase::cleanUp()
 */
function sscPlaceFooter(){
	global $database, $mytimerstart;
	
	 $m_time = explode(" ",microtime()); 
 $m_time = $m_time[0] + $m_time[1]; 
 $endtime = $m_time; 
 $totaltime = ($endtime - $mytimerstart); 
	
	printf("xhtml and css valid - %.4f seconds - %d queries",round($totaltime,4),$database->queries);
	$database->cleanUp();
	//@mysql_close($conn);
}


/**
 * Format an error message nicely
 * @param string Error to output
 * @return string Formatted error
 */
function error($str){
	global $sscConfig_webPath;
	return '<div class="error"><img src="'.$sscConfig_webPath.'/themes/admin/error.png" alt="Error!" /><span>'.$str.'</span><hr /></div>';
}

/**
 * Format a warning message nicely
 * @param string Warning to output
 * @return string Formatted warning
 */
function warn($str){
	global $sscConfig_webPath;
	return '<div class="warn"><img src="'.$sscConfig_webPath.'/themes/admin/warn.png" alt="Warning!" /><span>'.$str.'</span><hr /></div>';
}

/**
 * Format a message nicely
 * @param string Message to output
 * @return string Formatted message
 */
function message($str){
	global $sscConfig_webPath;
	return '<div class="message"><img src="'.$sscConfig_webPath.'/themes/admin/info.png" alt="Information:" /><span>'.$str.'</span><hr /></div>';
}

/**
 * Parse ambiguous dates for AU/UK (dd/mm/yy) as formatting rather than US (mm/dd/yy)
 * @param string Date to rewrite
 * @return string Rewritten date
 */
function parseDate($value)
{
    //see: http://www.php.net/manual/fi/function.strtotime.php#59748
    //switch day and month
    $reformatted = preg_replace("/^\s*([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]+([0-9]{1,4})/", "\\2/\\1/\\3", $value);
    return $reformatted;
}

?>
