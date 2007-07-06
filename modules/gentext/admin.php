<?php if(isset($_GET['sec'])){ 

	//ok.  how to tackle this one...
	
	if (isset($_POST['submit'])){
		//make changes here...
		print_r($_POST);
		$skip = 0;
		foreach($_POST as $name => $value){
			//ok.  now i'm looping (in order) thru everything...
			if($skip>0){
				if(intval($name) > 0){
					--$skip;
				}			
				
			}else{
			
			switch(substr($name, 0, 3)){
				case "row":
				//change the row type...
					$id = intval(substr($name, 3));
					if($id > 0){
						switch ($value)
						{
							case 1:
								//text | image
										// first, second, action
										//	 1       3       nothing
										//	 2       -2      2=>1, -2=>3
										//	 3       1       swap loc_id
										//	 3       3       3=>1, nothing

							case 2:
								//  T E X T
										// first, second, action
										//	 1       3       1=>2, 3=>-2, 3 content append new 2
										//	 2       -2      nothing
										//	 3       1       3=>2, 1=>-2, 1 content append new 2
										//	 3       3       3=>2, 3=>-2, 3 . 3 into new 2
							
							case 3:
								//image | text
										// first, second, action
										//	 1       3       nothing
										//	 2       -2      2=>1, -2=>3
										//	 3       1       swap loc_id
										//	 3       3       3=>1, nothing
							
							case 4:
								//image | image
										// first, second, action
										//	 1       3       nothing
										//	 2       -2      2=>1, -2=>3
										//	 3       1       swap loc_id
										//	 3       3       3=>1, nothing
						}
					}
					break;			
				
				case "del":
				//reached a delete row...
					if($value == true){
						//only act if actually checked tho this is just precautionary
						$loc_id = substr($name, 3);
						$sql = sprintf("SELECT gen_id, gen_dat FROM gentext WHERE gen_location = %d AND gen_loc_id = %d LIMIT 1", $_GET['sec'], $loc_id);
						$dat = mysql_fetch_assoc(mysql_query($sql));
						$sql = sprintf("DELETE FROM gentext WHERE gen_id = %d", $dat['gen_id']);
						mysql_query($sql);
						$file = $_GET['sec']."-".$dat['gen_id'].".".substr($dat['gen_dat'],0,3); 
						if(file_exists($file)){	unlink($file); }
						
						$sql = sprintf("SELECT gen_id, gen_dat FROM gentext WHERE gen_location = %d AND gen_loc_id = (%d + 1) LIMIT 1", $_GET['sec'], $loc_id);
						$dat = mysql_fetch_assoc(mysql_query($sql));
						$sql = sprintf("DELETE FROM gentext WHERE gen_id = %d", $dat['gen_id']);
						mysql_query($sql);					
						$file = $_GET['sec']."-".$dat['gen_id'].".".substr($dat['gen_dat'],0,3);					
						if(file_exists($file)){	unlink($file); }
						
						//deleted rows. now skip past relevant $_POST fields
						$skip = 2;
					}
				break;
			
			//=========== NEW ROW
			
				case "new":
					$sql = sprintf("SELECT MAX(gen_loc_id) AS count FROM gentext WHERE gen_location = %d", $_GET['sec']);
					$dat = mysql_fetch_assoc(mysql_query($sql));
					//reached the end.  add a row
					switch (intval($value)){
						case 1:
							//text | image
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 1, %d, ' ')",$_GET['sec'],$dat['count']+1);
							mysql_query($sql);
							
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 3, %d, ' ')",$_GET['sec'], $dat['count']+2);
							mysql_query($sql);				
							break;	
						case 3:
							//image | text
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 3, %d, ' ')",$_GET['sec'],$dat['count']+1);
							mysql_query($sql);
							
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 1, %d, ' ')",$_GET['sec'], $dat['count']+2);
							mysql_query($sql);											
							break;	
						case 4:
							//image | image
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 3, %d, ' ')",$_GET['sec'],$dat['count']+1);
							mysql_query($sql);
							
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 3, %d, ' ')",$_GET['sec'], $dat['count']+2);
							mysql_query($sql);										
							break;	
						case 2:
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, 2, %d, ' ')",$_GET['sec'],$dat['count']+1);
							mysql_query($sql);
							
							$sql = sprintf("INSERT INTO gentext (gen_location, gen_type, gen_loc_id, gen_dat) VALUES (%d, -2, %d, ' ')",$_GET['sec'], $dat['count']+2);
							mysql_query($sql);					//blank placeholder if a dbl converted to two singles
							break;						
					}
					break;
					//================================== END NEW ROW							
											
				default:
					//make changes here to set texts...
					break;
			}
			}

			
			
			
		}
		
	}
	
	echo '<h4>Page display modification</h4><span class="italic">All actions will be performed without confirmation.  Be careful with checkboxes</span><br /><br />';
	echo '<div id="cont" class="table">';
	
	// one div for each row
	//		nested divs for each cell
	
	echo '<div id="row"><div></div><div>cell</div><div>cell</div></div><br /><div id="row"><div>cell</div><div>cell</div><div>cell</div></div>';
	
	echo '</div>';
	
	
	
	
	
	
	
	
	
	echo "<h4>Page display modifications</h4><span class=\"italic\">All these actions will be performed without confirmation.  Be careful</span><br /><br />";
	$sql = sprintf("SELECT module_name FROM modules WHERE module_id = %d LIMIT 1", $_GET['sec']);
	$result = mysql_query($sql);
	if($dat = mysql_fetch_assoc($result)){$head = $dat['module_name'];}else{$head = " ";}
	
	$sql = sprintf("SELECT * FROM gentext WHERE gen_location = %d ORDER BY gen_loc_id ASC", $_GET['sec']);
	$result = mysql_query($sql);

	?><form action="" method="post" enctype="multipart/form-data"><table width="90%" class="center"><tr><td style="border-bottom: thin solid; ">(nav-bar)<br /><input type="text" alt="Heading" name="head" value="<?php echo $head; ?>" /></td><td style="text-align:center; border-bottom: thin solid; " colspan="2">(title text)<br /><input type="text" alt="Heading" name="head" value="<?php if($dat=mysql_fetch_assoc($result)){echo $dat['gen_dat'];} ?>" /></td></tr><?php
		//make defaults
	$col = 0;
	while ($dat = mysql_fetch_assoc($result)){
	
		if($dat['gen_type'] > 0){
			//while there are rows left...
			//allow for layout of rows to change
			if(($col != 0) && ($dat['gen_type'] == 2)){
				echo "<td>&nbsp;</td></tr>";$col = 0;
			}
			if($col == 0){
				?><tr><td style="border-bottom: thin solid; border-right:thin solid; "><input type="checkbox" name="del<?php $dat['gen_loc_id'];?>" alt="Delete this row?" /> Delete row<br /><br /><table class="center"><tr><td colspan="3">Row Layout</td></tr><tr><td><input type="radio" name="row<?php echo $dat['gen_loc_id'];?>" value="1" alt="Text | Image" /></td><td>Text&nbsp;</td><td>&nbsp;Image</td></tr><tr><td><input type="radio" name="row<?php echo $dat['gen_loc_id'];?>"  value="3" alt="Image | Text" /></td><td>Image&nbsp;</td><td>&nbsp;Text</td></tr><tr><td><input type="radio" name="row<?php $dat['gen_loc_id'];?>" value="4" alt="Image | Image" /></td><td>Image&nbsp;</td><td>&nbsp;Image</td></tr><tr><td><input type="radio" name="row<?php $dat['gen_loc_id'];?>" value="2"  alt="Text" /></td><td colspan="2">Text</td></tr></table></td><?php
			}
			
			
			
			switch($dat['gen_type']){
				//choose appropriate layouts
				case 1:
				case 3:
					//single column
					echo "<td style=\"border-bottom: thin solid; \">";
					//check if we txt of img
					switch($dat['gen_type']){
						case 1:
							echo "<textarea cols=\"35\" rows=\"5\" name=\"".$dat['gen_loc_id']."\">".$dat['gen_dat']."</textarea>";
							break;
						case 3:
							echo "<img src=\"".$dat['gen_dat']."\" alt=\"Uploaded Image\" /><br /><input type=\"file\" alt=\"Replacement Image\" name=\"f".$dat['gen_loc_id']."\" /><br /><br />Caption<input type=\"text\" alt=\"Caption\" name=\"".$dat['gen_loc_id']."\" />";
							break;
					}
					echo "</td>";
					if($col != 0){
						echo "</tr>";
					}
					$col = ($col+1) % 2;
					break;
				case 2:
					//double width
					echo "<td style=\"border-bottom: thin solid; \" colspan=\"2\"><textarea cols=\"70\" rows=\"5\" name=\"".$dat['gen_loc_id']."\">".$dat['gen_dat']."</textarea></td></tr>";
					
					break;
			}			
		}
	}	
	if($col != 0){
		//yet another safegaurd against dodgy layouts
		echo "<td style=\"border-bottom: thin solid; \">&nbsp;</td></tr>";
		$col = 0;
	}

	
	?><tr><td colspan="3">&nbsp;<br /><table class="center"><tr><td style="font-weight:bold; "colspan="3">Insert new row</td></tr><tr><td><input type="radio" name="new" value="1" alt="Text | Image" /></td><td >Text&nbsp;</td><td>&nbsp;Image</td></tr><tr><td><input type="radio" name="new" value="3" alt="Image | Text" /></td><td  >Image&nbsp;</td><td>&nbsp;Text</td></tr><tr><td><input type="radio" name="new" value="4" alt="Image | Image" /></td><td>Image&nbsp;</td><td>&nbsp;Image</td></tr><tr><td><input type="radio" name="new" value="2" alt="Text" /></td><td colspan="2">Text</td></tr></table><br />&nbsp;</td></tr><tr><td colspan="3"><input type="reset" value="Revert to saved data" name="reset" alt="Undo changes" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="Save changes" alt="Update page content to show changes" /></td></tr></table></form><?php

}?>