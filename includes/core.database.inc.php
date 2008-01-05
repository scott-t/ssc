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
	 * Generate a SELECT query
	 * @param array $structure Structure for the select
	 * @return string SQL query syntax that was executed
	 */
	abstract public function select_from($structure);
	
	/**
	 * Insert a row or rows into a table.
	 * 
	 * If array index [0] exists, then may assume multiple rows are to be inserted
	 * at the same time with the array elements containing arrays of field=>value pairs
	 * for each row.  If [0] does not exist, assume a single row where $values is a simple
	 * field=>value array.
	 *  
	 * @param string $table Table to insert into
	 * @param array $values Array of field=>value pairs to insert into the database
	 */
	abstract public function insert_row($table, $values);
	
	/**
	 * Delete a row from a table
	 * @param string $table Table name
	 * @param array $id Array of field=>value pairs which are used to help identify the
	 * 					row(s) to delete
	 * @return string SQL statement issued
	 */
	abstract public function delete_row($table, $id);
	
	/**
	 * Update a given row or rows
	 * 
	 * If array index [0] exists in $values, then may assume multiple rows are to be updated
	 * at the same time with the array elements containing arrays of field=>value pairs
	 * for each row.  If [0] does not exist, assume a single row where $values is a simple
	 * field=>value array.
	 * 
	 * The WHERE condition of the update is specified with a field key set to "where" which
	 * should contain an array of field=>value pairs for equivalence
	 * 
	 * @param string $table Table name to update
	 * @param array $values Array of field=>values containing what to update 
	 */
	abstract public function update_row($table, $values);
	
	/**
	 * Create a table in the database
	 * @param array $structure Structure containing specified table layout
	 * @return string SQL statement that was generated and executed
	 */
	abstract public function create_table($structure);
	
	/**
	 * Permanently remove a table
	 * @param string $table Table name representing the table to remove
	 */
	abstract public function delete_table($table);
	
	/**
	 * Permanently empty a table
	 * @param string $table Table name representing the table to empty
	 */
	abstract public function emtpy_table($table);
	
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