<?php
/**
 * MySQL database object
 * Contains all the functions for mySQL to interact with the database
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * @subpackage database
 * @package SSC
 */
class sscDatabase{
	
	/** @var string Internal variable to hold the current SQL query */
	var $sql = '';
	
	/** @var resource Internal variable to hold the database connection */
	var $conn = null;
	
	/** @var resource Internal variable to hold the result of most recent SQL query */
	var $result = null;
	
	/** @var int Internal variable to hold previous sql error number */
	var $errorNum = 0;
	
	/** @var string Internal variable to hold previous sql error message */
	var $errorMsg = '';
	
	/** @var string Internal variable to hold the table prefix */
	var $prefix = '';
	
	/** @var int Internal variable to hold number of queries executed */
	var $queries = 0;
	
	/**
	 * Database object constructor - initiate contact to server
	 * @param string $host Database server to connect to
	 * @param string $user Database username
	 * @param string $pass Database password
	 * @param string $db Database name
	 * @param string $prefix Table prefixes in database
	 */
	function sscDatabase($host, $user, $pass, $db, $prefix){
		global $sscConfig_absPath;
		if(!function_exists('mysql_connect')){
			include ($sscConfig_absPath . '/conf/offline.php');
			exit();
		}
		
		$this->conn = mysql_connect($host, $user, $pass);
		
		if(!$this->conn){
			include ($sscConfig_absPath . '/conf/offline.php');
			exit();
		}
		
		if($db == '' || !mysql_select_db($db)){
			include ($sscConfig_absPath . '/conf/offline.php');
			exit();
		}
	
		$this->prefix = $prefix;
		
		/*  UTF support?  */
		$verParts = explode( '.', $this->getVersion() );
		if ($verParts[0] == 5 || ($verParts[0] == 4 && $verParts[1] == 1 && (int)$verParts[2] >= 2)) {
			mysql_query( "SET NAMES 'utf8'", $this->conn );
			$this->queries++;
		}
	
	}
	
	/**
	 * Escapes $str to prevent sql injection
	 * @param string $str String to escape
	 * @return string Escaped string to ensure safe sql queries
	 */
	 
	function escapeString($str){
		return mysql_real_escape_string($str);
	}
	
	/**
	 * Encodes $str for storing sensitive data in a table
	 * @param string $str String to encode
	 * @return string Encoded string
	 */
	function encodeString($str){
		return md5($str);
	}
	
	/**
	 * Set and prepare the query ready for execution.  Query must be pre-escaped
	 * @param string $sql The query to execute
	 * @param string $prefix
	 */
	function setQuery($sql, $prefix = '#__'){
		global $sscConfig_sqlDBPrefix;
		$this->sql = str_replace($prefix, $sscConfig_sqlDBPrefix, $sql);
	}
	
	/**
	 * Return the query for debugging purposes
	 * @return string <pre> formatted sql query
	 */
	function getQuery(){
		return '<pre>'.htmlspecialchars($this->sql).'</pre>';
	}
	
	/**
	 * Executes the pre-stored SQL query
	 * @see setSQL()
	 * @return mixed Result of mysql_query()
	 */
	function query(){
		$this->result = mysql_query($this->sql,$this->conn);
		$this->queries++;
		
		if(!$this->result){
			$this->errorNum = mysql_errno($this->conn);
			$this->errorMsg = mysql_error($this->conn);
		}
		
		return $this->result;
	}
	
	/**
	 * Grab an associative array from the database
	 * @param result Optional parameter to specify result to use
	 * @return mixed Associative array from most recent query run
	 * @see query()
	 */
	 
	function getAssoc($result = null){
		return mysql_fetch_assoc($result ? $result : $this->result);
	}
	
	/**
	 * Number of rows the most recent run query returned
	 * @return int Number of returned rows.  -1 if error
	 */
	 
	function getNumberRows(){
		if(!$this->result){return -1;}
		return mysql_num_rows($this->result);
	}
	
	/**
	 * Number of rows the most recent run query affected
	 * @return int Number of affected rows
	 */
	 
	function getAffectedRows(){
		return mysql_affected_rows($this->result);
	}
	
	/**
	 * Return the last error message
	 * @return string Last occurred error message
	 */
	 
	function getErrorMessage(){
		return $this->errorMsg;
	}
	
	/**
	 * Return the last error number
	 * @return int Last occurred error number
	 */
	 
	function getErrorNumber(){
		return $this->errorNum;
	}
	
	/**
	 * Clear the last return error
	 */
	 
	function clearError(){
		$this->errorNum = 0;
		$this->errorMsg = '';
	}
	/**
	 * Return server version
	 * @return string Version string
	 */
	
	function getVersion(){
	mysql_get_server_info($this->conn);
	}
	
	/**
	 * Cleans up.  Called when finished page generation and can free resources if left unfreed
	 */
	function cleanUp(){
		@mysql_free_result($this->result);
		@mysql_close($this->conn);
	}
	
	/**
	 * Frees specified result
	 * @param mixed Optional parameter to specify result.  Default to this->result
	 */
	
	function freeResult($result = null){
		mysql_free_result($result ? $result : $this->result);
	}
	
	/**
	 * Retrieves the ID of the previously inserted row
	 * @return int ID of last insert
	 */
	function getLastInsertID(){
		return mysql_insert_id();
	}


}

?>