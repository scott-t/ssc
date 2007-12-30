<?php
/**
 * @file
 * Database engine using the mysqli interface.
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Interface for database objects 
 */
interface ssciDatabase{
	/**
	 *	Create the database object
	 */
	public function __construct();
	
	/**
	 * Clean up
	 */
	public function __destruct();
	
	/**
	 * Sets the current database object query
	 * @param $sql 
	 * 		SQL query to exectute
	 * @param ...
	 * 		Arguments to be passed to the query for escaping  
	 */
	public function set_query($sql);
	
	/**
	 * Execute the stored query
	 */
	public function query();

}