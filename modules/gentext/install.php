<?php $mod_install_name = "Generic Text"; 
	//gen_loc_type => 
	//					0 : header
	//					1 : single cell
	//					2 : double width
	//					3 : image

function install_module($ftp_conn){
	$sql = "CREATE TABLE gentext (".
				"gen_id smallint(6) NOT NULL auto_increment,".
				"gen_location smallint(6) NOT NULL default '0',".
				"gen_loc_id tinyint(4) NOT NULL default '0',".
				"gen_type tinyint(1) NOT NULL default '1',".
				"gen_dat text NOT NULL default '', ".
				"PRIMARY KEY (gen_id), ".
				"INDEX gen_location (gen_location),".
				"INDEX gen_loc_id (gen_loc_id) ) ".
				"ENGINE=MyISAM";
				
	$result = mysql_query($sql) or die(mysql_error());			
		
	return ($result ? true : false);} 
	
function uninstall_module(){
	$result = mysql_query("DROP TABLE gentext");
	return ($result ? true : false);} ?>