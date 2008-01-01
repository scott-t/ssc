<?php
/**
 * Database engine using the mysqli interface.
 * @package SSC
 * @subpackage Database
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

$SSC_SETTINGS['database']['engineclass'] = 'sscDatabaseMySQLi';

/**
 * MySQLI based database object
 * @package SSC
 * @subpackage Database
 */
class sscDatabaseMySQLi extends sscAbstractDatabase{

	/**
	 * @var mixed Contains the mysqli object
	 */
	private $link;
	
	/**
	 * @var string Storage for current query 
	 */
	private $query;
	
	/**
	 * @var mixed Contains the result from a query 
	 */
	private $result;
	
	/**
	 * @var int Flag indicating magic quote status 
	 */
	private $quote_status;
	
	/**
	 * Create the database connection
	 * @see ssciDatabase::__construct
	 */
	function __construct(){
		global $SSC_SETTINGS;
		
		// Check if mysqli is available
		if (!function_exists('mysqli_connect')){
			core_die(array(
						'title' => 'Installation Error',
						'body'  => 'The MySQLI interface for PHP is not available'
					));
			return;
		}
		
		// Perform connection
		$this->link = new mysqli($SSC_SETTINGS['database']['host'], $SSC_SETTINGS['database']['user'], $SSC_SETTINGS['database']['password'], $SSC_SETTINGS['database']['database'], $SSC_SETTINGS['database']['port']);
	}
	
	/**
	 * Clean up
	 * @see ssciDatabase::__destruct
	 */
	function __destruct(){
		$this->link->close();
	}
	
	/**
	 * @see ssciDatabase::query
	 */
	function query(){
		echo $this->query;
	}
	
	/**
	 * @see ssciDatabase::set_query
	 */
	function set_query($sql){
		$param = func_get_args();
		$param_count = count($param);
		for ($i = 1; $i < $param_count; $i++)
			$param[$i] = $this->escape_string($param[$i]);
		
		$sql = call_user_func_array('sprintf', $param);
		$this->query = $sql;
	}
	
	/**
	 * Escape the current string
	 * @param string $str String to be escaped
	 * @return string Escaped string ready for insertion into the database
	 */
	function escape_string($str){
		$this->link->real_escape_string($str);
		return str_replace(array('_', '%'), array('\\_', '\\%'),$str);
	}
}