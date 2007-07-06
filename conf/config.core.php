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
	 if(!isset($_GET['cont'])){
		//if not set, we 404
		header("HTTP/1.0 404 Not Found");
		$_GET['cont'] = 404;
		$_GET['file'] = 'error';
	}else{
		//does it currently exist?	
		if(!file_exists($sscConfig_absPath . '/modules/' . $_GET['cont'] . '/index.php')){
			//convert to a nice name
			$_GET['cont'] = ucwords(str_replace('-',' ',$_GET['cont']));
			//find if an associated module
			$database->setQuery(sprintf("SELECT filename FROM #__modules, #__navigation WHERE #__modules.id = #__navigation.module_id AND #__navigation.name LIKE '%s' LIMIT 1",$database->escapeString($_GET['cont'])));
			if($database->query() && $database->getNumberRows() > 0){
				$data = $database->getAssoc();
				$_GET['file'] = $data['filename'];
			}else{
				//no result? 404 it
				header("HTTP/1.0 404 Not Found");
				$_GET['cont'] = 404;
				$_GET['file'] = 'error';				
			}
			
	
		}else{
			$_GET['file'] = $_GET['cont'];
			$_GET['cont'] = ucwords(str_replace('-',' ',$_GET['cont']));
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
	
	$database->setQuery('SELECT name FROM #__navigation WHERE hidden = 0 ORDER BY position ASC, name ASC');
	if(!$database->query()){
	echo $database->getErrorNumber(), " - ", $database->getErrorMessage();
	}
	
	while ($dat = $database->getAssoc()){
		echo '<li><a href="',$sscConfig_webPath,'/', str_replace(' ','-',strtolower($dat['name'])), '" >';
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
	
	printf("xhtml and css valid - %.4f seconds - %d queries<br />Webspace provided by <a href=\"http://www.serversaustralia.com.au/\">Servers Australia</a>",round($totaltime,4),$database->queries);
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

?>
