<?php
/**
 * Database engine using the mysqli interface.
 * @package SSC
 * @subpackage Core
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Interface for database objects 
 */
abstract class sscAbstractDatabase{
	/**
	 *	Create the database object
	 */
	abstract public function __construct();
	
	/**
	 * Clean up
	 */
	abstract public function __destruct();
	
	/**
	 * Create a table in the database
	 * @param $structure
	 * 		Array structure containing specified table layout
	 */
	abstract public function create_table($structure)
	
	/**
	 * Replace appropriate table prefixes based on environment settings
	 * @param $table
	 * 		Unprefixed table containing 
	private function _set_table_prefix
	
	/**
	 * Sets the current database object query
	 * @param $sql 
	 * 		SQL query to exectute
	 * @param ...
	 * 		Arguments to be passed to the query for escaping  
	 */
	abstract public function set_query($sql);
	
	/**
	 * Execute the stored query
	 */
	abstract public function query();
	
}