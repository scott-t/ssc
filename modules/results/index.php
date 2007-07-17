<?php 
/**
 * Race results frontend
 *
 * Front-end display of race results
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

global $database;

$database->setQuery(sprintf("SELECT id, name, description FROM #__results_series WHERE nav_id = %d LIMIT 1",$_GET['pid']));
if ($database->query() && $data = $database->getAssoc()){
	echo '<h1>',$data['name'],'</h1>';
	if($data['description']!='')echo $data['description'],'<br />';
	echo 'Placings are only approximate';
	$database->setQuery("SELECT no, skipper, crew, class, name, results, division FROM #__results_results, #__results_entries WHERE series_id = ".$data['id']." AND number = no ORDER BY division ASC, points ASC, no ASC");
	$database->query();
	if($database->getNumberRows() > 0){
		//yay.  results exist
		$data = $database->getAssoc();
		$curDiv = '\0';
		$intable = false;
		$rowNo = 0;$size = 0;
		do{
			//check for change in div
			
			$results = explode(',',$data['results']);
			if($curDiv != $data['division'])
			{
				$curDiv = $data['division'];
				if($intable){
					echo '</table>';
				}
				echo '<h2>',$curDiv,'</h2><table class="tab-highlight" summary="Results for ',$curDiv,'"><tr><th>Sail No</th><th>Class</th><th>Boat Name</th><th>Skipper</th>';
				//number of races we have?
				$size = count($results);
				for($i = 1; $i <= $size; $i++){
					echo "<th>R$i</th>";
				}
				echo '</tr>';
				$intable = true;
				$rowNo = 0;
			}
			echo '<tr class="alt-',($rowNo++ % 2),'"><td>', substr($data['no'],0,strpos($data['no'],'+')), '</td><td>', $data['class'], '</td><td>', $data['name'], '</td><td>';
			if($data['crew'] == ''){
				echo $data['skipper'];
			}else{
				echo '<span class="popup" title="Crew: ',$data['crew'],'">',$data['skipper'],'</span>';
			}
			echo '</td>';
			
			for($i = 0; $i < $size; $i++)
				echo '<td>',$results[$i],'</td>';
				
			echo '</tr>'; 
		}while($data = $database->getAssoc());
		//if($intable)
			echo '</table>';
	}
}else{
	echo error("Error retrieving series details");
}

?>