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
						
						if(isset($_POST['sub-up'])){
							//race result parsing
							//this is gonna be painful...
							if(isset($_FILES['csv']['name'])){
								//file was spec'd
								switch ($_FILES['csv']['error']){
									case UPLOAD_ERR_OK:
										//upload successful
										//hmm.  gotta parse things now (*cringe*)
										$tmpFile = $_FILES['csv']['tmp_name'];
										//for our own error parsing
										$errStatus = 0;
										$handle = fopen($tmpFile, 'r');
										$line[0] = '';
										//get past the header
										do{
											$line = fgetcsv($handle,1024);
										}while ($line[0] == '');

										//parse column headers...
										$count = count($line);
										//defaults
										$div = -1;
										$skipper = -2;
										$crew = -3;
										$class = -4;
										$boat = -5;
										$club = -6;
										$sail = -7;
										$results = -8;
										for($i = 0; $i<$count; $i++){
											switch(strtolower($line[$i])){
												case 'div':
												case 'division':
													$div = $i;
													break;
												case 'skipper':
													$skipper = $i;
													break;
												case 'crew':
													$crew = $i;
													break;
												case 'club':
													$club = $i;
													break;
												case 'class':
													$class = $i;
													break;
												case 'name':
												case 'boat':
												case 'boat name':
													$boat = $i;
													break;
												case 'sail':
												case 'sail no.':
												case 'sail no':
												case 'sail number':
													$sail = $i;
													break;
													
												default:
													if(intval($line[$i])>0){
														$results = $i;
														break 2;	
													}
											}
										}
										
										//check which weren't detected
										$missing = '';
										$missing_cnt = 0;
										$crit = false;
										if($div < 0){
											$missing .= 'Division, ';
											$crit = true;
										}
										if($skipper < 0){
											$missing .= 'Skipper, ';
											$crit = true;
										}
										if($sail < 0){
											$missing .= 'Sail Number, ';
											$crit = true;
										}
										if($results < 0){
											$missing .= 'Results, ';
											$crit = true;
										}
										
										//non critical
										if($boat < 0){
											$missing .= 'Boat Name, ';
											$missing_cnt ++;
										}
										if($crew < 0){
											$missing .= 'Crew, ';
											$missing_cnt ++;
										}
										
										if($class < 0){
											$missing .= 'Class, ';
											$missing_cnt ++;
										}
											
										if($club < 0){
											$missing .='Club, ';
											$missing_cnt ++;
										}

										if($missing != ''){
											$msg = 'The following fields were not detected in the uploaded file: ' . substr($missing,0,-2);
											if($crit)
												echo error($msg . '<br /><br />Some of these were required.  Parsing will not continue.'), '<br />';
											else
												echo warn($msg), '<br />';
										}
										
										$missing_cnt += $results;
										
										if(!$crit){
											//so we know if some boats disappear from results
											$database->setQuery("UPDATE #__results_results SET results = '' WHERE series_id = " . $rid);
											$database->query();
											
											//now to parse the data
											while(($line = fgets($handle, 1024))!==FALSE){
												$line = explode(',',htmlspecialchars($line)); 

												if($line[$sail] != ''){
													//echo $line[$sail], ' - ';
													$line[$sail] = substr($line[$sail],0,16);
													$database->setQuery(sprintf("SELECT no, skipper, class, name, crew, club FROM #__results_entries WHERE no LIKE '%s+%%' ORDER BY no ASC", $database->escapeString($line[$sail])));
													$database->query();
													$pad = 0;
													$match = false;
													while($data = $database->getAssoc()){
														//got rows... try to match up
														$match = true;
														if($crew >= 0 && $line[$crew] != $data['crew'])
															$match = false;
														
														if($line[$skipper] != $data['skipper'])
															$match = false;
															
														if($class >= 0 && $line[$class] != $data['class'])
															$match = false;
															
														if($club >= 0 && $line[$club] != $data['club'])
															$match = false;
															
														if($boat >= 0 && $line[$boat] != $data['name'])
															$match = false;
													
														if($match == true){
															//got thru our checks
															$line[$sail] = $data['no'];
														}else{
															$pad++;
														}
													}
													
													if(!$match){
														//need to insert new
														$line[$sail] .= "+$pad";
														if($crew < 0)
															$line[$crew] = '';
																												
														if($class < 0)
															$line[$class] = '';
															
														if($club < 0)
															$line[$club] = '';
															
														if($boat < 0)
															$line[$boat] = '';
															
														$database->setQuery(sprintf("INSERT INTO #__results_entries (no, skipper, class, name, crew, club) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",$database->escapeString($line[$sail]),$database->escapeString($line[$skipper]),$database->escapeString($line[$class]),$database->escapeString($line[$boat]),$database->escapeString($line[$crew]),$database->escapeString($line[$club])));
														$database->query();
													}
													
													//should now have a boat matching the description
													//insert results for said boat
													$database->setQuery(sprintf("SELECT id, number, results, points, division FROM #__results_results WHERE number = '%s' AND series_id = %d LIMIT 1",$database->escapeString($line[$sail]),$rid));
													$database->query();
													$finish = implode(array_slice($line,$results,count($line)-$missing_cnt),',');
													//echo $finish, '<br />';
													//print_r($line);echo  '<br />';print_r($results);echo '<br />';
																											
													if($data = $database->getAssoc()){
														//update existing
														$database->setQuery(sprintf("UPDATE #__results_results SET results = '%s', division = '%s' WHERE id = %d LIMIT 1",$database->escapeString($finish), $database->escapeString($line[$div]), $data['id']));
														//$database->query();
														
													}else{
														//new
														$database->setQuery(sprintf("INSERT INTO #__results_results (number, series_id, results, points, division) VALUES ('%s', %d, '%s', 0, '%s')",$database->escapeString($line[$sail]),$rid,$database->escapeString($finish),$database->escapeString($line[$div])));
														//$database->query();
													}
													$database->query();
	
													//results should now be in
													//
												}
											}
											
											//finished with file now
											fclose($handle);
											
											//work out approx placings
											$database->setQuery("SELECT division, COUNT(division) AS entries FROM #__results_results WHERE series_id = $rid GROUP BY division ORDER BY division ASC");
											$divResult = $database->query();
											$curDiv = '\0';
											
											$database->setQuery("SELECT id, results, division FROM #__results_results WHERE series_id = $rid ORDER BY division ASC");
											$resultResult = $database->query();
											
											$data = $database->getAssoc();
											while($divData = $database->getAssoc($divResult)){
												//loop thru each div
												$curDiv = $divData['division'];
												do{
													if($curDiv != $data['division'])
														continue 2;
													
													$finish = $data['results'];
													if(preg_match("/[[:alpha:]]+/i",$finish) > 0)
														$finish = preg_replace("/[[:alpha:]]+/i","" . $divData['entries'], $finish);
													

													$points = array_sum(explode(',',$finish));

													$database->setQuery(sprintf("UPDATE #__results_results SET points = %d WHERE id = %d LIMIT 1", $points, $data['id']));
													$database->query();
												}while($data = $database->getAssoc($resultResult));
												break;
											}
											
											$database->freeResult($divResult);
											$database->freeResult($resultResult);
											
											//clean up
											$database->setQuery("DELETE FROM #__results_results WHERE series_id = $rid AND points = 0");
											$database->query();
											
										}
										
										break;
									case UPLOAD_ERR_NO_FILE:
										echo warn("No input file specified.  No results added"),'<br />';
										break;
									default:
										echo error("There was a problem with the file upload.  Check the correct file was selected and try again"),'<br />';
										break;
								}
							}else{
								echo warn("No input file specified.  No results added"),'<br />';
							}
						}
						
						if($rid > 0){
							echo message("Series details updated");
						}else{
							echo error('There was a problem updating series details');
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
		echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="List of race series\'"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Name</th><th>Description</th><th><span class="popup" title="Determines order sorted by">Updated date</span></th></tr>';
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