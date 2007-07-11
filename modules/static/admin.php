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
	$loop = count($_POST['del-id']);
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

		if(isset($_POST['title'],$_POST['nav'], $_POST['cont'], $_POST['uri'])){
			if($_POST['title'] != '' && $_POST['nav'] != '' && $_POST['cont'] != '' && $_POST['uri'] != ''){
				$database->setQuery("SELECT id FROM #__modules WHERE filename = 'static' LIMIT 1");
				$database->query();
				if($data = $database->getAssoc()){
					$mid = $data['id'];	//retrieve module id number
					
					if($edID == 0){
						//was a "new" page that we are saving
						//assign a temporary nav id (used to indicate new page)
						if(isset($_POST['sub'])){
							//we are a sub page...
							$nid = -1;
						}else{
							$nid = 0;
						}
					}else{
						//re-saving old...
						$nid = $edID;
					}
					
					//first step - add or ensure the navigation bar up to date
					//if we are new...
					if($nid <= 0){
						$database->setQuery(sprintf("INSERT INTO #__navigation (module_id, name, uri, position, hidden) VALUES (%d, '%s', 50,%d)",$mid, $database->escapeString($_POST['nav'], $database->escapeString($_GET['uri']),abs($nid))));
						if($database->query()){
							$nid = $database->getLastInsertID();
						}else{
							$nid = -1;
						}
					}else{
						//updating old
						$database->setQuery(sprintf("UPDATE #__navigation SET name = '%s', uri = '%s', hidden = %d WHERE id = %d LIMIT 1", $database->escapeString($_POST['nav'], substr($database->escapeString($_GET['uri']),1),(isset($_POST['sub'])?1:0), $nid)));
						$database->query();
					}

					//now we update the content of the actual page
					
					if($nid > 0){
						//ensure there was no insert errors
						//new page?
						if($edID == 0){
							$database->setQuery(sprintf("INSERT INTO #__static (nav_id, title, content) VALUES (%d, '%s', '%s')",$nid, $database->escapeString($_POST['title']),$database->escapeString($_POST['cont'])));
						}else{
							$database->setQuery(sprintf("UPDATE #__static SET nav_id = '%d', title = '%s', content = '%s' WHERE id = %d LIMIT 1",$nid, $database->escapeString($_POST['title']),$database->escapeString($_POST['cont']),$edID));
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
		
	}elseif(isset($_POST['preview'],$_POST['cont'])){
		//preview... so generate it
		echo "<h2>Page preview</h2>",sscEdit::parseToHTML($database->stripString($_POST['cont']));
	}
	if(isset($_POST['preview']) || isset($_POST['submit'])){
		//not new so keep old data if applicable
		$data['title'] = $database->stripString($_POST['title']);
		$data['name'] = $database->stripString($_POST['nav']);
		$data['content'] = $database->stripString($_POST['cont']);
		$data['uri'] = $database->stripString($_POST['uri']);
		if(isset($_POST['sub'])){
			$data['sub'] = -1;
		}else{$data['sub']=0;}
	}else{
		$database->setQuery("SELECT #__static.id, nav_id AS sub, title, title AS name, uri, content FROM #__static, #__navigation WHERE #__static.id = $edID AND #__navigation.id = nav_id LIMIT 1");
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
			$data['content'] = '';
			$data['sub'] = 0;
			$data['uri'] = '/';
			$edID = 0;
		}
	}
	//now display our form - ensure always correct id
	echo '<form action="',$sscConfig_adminURI,'/../',$edID,'" method="post"><fieldset><legend>';
		
	//minor semantics...
	if($edID == 0){
		echo 'Create new static page';
	}else{
		echo 'Edit existing static page';
	}
	
	//populate form stuffs
	echo '</legend><!--[if IE]><br /><![endif]--><div><label for="title">Page Title: </label><input type="text" maxlength="50" name="title" id="title" value="',$data['title'],'" /></div><br /><div><label for="nav">Navbar label: </label><input type="text" maxlength="30" name="nav" id="nav" value="',$data['name'],'" /></div><br /><div><label for="sub"><span class="popup" title="Prevents navigation item being created">Make subpage:</span></label><input type="checkbox" name="sub" id="sub" value="-1" ';
	if($data['sub'] < 0){echo 'checked="checked" ';}
	echo '/></div><br /><div><label for="uri"><span class="popup" title="Address to access page from. eg weather\now">Access URI</span></label><input type="text" maxlength="100" name="uri" id="uri" value="'.$data['uri'].'" /></div><br /><div><label for="cont">Page Contents: </label>';
	sscEdit::placeEditor('cont',$data['content']);
	echo '</div><br /><div class="btn"><input type="submit" value="',($edID==0?'Create':'Save'),' Page" name="submit" id="submit" /><input type="submit" value="Preview Page" name="preview" id="preview" /></div>';
	echo '</fieldset></form><br class="clear" /><br /><h2>Editor Help</h2>',sscEdit::placeHelp(3),'<br /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to static page list';
}else{
//guess not.  display pages belonging to this module
$database->setQuery("SELECT #__static.id, nav_id, uri, title, content FROM #__static, #__navigation WHERE #__navigation.id = nav_id");
if($database->query()){
	if($database->getNumberRows() > 0){
		echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Details of pages controlled by this module"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Page Title</th><th><span class="popup" title="Path to access page">URI Text</span></th><th class="w-70">Contents</th></tr>';
		while($data = $database->getAssoc()){
			echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="',$sscConfig_adminURI,'/edit/',$data['id'],'" title="Edit page contents">',$data['title'],'</a></td><td>/',$data['uri'];
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