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
	 * If array the first element's value is an array, then it is assumed that multiple
	 * rows are to be inserted at the same time.  The key will then hold the field name
	 * and the value will hold an array of successive values representing to each row.
	 * Each field should be an array with equivalent length. 
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
	abstract public function empty_table($table);
	
	/**
	 * Replace appropriate table prefixes based on environment settings
	 * @param string $table Unprefixed table name
	 * @return string Prefixed table name
	 */ 
	protected function _set_table_prefix($table){
		global $SSC_SETTINGS;
		$table = '_' . $table;
		if (array_key_exists($table, $SSC_SETTINGS['db-prefix'])){
			return $SSC_SETTINGS['db-prefix'][$table] . $table;
		}
		else {
			return $SSC_SETTINGS['db-prefix']['default'] . $table;
		}
	}
	
	/**
	 * Get the number of rows affected or returned in the last operation
	 * @return int Number of rows affected or returned
	 */
	abstract public function number_rows();
	
	/**
	 * Fetch an associative array for the given resource
	 * @param resource $result Result from a previous query
	 * @return array Associative array containing row result
	 */
	abstract public function fetch_assoc($result);
	
	/**
	 * Fetch an object for the given resource
	 * @param resource $result Result from a previous query
	 * @return object Object containing row result
	 */
	abstract public function fetch_object($result);
	
	/**
	 * Returns a string containing the error message associated with the last query
	 * @return string Error message
	 */
	abstract public function error();
	
	/**
	 * Sets and execute a query on the current database object
	 * @param string $sql SQL query to exectute
	 * @param mixed $sql,... Arguments to be passed to the query for escaping  
	 * @return resource Query result
	 */
	abstract public function query($sql);
	
	/**
	 * Easy access for "paging" of a query.
	 * 
	 * Note: This function will generate a result containing 1 + $per_page rows
	 * or less if not enough rows are present.  This is used to determine if a
	 * 'next' page exists.
	 * 
	 * @param int $page Page to view, starting from 1
	 * @param int $per_page Number of items per page 
	 * @param string $sql SQL query (excluding LIMIT argument)
	 * @param mixed $sql,... Arguments to be passed to the query for escaping
	 * @return array Array containing information about the paging including
	 * 				whether or not a previous/next page exists and the query
	 * 				result object
	 */
	public function query_paged(){
		$args = func_get_args();
		$page = intval($args[0]);
		$per_page = intval($args[1]);
		unset($args[0], $args[1]);
		
		$args[2] .= " LIMIT " . ($page - 1) * $per_page . ", " . $page * $per_page;
		
		$result = call_user_func_array('$this->query', $args);
		$rows = $this->number_rows();
		
		return array(
				"result" => $result,
				"next" => ($rows == $per_page ? true : false),
				"previous" => ($page == 1 ? false : true)
				);
	}
}