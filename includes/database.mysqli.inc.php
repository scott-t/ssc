<?php
/**
 * Database engine using the mysqli interface.
 * @package SSC
 * @subpackage MySQLi
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * MySQLI based database object
 * @package SSC
 * @subpackage MySQLi
 */

class sscDatabase extends sscAbstractDatabase{

	// Query related storage
	private $link;
	private $query;
	private $result;
	// Environment settings storage
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
	 */
	function escape_string($str){
		$this->link->real_escape_string($str);
		return str_replace(array('_', '%'), array('\\_', '\\%'),$str);
	}
}