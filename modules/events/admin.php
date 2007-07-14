<?php
/**
 * Events admin page
 *
 * Administration on current/future/past events
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/event.png" alt="" /><span class="title">Events</span><hr class="admin" /><div class="indent">';
if(isset($_GET['edit'])){
	//need to edit event details
		
	//event id
	$eid = intval($_GET['edit']);
	
	//save?
	if(isset($_POST['submit'])){
		if(isset($_POST['name'], $_POST['desc'], $_POST['date'], $_POST['uri']))
		{
			if($_POST['name'] == ''){
				echo error("Please enter an event name!");
			}else{
				//check for blank/invalid date
				if(($_POST['date'] = strtotime(parseDate($_POST['date']))) == '')
				{
					$_POST['date'] = time();
					echo warn("Invalid date format.  Defaulted to today"),'<br />';
				}
			}
			if(isset($_POST['show']) && $_POST['uri'] != '')
				$_POST['type'] = 1;
			else
				$_POST['type'] = 0;
				
			//update database
			if($eid == 0){
				//new
				$database->setQuery(sprintf("INSERT INTO #__events (name, description, dt, uri, type) VALUES ('%s','%s','%s','%s', %d)",$database->escapeString($_POST['name']),$database->escapeString($_POST['desc']),date("Y-m-d",$_POST['date']),$database->escapeString($_POST['uri']),$_POST['type']));
				if($database->query())
					echo message("Event was added"),'<br />';
				$eid = $database->getLastInsertID();
			}else{
				//update
				$database->setQuery(sprintf("UPDATE #__events SET name = '%s', description = '%s', dt = '%s', uri = '%s', type = %d WHERE id = %d LIMIT 1",$database->escapeString($_POST['name']),$database->escapeString($_POST['desc']),date("Y-m-d",$_POST['date']),$database->escapeString($_POST['uri']),$_POST['type'],$eid));
				if($database->query())
					echo message("Event was updated"),'<br />';
			}
			
			
			
			//use data that was submitted to re-fill form
			$data['dt'] = date("d M Y", $_POST['date']);
			$data['name'] = $database->stripString($_POST['name']);
			$data['description'] = $database->stripString($_POST['desc']);
			$data['uri'] = $database->stripString($_POST['uri']);
			$data['type'] = $_POST['type'];
		}else{
			echo error("Not all fields were present!");
		}	
	}else{
		$database->setQuery("SELECT dt, name, description, uri, type FROM #__events WHERE id = $eid LIMIT 1");
		$database->query();
		if(!($data = $database->getAssoc())){
			//no database content for supplied id.  gen new
			$eid = 0;
			$data['dt'] = date("Y m d");
			$data['name'] = '';
			$data['description'] = '';
			$data['uri'] = '';
			$data['type'] = 0;
		}
	}
		
	//now output form
	echo '<form action="',$sscConfig_adminURI,'/../',$eid,'" method="post"><fieldset><legend>';
	//minor semantics...
	if($eid == 0){
		echo 'Create new event';
	}else{
		echo 'Edit existing event';
	}
	echo '</legend><!--[if IE]><br /><![endif]--><div><label for="name">Event Name</label><input type="text" name="name" maxlength="100" id="name" value="',$data['name'],'" /></div><br /><div><label for="desc"><span class="popup" title="Optional.  Default to blank">Brief description</span></label><input type="text" size="50" maxlength="250" name="desc" id="desc" value="',$data['description'],'" /></div><br />';
	echo '<div><label for="date"><span class="popup" title="Optional. Defaults to today.  WARNING: Ambiguous dates may be wrongly interpreted.">Date</span></label><input type="text" name="date" id="date" value="',date("d M Y",strtotime($data['dt'])),'" /></div><br /><div><label for="uri"><span class="popup" title="Optional. Clickable link for further details on the event. External links MUST start with http">Link for details</span></label><input type="text" maxlength="200" size="50" name="uri" id="uri" value="',$data['uri'],'" /></div><br /><div><label for="show"><span class="popup" title="Optional. Check if the \'more info\' link should be shown. Default yes if link entered">Show link</span></label><input type="checkbox" name="show" id="show" ',($data['type'] & 1?'checked="checked"':''),' /></div><br />';
	echo '<br /><div class="btn"><input type="submit" value="',($eid==0?'Create':'Save'),' Event" name="submit" id="submit" /></div></fieldset></form><br class="clear" />';

	echo '<a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to events list';
}else{
	//show summaries of all events in system
	
	if(isset($_POST['delete'], $_POST['del-id'])){
		//delete loop
		$count = count($_POST['del-id']);
		for($i = 0; $i < $count; $i++){
			//event id for each deleted event
			$eid = intval($_POST['del-id'][$i]);
			
			if($eid > 0)
			{
				$database->setQuery("DELETE FROM #__events WHERE id = $eid LIMIT 1");
				$database->query();
			}
		}
		echo message("Event(s) deleted"), '<br />';
	}elseif(isset($_POST['submit'], $_POST['title'], $_POST['nav-name'], $_POST['nav-uri'], $_POST['title-f'], $_POST['title-c'], $_POST['title-r'], $_POST['r-start'], $_POST['c-start'], $_POST['c-end'], $_POST['f-end'])){
		//change names
		$nid = intval($_POST['nid']);
		if($_POST['nav-uri'] == '' && $_POST['nav-name'] && $_POST['title'] && $_POST['c-end'] && $_POST['c-start'] && $nid == 0)
			echo error("Not all required fields were filled in!"),'<br />';
		else{
			//save stuff
			//check for leading / on uri
			if(strpos($_POST['nav-uri'],'/')!== 0)
				$_POST['nav-uri'] = '/'.$_POST['nav-uri'];
				
			$database->setQuery(sprintf("UPDATE #__navigation SET name = '%s', uri = '%s' WHERE id = %d LIMIT 1",$database->escapeString($_POST['nav-name']),$database->escapeString($_POST['nav-uri']), $nid));

			if($database->query()){
				//nav bar done.  now module display config...
				switch(true){
					case (strtotime($_POST['r-start']) == ''):
						echo warn("Invalid date range for recent events start.  Defaulted to today");
						$_POST['r-start'] = 'Today';
						break;
					case (strtotime($_POST['c-start']) == ''):
						echo warn("Invalid date range for current events start.  Defaulted to today");
						$_POST['c-start'] = 'Today';
						break;
					case (strtotime($_POST['c-end']) == ''):
						echo warn("Invalid date range for current events end.  Defaulted to today");
						$_POST['c-end'] = 'Today';
						break;
					case (strtotime($_POST['f-end']) == ''):
						echo warn("Invalid date range for future events end.  Defaulted to today");
						$_POST['f-end'] = 'Today';
						break;
				}
				$sql[0] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['title']), 'events_title');
				$sql[1] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['title-r']), 'events_title_recent');
				$sql[2] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['title-c']), 'events_title_current');
				$sql[3] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['title-f']), 'events_title_future');
				$sql[4] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['r-start']), 'events_recent_start');
				$sql[5] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['c-start']), 'events_current_start');
				$sql[6] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['c-end']), 'events_current_end');
				$sql[7] = sprintf("UPDATE #__module_config SET value = '%s' WHERE `key` = '%s' LIMIT 1", $database->escapeString($_POST['f-end']), 'events_future_end');
				for($i = 0; $i < 8; $i++){
					$database->setQuery($sql[$i]);
					if(!$database->query())
					{
						echo error("There was a problem updating the module configuration!"),'<br />';
						$i = -1;
						break;
					}
				}
				if($i >= 0)
					echo message("Module configuration was updated successfully"),'<br />';
				
			}else{
				echo error("There was a problem updating the navigation bar.  Please check it is still correct"),'<br />';
			}		
		}
	}
	
	//display event list
	$database->setQuery("SELECT id, dt, name, description, uri, type FROM #__events ORDER BY dt ASC");
	$database->query();
	//set out table
	echo '<form action="',$sscConfig_adminURI,'" method="post">';
	if($database->getNumberRows())
	{
		echo '<table class="tab-admin" summary="Events"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Date</th><th>Event Name</th><th>Extra Details</th><th>Show link</th><th>Link for details</th></tr>';
		while($data = $database->getAssoc()){
			echo '<tr><td>', $data['id'], '</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td>', date("d M Y",strtotime($data['dt'])), '</td><td><a href="', $sscConfig_adminURI, '/edit/', $data['id'], '">', $data['name'], '</a></td><td>', $data['description'], '</td><td><img src="',$sscConfig_adminImages, ($data['type'] & 1?'/done.png" alt="Yes" />':'/delete.png" alt="No" />'),'</td><td>', $data['uri'],'</td></tr>';
		}
		echo '</table><p><button type="submit" name="delete" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p>';
	}else{
		echo message("There are currently no events set up");
	}
	//get module settings
	$database->setQuery("SELECT #__navigation.name, uri, #__navigation.id FROM #__navigation, #__modules WHERE module_id = #__modules.id AND #__modules.filename = 'events' LIMIT 1");
	$database->query();
	if(!($data = $database->getAssoc())){
		echo error("Module installation error!");
	}else{
		$database->setQuery("SELECT id, 'key', value FROM #__module_config WHERE `key` LIKE 'events_%' ORDER BY id ASC");
		$database->query();
		if($database->getNumberRows() != 8){
			echo error("Module installation error!");
		}else{
			echo '<fieldset><legend>Events display setup</legend><div><input type="hidden" name="nid" id="nid" value="',$data['id'],'"/><label for="nav-name">Nav-bar text</label><input type="text" maxlength="30" name="nav-name" id="nav-name" value="',$data['name'],'" /></div><div><label for="nav-uri">Page URI</label><input type="text" maxlength="60" name="nav-uri" id="nav-uri" value="',$data['uri'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="title">Page title</label><input type="text" maxlength="30" name="title" id="title" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="title-r"><span class="popup" title="Header for recent events listing.  Leave blank to not display">Recent title</span></label><input type="text" maxlength="30" name="title-r" id="title-r" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="title-c">Current title</label><input type="text" maxlength="30" name="title-c" id="title-c" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="title-f">Future title</label><input type="text" maxlength="30" name="title-f" id="title-f" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="r-start"><span class="popup" title="Date for start of recent event list.  Blank for no limit.">Recent end</span></label><input type="text" maxlength="30" name="r-start" id="r-start" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="c-start"><span class="popup" title="Date for the border of recent and current events">Current end</span></label><input type="text" maxlength="30" name="c-start" id="c-start" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="c-end"><span class="popup" title="Date for the border of current and future events">Current start</span></label><input type="text" maxlength="30" name="c-end" id="c-end" value="',$data['value'],'" /></div>';
			$data = $database->getAssoc();
			echo '<div><label for="f-end"><span class="popup" title="Date for end of future events. Blank for no limit">Future end</span></label><input type="text" maxlength="30" name="f-end" id="f-end" value="',$data['value'],'" /></div>';
			echo '<div class="btn"><input type="submit" value="Update Config" name="submit" id="submit" /></fieldset>';
		}
	}
	echo '</form><br class="clear" /><a title="Create a new event" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New event</span></a><br />';
}

echo '</div>';
?>
