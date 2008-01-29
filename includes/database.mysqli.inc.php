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

$SSC_SETTINGS['db-engineclass'] = 'sscDatabaseMySQLi';

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
	 * @var mixed Contains the result from a query 
	 */
	private $result;
	
	/**
	 * @var int Flag indicating magic quote status 
	 */
	private $quote_status;
	
	/**
	 * Create the database connection
	 * @see ssciDatabase::__construct()
	 */
	function __construct(){
		global $SSC_SETTINGS;
		
		// Check if mysqli is available
		if (!function_exists('mysqli_connect')){
			ssc_die(array(
						'title' => 'Installation Error',
						'body'  => 'The MySQLI interface for PHP is not available'
					));
			return;
		}
		
		// Perform connection
		$this->link = new mysqli($SSC_SETTINGS['db-host'], $SSC_SETTINGS['db-user'], $SSC_SETTINGS['db-password'], $SSC_SETTINGS['db-database'], $SSC_SETTINGS['db-port']);
	}
	
	/**
	 * Clean up
	 * @see ssciDatabase::__destruct()
	 */
	function __destruct(){
		$this->link->close();
	}
	
	/**
	 * @see ssciDatabase::select_from()
	 */
	function select_from($structure){
		$sql = "SELECT ";
		
		// Get fields
		foreach ($structure['fields'] as $field){
			if (isset($sqlf)){
				$sqlf .= ", $field";
			}
			else{
				$sqlf = $field; 
			}
		}
		unset($sqlf);
		$sql .= " FROM ";
		
		// Get which tables
		foreach ($structure['table'] as $field){
			if (isset($sqlf)){
				$sqlf .= ", $field";
			}
			else{
				$sqlf = $field; 
			}
		}
		unset($sqlf);
		$sql .= " WHERE ";
		
	}
	
	/**
	 * @see ssciDatabase::insert_row()
	 */
	function insert_row($table, $values){
	
		$sql = "INSERT INTO " . $this->_set_table_prefix($table) . " (";
		$keys = array_keys($values);
		foreach ($keys as $key){
			if (isset($sqlf)){
				$sqlf .= ", $key";
			}
			else{
				$sqlf = $key;
				$first = $key;
			}
		}
		$sql .= $sqlf . ") VALUES ";
		
		// Get number of "rows" to insert
		$count = count($values[$first]);
		
		// Loop through each row
		for ($i = 0; $i < $count; $i++){
			// Comma separator
			if ($i > 0)
				$sql .= ", ";
				
			$sqlf = "(";
		
			// For each key
			foreach ($values as $key => $value){
				if ($sqlf != '(')
					$sqlf .= ', ';
						
				if (is_array($value))
					$value = $value[$i];
				
				if (is_string($value))
					$sqlf .= "'$value'";
				else
					$sqlf .= $value;
			}
			$sql .= "$sqlf)";
		}
		return $sql;
	}

	/**
	 * @see ssciDatabase::delete_row()
	 */
	function delete_row($table, $id){
		
		// Loop through each of the field parameters
		foreach ($id as $field => $value){
			// Quotes for string
			if (is_string($value))
				$value = "'$value'";
		
			// Ensure 'AND' is placed when needed
			if (!isset($sql)){
				$sql = "$field = $value";
			}
			else{
				$sql .= " AND $field = $value";
			}
			
		}
		$this->query("DELETE FROM " . $this->_set_table_prefix($table) . " WHERE $sql ");
		return "DELETE FROM " . $this->_set_table_prefix($table) . " WHERE $sql ";
	}
	
	/**
	 * @see ssciDatabase::update_row()
	 */
	function update_row($table, $values){
	
	}

	
	/**
	 * @see ssciDatabase::create_table()
	 */
	function create_table($structure){
		$sql = "CREATE TABLE " . $this->_set_table_prefix($structure['name']);
		echo '<pre>';var_dump($structure);echo '</pre>';
		
		// Number of fields table contains
		$fields = count($structure['fields']);
		
		// Opening bracket for field declaration if needed
		if ($fields > 0)
			$sql .= ' (';
			
		// Add each field
		$i = 0;
		foreach ($structure['fields'] as $key => $value){
			// Main column data
			if ($i == 0){
				$sqlf = " " . $key . ' ' . $this->_map_field_type($value) . ' ';
				$i++;
			}
			else {
				$sqlf = ", " . $key . ' ' . $this->_map_field_type($value) . ' ';
			} 
			
			// Allowed to be null?
			if (isset($value['null']) && $value['null'] == 1){
				$sqlf .= ' NULL ';
			} else {
				$sqlf .= ' NOT NULL ';
			}
			
			// Auto inc?
			if (isset($value['auto_inc']) && $value['auto_inc'] == 1){
				$sqlf .= ' AUTO_INCREMENT';
			}
			
			// Description?
			if (isset($value['description'])){
				$sqlf .= " COMMENT '$value[description]'";
			}
			
			$sql .= $sqlf;
		}
		
		// Primary keys
		$i = count($structure['primary']);
		if ($i > 0)
			$sqlf = ', PRIMARY KEY (';
		foreach ($structure['primary'] as $key => $value){
			if (intval($key) != 0){
				 $sqlf .= ", ";
			}
			
			$sqlf .= $value;
		}
		if ($i > 0)
			$sqlf .= ')';
			
		$sql .= $sqlf;
		
		// Unique keys
		$i = count($structure['unique']);
		if ($i > 0)
			$sqlf = ', UNIQUE (';
		foreach ($structure['unique'] as $key => $value){
			if (intval($key) != 0){
				 $sqlf .= ", ";
			}
			
			$sqlf .= $value;
		}
		if ($i > 0)
			$sqlf .= ')';
			
		$sql .= $sqlf;
		
		// Index fields
		$i = count($structure['index']);
		if ($i > 0)
			$sqlf = ', INDEX (';
		foreach ($structure['index'] as $key => $value){
			if (intval($key) != 0){
				 $sqlf .= ", ";
			}
			
			$sqlf .= $value;
		}
		if ($i > 0)
			$sqlf .= ')';
			
		$sql .= $sqlf;
		
		if ($fields > 0)
			$sql .= ') ';
			
		// Force MyISAM
		$sql .= " ENGINE = MyISAM";
		
		// Table comment
		if (isset($structure['description'])){
			$sql .= " COMMENT '$structure[description]'";
		}
		
		$this->query($sql);
		
		
		return $sql;
	}
	
	/**
	 * @see ssciDatabase::delete_table()
	 */
	function delete_table($table){
		$this->query("DROP TABLE " . $this->_set_table_prefix($table));
		return ($this->link->query() ? true : false);
	}
	
	/**
	 * @see ssciDatabase::empty_table()
	 */
	function empty_table($table){
		$this->query("TRUNCATE TABLE " . $this->_set_table_prefix($table));
		return ($this->link->query() ? true : false);
	}
	
	/**
	 * Maps the field structures type and size into the databases equivalent
	 * @param array $structure Field structure
	 * @return string SQL portion relating to field structure
	 */
	private function _map_field_type($structure){
		$ret = '';
		switch (strtolower($structure['type'])){
		case 'varchar':
			return "VARCHAR (" . intval($structure['size']) . ")";
			break;
		
		case 'text':
			return "TEXT";
			break;
		
		case 'blob':
			return "BLOB";
			break;
		
		case 'int':
			switch (strtolower($structure['size'])){
			case 'tiny':
				$ret = 'TINYINT';
				break;
			case 'small':
				$ret = 'SMALLINT';
				break;
			case 'medium':
				$ret = 'MEDIUMINT';
				break;
			case 'normal':
				$ret = 'INT';
				break;
			default:	// Large
				$ret = 'BIGINT';
				break;
			
			}
			break;
		
		case 'float':
			break;
		
		case 'datetime':
			return "TIMESTAMP";
			break;
		}
		
		if (isset($structure['unsigned']) && $structure['unsigned'] == 1){
			$ret .= ' UNSIGNED ';
		}

		return $ret;
	}
	
	/**
	 * @see ssciDatabase::query()
	 */
	function query($sql){
		$param = func_get_args();
		$param_count = count($param);
		for ($i = 1; $i < $param_count; $i++){
			// Escape string's as needed
			if (is_string($param[$i]))
				$param[$i] = $this->escape_string($param[$i]);
			
		}
		
		// Replace table name
		$param[0] = preg_replace('/#__(\w+)/e', "sscAbstractDatabase::_set_table_prefix('$1')", $sql);

		// Substitute in variables
		$sql = call_user_func_array('sprintf', $param);
		
		// Execute
		ssc_debug(array("title" => "database debug", "body" => $sql));
		return $this->link->query($sql);
	}
	
	/**
	 * @see ssciDatabase::error()
	 */
	public function error(){
		return $this->link->error;
	}
	
	/**
	 * @see ssciDatabase::number_rows()
	 */
	public function number_rows(){
		return $this->link->affected_rows;
	}
	
	/**
	 * @see ssciDatabase::fetch_assoc()
	 */
	public function fetch_assoc($result){
		return $result->fetch_assoc();
	}
	
	/**
	 * @see ssciDatabase::fetch_object()
	 */
	public function fetch_object($result){
		return $result->fetch_object();
	}
	
	/**
	 * Escape the current string
	 * @param string $str String to be escaped
	 * @return string Escaped string ready for insertion into the database
	 */
	function escape_string($str){
		$this->link->real_escape_string($str);
		return $str;
		//return str_replace(array('_', '%'), array('\\_', '\\%'),$str);
	}
}