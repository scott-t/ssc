<?php
/**
 * Dynamic text page
 *
 * Create/edit/delete pages with "dynamic" content
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');


echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/text.png" alt="" /><span class="title">Dynamic Pages</span><hr class="admin" /><div class="indent">';

//kill a page
if(isset($_POST['del'],$_POST['del-id'])){
	if(isset($_POST['yes-i-am-sure'])){

		$loop = count($_POST['del-id']);
		for($i = 0; $i < $loop;$i++){
			$delID = intval($_POST['del-id'][$i]);
			if($delID <= 0){
				echo error("Invalid delete ID number");
			}else{
				$database->setQuery("SELECT #__navigation.id FROM #__navigation,#__dynamic WHERE #__dynamic.id = $delID AND #__navigation.id = #__dynamic.nav_id LIMIT 1");
				if($database->query()){
					if($data=$database->getAssoc()){
						$database->setQuery("DELETE FROM #__navigation WHERE id = " . $data['id']." LIMIT 1");
						if($database->query()){
							$database->setQuery(sprintf("DELETE FROM #__dynamic WHERE id = %d LIMIT 1",$delID));				
							if($database->query()){
								$database->setQuery(sprintf("DELETE FROM #__dynamic_content WHERE blog_id = %d",$delID));
								if($database->query())
									echo message("Deleted dynamic page with ID $delID");
								else
									echo error("Error deleting posts for page with ID $delID<br />".$database->getErrorMessage());
							}else{
								echo error("Error deleting page<br />".$database->getErrorMessage());
							}
						}else{
							echo error("Error deleting navigation item<br />".$database->getErrorMessage());
						}
					}else{
						if($database->getNumberRows() == 0){
							$database->setQuery(sprintf("DELETE FROM #__dynamic WHERE id = %d LIMIT 1",$delID));				
							if($database->query()){
								$database->setQuery(sprintf("DELETE FROM #__dynamic_content WHERE blog_id = %d",$delID));
								if($databae->query())
									echo message("Deleted page with ID $delID");
								else
									echo error("Problem deleting posts for page with ID $delID<br />".$database->getErrorMessage());
							}else{
								echo error("Error deleting page<br />".$database->getErrorMessage());
							}
						}else{
							echo error("Error retrieving navigation data for deletion<br />".$database->getErrorMessage());
						}
					}
				}else{
					echo error("Invalid delete ID number");
				}
			}
		}
	}else{
		echo error("Please tick the confirmation box if you really with to permanently delete a dynamic page");
	}
	echo '<br />';
// do we change a page contents?
}

if(isset($_GET['edit'])){
//are we saving changes?
	$edID = intval($_GET['edit']);
	if(isset($_GET['post'])){
		$pID = intval($_GET['post']);
		require_once($sscConfig_absPath . "/includes/sscEdit.php");
		if(isset($_POST['submit'])){
			if(isset($_POST['title'], $_POST['date'], $_POST['cont']) && $_POST['title'] != '' && $_POST['title'] != ''){
				if(!isset($_POST['uri']) || $_POST['uri'] == ''){
					 $_POST['uri'] = urlify(str_replace(" ","-",$_POST['title']));
				}else{
					$_POST['uri'] = urlify(str_replace(" ","-",$_POST['uri']));
				}
				if($_POST['date'] == ''){
					$_POST['date'] = "Now";
				}
				$database->setQuery(sprintf("SELECT id FROM #__users WHERE username = '%s' LIMIT 1", $database->escapeString($_SESSION['UserName'])));
				if($database->query() && $data = $database->getAssoc()){
					$uID = $data['id'];
					$res = 0;
					if($pID == 0){
						$database->setQuery(sprintf("INSERT INTO #__dynamic_content (blog_id, date, user_id, title, uri, content) VALUES (%d,'%s',%d,'%s','%s','%s')",$edID, date("Y-m-d H:s:i",strtotime($_POST['date'])), $uID, $database->escapeString($_POST['title']), $database->escapeString($_POST['uri']), $database->escapeString($_POST['cont'])));
						$res = $database->query();
						$pID = $database->getLastInsertID();
					}else{
						$database->setQuery(sprintf("UPDATE #__dynamic_content SET date = '%s', title = '%s', uri = '%s', content = '%s' WHERE id = %d LIMIT 1", date("Y-m-d H:s:i",strtotime($_POST['date'])), $database->escapeString($_POST['title']), $database->escapeString($_POST['uri']), $database->escapeString($_POST['cont']),$pID));
						$res = $database->query();
					}
					
					if($res)
						echo message("Post was saved");
					else
						echo error("There was a problem saving the post<br />".$database->getErrorMessage());
				}else
					echo error("Unable to retrieve posters display name");
			}else
				echo error("Not all fields were filled in!");
			
			echo '<br />';
			echo warn("We wanna save our post!"),'<br />';
		}
		
		if(isset($_POST['submit']) || isset($_POST['preview'])){
			$data['date'] = $database->stripString($_POST['date']);
			$data['title'] = $database->stripString($_POST['title']);
			$data['uri'] = $database->stripString($_POST['uri']);
			$data['content'] = $database->stripString($_POST['cont']);
			
			if(isset($_POST['cont']) && $_POST['cont'] != ''){
				echo "<h2>Page preview</h2><h2>",$_POST['title'],"</h2>",sscEdit::parseToHTML($_POST['cont']);
			}
			
		}else{
			$database->setQuery("SELECT date, user_id, title, uri, content FROM #__dynamic_content WHERE id = $pID ORDER BY date DESC");
			if($database->query() && $data = $database->getAssoc()){
				//$data['date'] = strtotime($data['date']);
			}else{
				$data['date'] = 'Now';
				$data['title'] = '';
				$data['content'] = '';
				$data['uri'] = '';
			}
		}
	
		//now display our form - ensure always correct id
		echo '<form action="',$sscConfig_adminURI,'/../',$pID,'" method="post"><fieldset><legend>';
			
		//minor semantics...
		if($pID == 0){
			echo 'Create new post';
		}else{
			echo 'Edit existing post';
		}
		
		//populate form stuffs
		echo '</legend><!--[if IE]><br /><![endif]--><div><label for="title">Post Title: </label><input type="text" maxlength="50" name="title" id="title" value="',$data['title'],'" /></div><br /><div><label for="date"><span class="popup" title="Accepts string \'inputs\', eg \'tomorrow\'">Date:</span> </label><input type="text" maxlength="30" name="date" id="date" value="',$data['date'],'" /></div><br /><div><label for="uri"><span class="popup" title="Friendly uri version of page title. eg my-post. Leave blank to guess from title">Access URI</span></label><input type="text" maxlength="100" name="uri" id="uri" value="'.$data['uri'].'" /></div><br /><div><label for="cont">Page Contents: </label>';
		sscEdit::placeEditor('cont',$data['content']);
		echo '</div><br /><div class="btn"><input type="submit" value="',($edID==0?'Create':'Save'),' Post" name="submit" id="submit" /><input type="submit" value="Preview Page" name="preview" id="preview" /></div>';
		echo '</fieldset></form><br class="clear" /><br />';
		
		echo '<a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to the post list';
	}elseif(isset($_POST['submit'])){

		if(isset($_POST['title'],$_POST['nav'], $_POST['nid'], $_POST['uri'])){
			if($_POST['title'] != '' && $_POST['nav'] != '' && $_POST['uri'] != '' && $_POST['nid'] != ''){
				//ensure leading '/'
				if(strpos($_POST['uri'],'/') !== 0)
					$_POST['uri'] = '/'.$_POST['uri'];
				
				$database->setQuery("SELECT id FROM #__modules WHERE filename = 'dynamic' LIMIT 1");
				$database->query();
				if($data = $database->getAssoc()){
					$mid = $data['id'];	//retrieve module id number
										
					//nav id
					$nid = intval($_POST['nid']);
					
					//first step - add or ensure the navigation bar up to date
					//if we are new...
					if($nid <= 0){
						$database->setQuery(sprintf("INSERT INTO #__navigation (module_id, name, uri, position, hidden) VALUES (%d, '%s', '%s', 50, 0)",$mid, $database->escapeString($_POST['nav']), $database->escapeString($_POST['uri'])));
						if($database->query()){
							$nid = $database->getLastInsertID();
						}else{
							$nid = -1;
						}
					}else{
						//updating old
						$database->setQuery(sprintf("UPDATE #__navigation SET name = '%s', uri = '%s' WHERE id = %d LIMIT 1", $database->escapeString($_POST['nav']), $database->escapeString($_POST['uri']), $nid));
						$database->query();
					}

					//now we update the content of the actual page
					
					if($nid > 0){
						//ensure there was no insert errors
						//new page?
						if($edID == 0){
							$database->setQuery(sprintf("INSERT INTO #__dynamic (nav_id, title) VALUES (%d, '%s')",$nid, $database->escapeString($_POST['title'])));
						}else{
							$database->setQuery(sprintf("UPDATE #__dynamic SET nav_id = '%d', title = '%s' WHERE id = %d LIMIT 1",$nid, $database->escapeString($_POST['title']),$edID));
						}
						if($database->query()){
							echo message('Page was updated successfully');
						}else{
							echo error('There was a problem updating page contents');
						}
						
					}else{
						echo error('Something bad happened.  There was an error updating navigation bar details');
					}
					
				}else{echo error("Unexpected error retrieving module ID");}
			}else{echo error("One or more required fields were not filled in");}
		}else{echo error("One or more required fields were not filled in");}
		echo '<br />';
		
		//not new so keep old data if applicable
		$data['title'] = $database->stripString($_POST['title']);
		$data['name'] = $database->stripString($_POST['nav']);
		$data['uri'] = $database->stripString($_POST['uri']);
		$data['nav_id'] = intval($_POST['nid']);
	}else{
		$database->setQuery("SELECT #__dynamic.id, nav_id, name, title, uri FROM #__dynamic, #__navigation WHERE #__dynamic.id = $edID AND #__navigation.id = nav_id LIMIT 1");
		$database->query();
		//echo $database->getErrorMessage();
		//page exist?
		if($database->getNumberRows()==1){
			$data = $database->getAssoc();
			$edID = $data['id'];
			//$data['name'] = ucwords(str_replace('-',' ',$data['name']));
		}else{
			//guess not so fill with blank data
			$data['title'] = '';
			$data['name'] = '';
			$data['nav_id'] = 0;
			$data['uri'] = '/';
			$edID = 0;
		}
	}
	
	if(!isset($_GET['post'])){
		
		if(isset($_POST['del-p'])){
			foreach($_POST['del-pid'] as $dpid){
				
				if($dpid > 0){
					$database->setQuery(sprintf("DELETE FROM #__dynamic_content WHERE id = %d LIMIT 1",$dpid));
					$database->query();
				}
			}
			echo message("Posts should have been deleted"),'<br />';
		}
		//now display our form - ensure always correct id
		echo '<form action="',$sscConfig_adminURI,'/../',$edID,'" method="post"><fieldset><legend>';
			
		//minor semantics...
		if($edID == 0){
			echo 'Create new dynamic page';
		}else{
			echo 'Edit existing dynamic page';
		}
		
		//populate form stuffs
		echo '</legend><!--[if IE]><br /><![endif]--><div><input type="hidden" name="nid" id="nid" value="',$data['nav_id'],'" /><label for="title">Page Title: </label><input type="text" maxlength="50" name="title" id="title" value="',$data['title'],'" /></div><br /><div><label for="nav">Navbar label: </label><input type="text" maxlength="30" name="nav" id="nav" value="',$data['name'],'" /></div><br /><div><label for="uri"><span class="popup" title="Address to access page from. eg /weather/now">Access URI</span></label><input type="text" maxlength="100" name="uri" id="uri" value="'.$data['uri'].'" /></div><br /><div class="btn"><input type="submit" value="',($edID==0?'Create':'Save'),' Page" name="submit" id="submit" /></div>';
		echo '</fieldset></form><br class="clear" /><br />';
		if($edID > 0){
			$database->setQuery(sprintf("SELECT #__dynamic_content.id, date, title, fullname, content FROM #__dynamic_content, #__users WHERE user_id = #__users.id AND blog_id = %d ORDER BY date DESC",$edID));
			if($database->query()){
				echo '</div><img class="panel-icon-img" src="',$sscConfig_adminImages,'/text.png" alt="" /><span class="title">Page Posts</span><hr class="admin" /><div class="indent"><form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Details of posts for the current dynamic page"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Post Title</th><th>Posted Date</th><th class="w-70">Contents</th></tr>';
				while($data = $database->getAssoc()){
					echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-pid[]" /></td><td><a href="',$sscConfig_adminURI,'/../',$edID,'/post/',$data['id'],'">',$data['title'],'</a></td><td>',date("d-m-Y h:s a",strtotime($data['date'])),'</td><td>',$data['content'],'</td></tr>';
				
					if(strlen($data['content']) > 150)
						$data['content'] = substr($data['content'],0,150).'...';
						
				}
				echo '</table><p><button type="submit" name="del-p" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
			}else{
				echo error("Problem retrieving page contents<br />".$database->getErrorMessage());
			}
				
			
			echo '<a title="Create a new post" class="small-ico" href="',$sscConfig_adminURI,'/post/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New post</span></a><br /><br />';
		}
			
		echo '<a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to the dynamic page list';
	}
	
}else{
//guess not.  display pages belonging to this module
$database->setQuery("SELECT #__dynamic.id, #__navigation.uri, #__dynamic.title, COUNT(blog_id) AS posts FROM #__dynamic, #__dynamic_content, #__navigation WHERE #__navigation.id = nav_id GROUP BY #__dynamic_content.blog_id ORDER BY uri ASC");
if($database->query()){
	if($database->getNumberRows() > 0){
		echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Details of pages controlled by this module"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Page Title</th><th><span class="popup" title="Path to access page">URI Text</span></th><th>Posts</th></tr>';
		while($data = $database->getAssoc()){
			echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="',$sscConfig_adminURI,'/edit/',$data['id'],'" title="Edit page contents">',$data['title'],'</a></td><td>',$data['uri'], '</td><td>',$data['posts'],'</td></tr>';
		}
		echo '</table><p><input type="checkbox" name="yes-i-am-sure" id="yes-i-am-sure" />Yes, I am absolutely sure I wish to permanently delete the selected pages above and acknowledge that any mistake is irreversible<br /><br /><button type="submit" name="del" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
	}else{echo message("There are no dynamic pages set up yet."),'<br />';}echo '<a title="Create a new dynamic page" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New dynamic page</span></a><br />';}else{echo error("Unexpected database error: ". $database->getErrorMessage());}
}
echo '</div>';
?>