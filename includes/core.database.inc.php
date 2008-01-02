<?php
/**
 * File containing the core database interface
 * @package SSC
 * @subpackage Core
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Interface for database objects 
 * @package SSC
 * @subpackage Core
 */
abstract class sscAbstractDatabase{
	/**
	 *	Connect to the database object using the $SSC_SETTINGS['database'] settings
	 */
	abstract public function __construct();
	
	/**
	 * Free up the database objects
	 */
	abstract public function __destruct();
	
	/**
	 * Create a table in the database
	 * @param array $structure Structure containing specified table layout
	 * @return string SQL statement that was generated and executed
	 */
	abstract public function create_table($structure);
	
	/**
	 * Replace appropriate table prefixes based on environment settings
	 * @param string $table Unprefixed table name
	 * @return string Prefixed table name
	 */ 
	protected function _set_table_prefix($table){
		global $SSC_SETTINGS;
		$table = '_' . $table;
		
		if (array_key_exists($table, $SSC_SETTINGS['database']['prefix'])){
			return $SSC_SETTINGS['database']['prefix'][$table] . $table;
		}
		else {
			return $SSC_SETTINGS['database']['prefix']['default'] . $table;
		}
	}
	
	/**
	 * Sets the current database object query
	 * @param string $sql SQL query to exectute
	 * @param mixed $...,... Arguments to be passed to the query for escaping  
	 */
	abstract public function set_query($sql);
	
	/**
	 * Execute the stored query
	 */
	abstract public function query();
	
}