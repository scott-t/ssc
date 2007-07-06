<?php
/**
 * Static text page
 *
 * Create/edit/delete pages that are 'static' - they don't change as time passes, eg contact, about
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');


echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/text.png" alt="" /><span class="title">Static Pages</span><hr class="admin" /><div class="indent">';

//kill a page
if(isset($_POST['del'],$_POST['del-id'])){
	$loop = count($_POST['del-id'])
	for($i = 0; $i < $loop;$i++){
		$delID = intval($_POST['del-id'][$i]);
		if($delID <= 0){
			echo error("Invalid delete ID number");
		}else{
			$database->setQuery("SELECT #__navigation.id FROM #__navigation,#__static WHERE #__static.id = $delID AND #__navigation.id = #__static.nav_id LIMIT 1");
			if($database->query()){
				if($data=$database->getAssoc()){
					$database->setQuery("DELETE FROM #__navigation WHERE id = " . $data['id']." LIMIT 1");
					if($database->query()){
						$database->setQuery(sprintf("DELETE FROM #__static WHERE id = %d LIMIT 1",$delID));				
						if($database->query()){
							echo message("Deleted page with ID $delID");
						}else{
							echo error("Error deleting page<br />".$database->getErrorMessage());
						}
					}else{
						echo error("Error deleting navigation item<br />".$database->getErrorMessage());
					}
				}else{
					if($database->getNumberRows() == 0){
						$database->setQuery(sprintf("DELETE FROM #__static WHERE id = %d LIMIT 1",$delID));				
						if($database->query()){
							echo message("Deleted page with ID $delID");
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
	echo '<br />';
// do we change a page contents?
}

if(isset($_GET['edit'])){
//are we saving changes?
	require_once($sscConfig_absPath . "/includes/sscEdit.php");
	$edID = intval($_GET['edit']);
	if(isset($_POST['submit'])){
		if(isset($_POST['title'],$_POST['nav'], $_POST['cont'])){
			if($_POST['title'] != '' && $_POST['nav'] != '' && $_POST['cont'] != ''){
				$database->setQuery("SELECT id FROM #__modules WHERE filename = 'static' LIMIT 1");
				$database->query();
				if($data = $database->getAssoc()){
					//hmm.  lot of error checking here ;)
					$mid = $data['id'];
					
					/* Yay - fun!  If new ($edID == 0), then set $nid -1 if hiding, else 0
					 * if not new, then get data
					 */
					
					if($edID == 0){
						if(isset($_POST['sub'])){
							$nid=-1;
						}else{$nid=0;}
						$sid = 0;
					}else{
						$database->setQuery("SELECT nav_id AS nid, id AS sid FROM #__static WHERE #__static.id = $edID LIMIT 1");
						$database->query();					
						if($data = $database->getAssoc()){
							$nid = $data['nid'];
							$sid = $data['sid'];
						}else{
							echo error("Page ID '$edID' does not exist!");
						}
					}

					if(isset($nid,$sid)){
						//ok.. lets get updating/adding
						if($nid == 0 || ($nid < 0 && !isset($_POST['sub']))){
							//either new or changing from sub to non-sub
							$database->setQuery(sprintf("INSERT INTO #__navigation (module_id, name, position, hidden) VALUES (%d, '%s', 50,0)",$mid, $database->escapeString($_POST['nav']))); 						
							$database->query();
							$database->setQuery(sprintf("SELECT id FROM #__navigation WHERE module_id = %d AND name = '%s' AND position = 50 LIMIT 1",$mid, $database->escapeString($_POST['nav'])));
							$database->query();
							$data = $database->getAssoc();
							$nid = $data['id'];
						}elseif($nid > 0){
							//exists.  remove if turning into subpage
							if(isset($_POST['sub'])){
								$database->setQuery("DELETE FROM #__navigation WHERE id = $nid LIMIT 1");
								$database->query();
								$nid=-1;
							}else{
								$database->setQuery(sprintf("UPDATE #__navigation SET name = '%s' WHERE id = %d",$database->escapeString($_POST['nav']),$nid));
								$database->query();
							}
						}else{$nid = -1;}
						
						if($sid == 0){
							$database->setQuery(sprintf("INSERT INTO #__static (nav_id, title, access, content) VALUES (%d, '%s', '%s', '%s')",$nid, $database->escapeString($_POST['title']),$database->escapeString($_POST['nav']),$database->escapeString($_POST['cont'])));
						}else{
							$database->setQuery(sprintf("UPDATE #__static SET nav_id = '%d', title = '%s', access = '%s', content = '%s' WHERE id = %d LIMIT 1",$nid, $database->escapeString($_POST['title']),$database->escapeString($_POST['nav']),$database->escapeString($_POST['cont']),$sid));
						}
						if($database->query()){
							echo message("Page contents successfully saved");
						}else{
							echo error("Error saving page<br />".$database->getErrorMessage());
						}
					}					
				}else{echo error("Unexpected error retrieving module ID");}
			}else{echo error("One or more required fields were not filled in");}
		}else{echo error("One or more required fields were not filled in");}
		echo '<br />';
	}elseif(isset($_POST['preview'],$_POST['cont'])){
		echo "<h2>Page preview</h2>",sscEdit::parseToHTML(stripslashes($_POST['cont']));
	}
	if(isset($_POST['preview']) || isset($_POST['submit'])){		
		$database->setQuery("SELECT id FROM #__static WHERE access = '".$database->escapeString($_POST['nav'])."' LIMIT 1");
		$database->query();
		if($data=$database->getAssoc()){
			$edID = $data['id'];
		}
		$data['title'] = stripslashes($_POST['title']);
		$data['name'] = stripslashes($_POST['nav']);
		$data['content'] = stripslashes($_POST['cont']);
		if(isset($_POST['sub'])){
			$data['sub'] = -1;
		}else{$data['sub']=0;}
	}else{
		$database->setQuery("SELECT id, nav_id AS sub, title, access AS name, content FROM #__static WHERE id = $edID LIMIT 1");
		$database->query();
		echo $database->getErrorMessage();
		//page exist?
		if($database->getNumberRows()==1){
			$data = $database->getAssoc();
			$edID = $data['id'];
			//$data['name'] = ucwords(str_replace('-',' ',$data['name']));
		}else{
			//guess not so fill with blank data
			$data['title'] = '';
			$data['name'] = '';
			$data['content'] = '';
			$data['sub'] = 0;
		}
	}
	//now display our form
	if($data['title']==''){
		echo '<form action="',$sscConfig_adminURI,'" method="post"><fieldset><legend>';
	}else{
		echo '<form action="',$sscConfig_adminURI,'/../',$edID,'" method="post"><fieldset><legend>';
	}
	
	//minor semantics...
	if($edID == 0){
		echo 'Create new static page';
	}else{
		echo 'Edit existing static page';
	}
	
	//populate form stuffs
	echo '</legend><!--[if IE]><br /><![endif]--><div><label for="title">Page Title: </label><input type="text" maxlength="50" name="title" id="title" value="',$data['title'],'" /></div><br /><div><label for="nav">Nav/URI Text: </label><input type="text" maxlength="30" name="nav" id="nav" value="',$data['name'],'" /></div><br /><div><label for="sub"><span class="popup" title="Prevents navigation item being created">Make subpage:</span></label><input type="checkbox" name="sub" id="sub" value="-1" ';
	if($data['sub'] < 0){echo 'checked="checked" ';}
	echo '/></div><br /><div><label for="cont">Page Contents: </label>';
	sscEdit::placeEditor('cont',$data['content']);
	echo '</div><br /><div class="btn"><input type="submit" value="',($edID==0?'Create':'Save'),' Page" name="submit" id="submit" /><input type="submit" value="Preview Page" name="preview" id="preview" /></div>';
	echo '</fieldset></form><br class="clear" /><br /><h2>Editor Help</h2>',sscEdit::placeHelp(3),'<br /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to static page list';
}else{
//guess not.  display pages belonging to this module
$database->setQuery("SELECT id, nav_id, access, title, content FROM #__static");
if($database->query()){
if($database->getNumberRows() > 0){
	echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Details of pages controlled by this module"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Page Title</th><th><span class="popup" title="If page type is a subpage, must be accessed as subpage (eg /URI/Sub-URI)">URI Text</span></th><th class="w-70">Contents</th></tr>';
	while($data = $database->getAssoc()){
		echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="',$sscConfig_adminURI,'/edit/',$data['id'],'" title="Edit page contents">',$data['title'],'</a></td><td>',$data['access'];
		if($data['nav_id'] <= 0){echo '<br />(subpage)';} echo'</td><td>';
		if(strlen($data['content']) > 150){
			echo substr($data['content'],0,150),'...';
		}else{
			echo $data['content'];
		}
		echo '</td></tr>';
	}
	echo '</table><p><button type="submit" name="del" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
}else{echo message("There are no static pages set up yet."),'<br />';}echo '<a title="Create a new static page" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New static page</span></a><br />';}else{echo error("Unexpected database error: ". $database->getErrorMessage());}
}
echo '</div>';
?>