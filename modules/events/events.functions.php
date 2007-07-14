<?php
/**
 * Events module functions
 *
 * Functions that the events module uses
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Generate a table listing events from $start to $end.  strToTime WILL be run on it
 * @param string $start Starting date for event list
 * @param string $end End date for event list
 * @param int $asc Direction to sort.  Ascending if >= 1, Descending otherwise
 * @return int 1 if success, -1 if fail
 */
function showEvent($start = null, $end = null, $asc = 1){
	global $sscConfig_webPath, $database;

	if($asc)
		$dir = "ASC";
	else
		$dir = "DESC";


	if($start == null){
		if($end == null){
			//neither set.  grab all
			$database->setQuery("SELECT name, description, dt, uri, type FROM #__events ORDER BY dt $dir");
			//$summary = "all events";
		}else{
			//end set.  all till $end
			$end = date("Y-m-d",strtotime($end));
			$database->setQuery("SELECT name, description, dt, uri, type FROM #__events WHERE dt <= '".$end."' ORDER BY dt $dir");
			//$summary = "events up until " . $end;
		}
	}else{
		$start = date("Y-m-d",strtotime($start));
		if($end == null){
			//start but not end
			$database->setQuery("SELECT name, description, dt, uri, type FROM #__events WHERE dt >= '".$start."' ORDER BY dt $dir");
			//$summary = "events before " . $start;
		}else{
			//both fine
			$end = date("Y-m-d",strtotime($end));
			$database->setQuery("SELECT name, description, dt, uri, type FROM #__events WHERE dt > '".$start."' AND dt < '".$end."' ORDER BY dt $dir");
			//$summary = "events between " . $start . " and " . $end;
		}
	}
	
	$database->query();
	
	//echo '<table summary="List of ',$summary,'"><tr><th>Name</th>';
	if($database->getNumberRows() > 0){
		echo '<ul class="mod-events">';
		while($data = $database->getAssoc()){
			echo '<li>', date("d M y",strtotime($data['dt'])), ' - ';
			if($data['type'] & 1){
				//show link
				if(strpos($data['uri'],'http') === 0){
					echo '<a href="', $data['uri'], '">',$data['name'],'</a>';
				}else{
					echo '<a href="', $sscConfig_webPath, $data['uri'], '">',$data['name'],'</a>';
				}
			}else{
				echo $data['name'];
			}
			if($data['description'] != '')
				echo '<br />', $data['description'];
			
			echo '</li>';
			
		}
		
		echo '</ul>';
		return 1;
	}else{
		return -1;
	}
	echo '';
	
}
?>
