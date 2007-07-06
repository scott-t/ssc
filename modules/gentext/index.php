<?php if(isset($_GET['id'])){ 
	//ok.  how to tackle this one...
	
	
	$sql = sprintf("SELECT * FROM gentext WHERE gen_location = %d ORDER BY gen_loc_id ASC", $_GET['id']);
	$result = mysql_query($sql);

	?><table width="90%" class="center"><tr><td style="text-align:center " colspan="2"><h4><? if($dat=mysql_fetch_assoc($result)){echo $dat['gen_cont'];}?> </h4></td></tr><?php
		//make defaults
	$col = 0;
	while ($dat = mysql_fetch_assoc($result)){
		//while there are rows left...
		switch($dat['gen_type']){
			//choose appropriate layouts
			case 1:
			case 3:
				//single column
				if($col == 0){
					echo "<tr>";
				}
				echo "<td>";
				//check if we txt of img
				switch($dat['gen_type']){
					case 1:
						echo $dat['gen_dat'];
						break;
					case 3:
						echo "<img src=\"".$dat['gen_dat']."\" alt=\"Image\" />";
						break;
				}
				echo "</td>";
				if($col != 0){
					echo "</tr>";
				}
				$col = ($col++) % 2;
				break;
			case 2:
				if($col != 0){
					echo "<td>&nbsp;</td></tr>";
					$col = 0;
				}
				//double width
				echo "<tr><td colspan=\"2\">".$dat['gen_dat']." </td></tr>";
				
				break;
			}
	}
	
	
	
	?></table>
	<?php
	}?>