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
	
	//save?
	if(isset($_POST['submit'])){
		echo warn("TODO");
		
		if(isset($_POST['name'], $_POST['desc'], $_POST['date'], $_POST['uri']))
		{
			if($_POST['name'] == ''){
				
			}else{
				echo error("Please enter an event name!");
			}
		}else{
			echo error("Not all fields were present!");
		}
		//use data that was submitted to re-fill form
	}else{
		//need to grab from query
		$eid = intval($_GET['edit']);
		$database->setQuery("SELECT dt, name, description, uri, type FROM #__events WHERE id = $eid LIMIT 1");
		$database->query();
		if(!($data = $database->getAssoc())){
			//no database content for supplied id.  gen new
			$eid = 0;
			$data['dt'] = date("Y-m-d");
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
	echo '<div><label for="date"><span class="popup" title="Optional. Defaults to today.  WARNING: Ambiguous dates may be wrongly interpreted.">Date</span></label><input type="text" name="date" id="date" value="',date("d-M-Y",strtotime($data['dt'])),'" /></div><br /><div><label for="uri"><span class="popup" title="Optional. Clickable link for further details on the event">Link for details</span></label><input type="text" maxlength="200" size="50" name="uri" id="uri" value="',$data['uri'],'" /></div><br /><div><label for="show"><span class="popup" title="Optional. Check if the \'more info\' link should be shown. Default yes if link entered">Show link</span></label><input type="checkbox" name="show" id="show" ',($data['type'] & 1?'checked="checked"':''),'" /></div><br />';
	echo '<br /><div class="btn"><input type="submit" value="',($eid==0?'Create':'Save'),' Event" name="submit" id="submit" /></div></fieldset></form><br class="clear" />';

	echo '<a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to events list';
}else{
	//show summaries of all events in system
	
	if(isset($_POST['delete'], $_POST['del-id'])){
		echo warn("TODO");
	}
	
	//display event list
	$database->setQuery("SELECT id, dt, name, description, uri, type FROM #__events ORDER BY dt ASC");
	$database->query();
	//set out table
	if($database->getNumberRows())
	{
		echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Events"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Date</th><th>Event Name</th><th>Extra Details</th><th>Show link</th><th>Link for details</th></tr>';
		while($data = $database->getAssoc()){
			echo '<tr><td>', $data['id'], '</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td>', $data['dt'], '</td><td><a href="', $sscConfig_adminURI, '/edit/', $data['id'], '">', $data['name'], '</a></td><td>', $data['description'], '</td><td><img src="',$sscConfig_adminImages, ($data['type'] & 1?'/done.png" alt="Yes" />':'/delete.png" alt="No" />'),'</td><td>', $data['uri'],'</td></tr>';
		}
		echo '</table><p><button type="submit" name="delete" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
	}else{
		echo message("There are currently no events set up");
	}
	echo '<a title="Create a new event" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New event</span></a><br />';
}

echo '</div>';
?>
