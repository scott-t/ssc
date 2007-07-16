<?php 
/**
 * Race results admin
 *
 * Perform administration on race results
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');


echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/events.png" alt="" /><span class="title">Race Results</span><hr class="admin" /><div class="indent">';

if(isset($_GET['edit'])){

	$rid = intval($_GET['edit']);
	
	//save?	
	if(isset($_POST['sub-save']) || isset($_POST['sub-up'])){
		//mod id
		$database->setQuery("SELECT id FROM #__modules WHERE filename = 'results' LIMIT 1");
		$database->query();
		if($data = $database->getAssoc()){
			$mid = $data['id'];	//retrieve module id number
					
			//ensure correct stuff
			if(isset($_POST['nav-id'], $_POST['nav-name'], $_POST['nav-uri'], $_POST['name'], $_POST['desc'], $_POST['date']) && $_POST['name'] != '' && $_POST['date'] != '' && $_POST['nav-uri'] != ''){
				
				if(($_POST['date'] = strtotime(parseDate($_POST['date']))) == ''){
					echo warn('Invalid date choice.  Defaulted to today'),'<br />';
					$_POST['date'] = time();
				}
				$_POST['date'] = date("Y-m-d",$_POST['date']);
				
				//leading '/'?
				if(strpos($_POST['nav-uri'],'/') !== 0)
					$_POST['nav-uri'] = '/'.$_POST['nav-uri'];
				
				//nav id
				$nid = intval($_POST['nav-id']);
				
				if(($nid == 0 || $rid == 0) && $nid != $rid){
					echo error("Invalid input combinations"),'<br />';
				}else{
					//navigation bar
					if($nid == 0){
						$database->setQuery(sprintf("INSERT INTO #__navigation (module_id, name, uri, position, hidden) VALUES (%d, '%s', '%s', 70, 1)",$mid, $database->escapeString($_POST['nav-name']), $database->escapeString($_POST['nav-uri'])));
						$database->query();
						$nid = $database->getLastInsertID();
					}else{
						$database->setQuery(sprintf("UPDATE #__navigation SET name  = '%s', uri = '%s' WHERE id = %d LIMIT 1", $database->escapeString($_POST['nav-name']), $database->escapeString($_POST['nav-uri']), $nid));
						$database->query();
					}
					
					if($nid == 0){
						echo warn("There was a problem updating the navigation bar.  Check for errors"),'<br />';
					}else{
						//nav updated... now ourselves
						if($rid == 0){
							$database->setQuery(sprintf("INSERT INTO #__results_series (nav_id, name, description, dt) VALUES (%d, '%s', '%s', '%s')", $nid, $database->escapeString($_POST['name']), $database->escapeString($_POST['desc']), $database->escapeString($_POST['date'])));
							$database->query();
							$rid = $database->getLastInsertID();
						}else{
							$database->setQuery(sprintf("UPDATE #__results_series SET nav_id = %d, name = '%s', description = '%s', dt = '%s' WHERE id = %d LIMIT 1", $nid, $database->escapeString($_POST['name']), $database->escapeString($_POST['desc']), $database->escapeString($_POST['date']), $rid));
							$database->query();
						}
						echo warn($database->getQuery().'<br />'.$database->getErrorMessage()),'<br />';
						
						if(isset($_POST['sub-up'])){
							//race result parsing
							echo warn("TODO: Result parsing");
						}else{
							if($rid > 0){
								echo message("Series details updated");
							}else{
								echo error('There was a problem updating series details');
							}
						}
						echo '<br />';
					}
				}
			}else{
				echo error("Not all required fields were filled in"),'<br />';
			}
		}else{
			echo error("Problem retrieving module id number"),'<br />';
		}
	}


	if($rid > 0){	
		$database->setQuery("SELECT nav_id,  #__navigation.name AS nav_name, uri, #__results_series.name, description, dt FROM #__results_series, #__navigation WHERE #__results_series.id = $rid AND #__navigation.id = nav_id LIMIT 1");
		if($database->query() && $data = $database->getAssoc()){
		//existing stuff...
		}else{
			$data['name'] = '';
			$data['description'] = '';
			$data['dt'] = 'today';
			$data['nav_id'] = 0;
			$data['nav_name'] = 'Results';
			$data['uri'] = '/results';
			$rid = 0;
		}
	}else{
		$data['name'] = '';
		$data['description'] = '';
		$data['dt'] = 'today';
		$data['nav_id'] = 0;
		$data['nav_name'] = 'Results';
		$data['uri'] = '/results';
		$rid = 0;
	}
	echo '<form action="',$sscConfig_adminURI,'/../',$rid,'" enctype="multipart/form-data" method="post"><fieldset><legend>Regatta details</legend><!--[if IE]><br /><![endif]--><div><label for="name">Name</label><input type="text" name="name" maxlength="100" id="name" value="',$data['name'],'" /></div>';
	echo '<div><label for="desc">Description</label><input type="text" size="50" maxlength="255" name="desc" id="desc" value="',$data['description'],'" /></div><div><label for="date"><span class="popup" title="This must be manually updated">Date updated</span></label><input type="text" name="date" id="date" value="',date("d M Y", strtotime($data['dt'])), '" /></div>';
	echo '<div><input type="hidden" name="nav-id" value="',$data['nav_id'],'" /><label for="nav-name"><span class="popup" title="Change item visibility in the navigation bar module.  Defaults hidden">Navbar text</span></label><input type="text" name="nav-name" id="nav-name" value="',$data['nav_name'],'" /></div><div><label for="nav-uri"><span class="popup" title="eg /results/series01">URI</span></label><input type="text" name="nav-uri" id="nav-uri" value="',$data['uri'],'" /></div><div class="btn"><input type="submit" value="Save only" name="sub-save" /></div></fieldset>';
	echo '<fieldset><legend>Upload results</legend><!--[if IE]><br /><![endif]--><div><label for="csv"><span class="popup" title="Location of the file containing the results.  This should be a specially formatted CSV file">CSV to upload</span></label><input type="hidden" name="MAX_FILE_SIZE" value="20480" /><input type="file" name="csv" id="csv" value="" /></div><div class="btn"><input type="submit" value="Save and upload" name="sub-up" /></div></fieldset>';
	echo '</form>';
	echo '<br class="clear" /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to regatta list';
}else{
	
	//check for deletions
	if(isset($_POST['del'])){
		echo warn("TODO: DELETE");
	}

	//display list of race series'
	$database->setQuery("SELECT id, name, description, dt FROM #__results_series ORDER BY name ASC");
	if($database->query() && $database->getNumberRows() > 0){
		echo '<form action="',$sscConfig_adminUri,'" method="post"><table class="tab-admin" summary="List of race series\'"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Name</th><th>Description</th><th><span class="popup" title="Determines order sorted by">Updated date</span></th></tr>';
		while($data=$database->getAssoc()){
			echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="', $sscConfig_adminURI,'/edit/',$data['id'],'">',$data['name'],'</a></td><td>',$data['description'],'</td><td>',$data['dt'],'</td></tr>';
		}
		echo '</table><p><button type="submit" name="del" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
	}else{
		echo message("Currently no regattas have been made"),'<br />';
	}
	echo '<a title="Create a new regatta" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New regatta</span></a><br />';
}

echo '</div>';
?>