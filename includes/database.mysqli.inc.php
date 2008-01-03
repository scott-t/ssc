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
	 * @see ssciDatabase::__construct()
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
	 * @see ssciDatabase::__destruct()
	 */
	function __destruct(){
		$this->link->close();
	}
	
	/**
	 * @see ssciDatabase::create_table()
	 */
	function create_table($structure){
		$sql = "CREATE TABLE " . $this->_set_table_prefix($structure['name']);
		echo '<pre>';print_r($structure);echo '</pre>';
		
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
			
		return $sql;
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
	function query(){
		echo $this->query;
	}
	
	/**
	 * @see ssciDatabase::set_query()
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