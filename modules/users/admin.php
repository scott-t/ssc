<?php
/**
 * User Administration
 *
 * Perform administration on user authorisations.  Add/edit/delete users and groups, assign group permissions.
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');



echo '<img class="panel-icon-img" src="'.$sscConfig_adminImages.'/user.png" alt="" /><span class="title">User Administration</span><hr class="admin" /><div class="indent">';

if(isset($_POST['delete'],$_POST['del-id'])){
	//delete the given user
	$len = count($_POST['del-id']);
	for($i = 0; $i<$len;$i++){
		$delID = intval($_POST['del-id'][$i]);
		//check if was a string...
		if ($delID <= 0){
			echo error('Invalid user ID number');
		}else{
		
			//check if not ourselves or a super user
			$database->setQuery('SELECT username, group_id FROM #__users WHERE id = '.$delID.' LIMIT 1');
			$database->query();
				
			if($database->getNumberRows() != 1){
				//in case specified user didn't exist
				echo error('Invalid user ID number');
			}else{
				$data = $database->getAssoc();
				//self check
				if($data['username'] == $_SESSION['UserName']){
					//was self
					echo error('Sorry.  You can\'t delete yourself!');
				}elseif($data['group_id'] == 1){
					//was superuser
					echo error('Unable to delete superuser!  Please reduce this users privileges and try again');
				}else{
					//proceed with delete...
					$database->setQuery('DELETE FROM #__users WHERE id = '.$delID.' LIMIT 1');
					if($database->query()){
						echo message('User '.$data['username'].' removed');
					}else{
						echo error('Unable to delete '.$data['username'].'<br />'.$database->getErrorMessage());
					}
				}//end self/super check
			}//end user id existed
		}//end valid _POST
	echo '<br />';		
	}
}//end _POST['delete']
elseif(isset($_POST['groupdel'], $_POST['del-group-id'])){
	//delete a permission group instead...
	$len = count($_POST['del-group-id']);
	for($i = 0; $i<$len;$i++){
		$delID = intval($_POST['del-group-id'][$i]);
		
		//attempting supergroup delete?
		if($delID==1){
			//can't delete ID 1 (Super User)
			echo error('Group with ID 1 cannot be deleted');
		}else{
			//not completely restricted...
			$database->setQuery('SELECT id FROM #__users WHERE group_id = ',$delID,' LIMIT 1');
			$database->query();
			//group still in use?
			if($database->getNumberRows() > 0){
				echo error('Unable to remove permission group - Users are still in it!');
			}else{
				//we have an empty non-superuser group.  kill it
				
				$database->setQuery('DELETE FROM #__permissions WHERE group_id = '.$delID);
				if($database->query()){
					//deleted from permission table.  now remove group
					$database->setQuery('DELETE FROM #__groups WHERE id = '.$delID.' LIMIT 1');
					if($database->query()){
						echo message('Group was successfully removed');
					}else{
						echo error('Problem removing from group table<br />'.$database->getErrorMessage());
					}
				}else{
					echo error('Problem removing permissions for this group<br />'.$database->getErrorMessage());
				}
			}//end in use
		}//end supergroup check
	echo '<br />';
	}
}//end delete


//edit existing user
if(isset($_GET['edit'])){	
	//are we saving changes?
	if(isset($_POST['submit'])){
		//all required fields set?
		if(isset($_POST['pass'],$_POST['pass2'],$_POST['usr'],$_POST['fname'],$_POST['uid'],$_POST['gid'])){
			//password confirmed correctly?
			if($_POST['pass'] === $_POST['pass2']){
				$gid = intval($_POST['gid']);
				$uid = intval($_POST['uid']);
				
				if($gid <= 0){
					echo error('Invalid form parameters');
				}else{
					//check if the last super admin been removed && old user
					if($gid != 1 && $uid > 0){
						$database->setQuery("SELECT id FROM #__users WHERE group_id = '1' LIMIT 2");
						$database->query();
						if($database->getNumberRows() <= 1){
							$database->setQuery("SELECT id FROM #__users WHERE id = '$uid' LIMIT 1");
							$database->query();
							if($database->getNumberRows() == 1){
								//user is a super and bout to be changed...
								echo warn('There must be at least one user belonging in the original permission group.  Group was not changed'),'<br />';
								//keep same gid
								$gid = 1;
							}
						}//end only one superuser
					}//end changing out of supergroup
					
					
					//either add/update user
					if($uid == 0){
						//inserting new...
						$database->setQuery(sprintf("INSERT INTO #__users (username, fullname, password, email,group_id,last_access) VALUES ('%s','%s','%s','%s','%d','%s')",$database->escapeString($_POST['usr']),$database->escapeString($_POST['fname']),$database->encodeString($database->escapeString($_POST['usr'].$_POST['pass'])),$database->escapeString($_POST['email']),$gid,date('Y-m-d H:i:s')));
					}else{
						//edit existing - update password?
						if(strlen($_POST['pass'])){
							//yes
							$database->setQuery(sprintf("UPDATE #__users SET username = '%s', fullname = '%s', password = '%s', email = '%s', group_id = '%d' WHERE id = '%d' LIMIT 1",$database->escapeString($_POST['usr']),$database->escapeString($_POST['fname']),$database->encodeString($database->escapeString($_POST['usr'].$_POST['pass'])),$database->escapeString($_POST['email']),$gid,$uid));
						}else{
							$database->setQuery(sprintf("UPDATE #__users SET username = '%s', fullname = '%s', email= '%s', group_id = '%d' WHERE id = '%d' LIMIT 1",$database->escapeString($_POST['usr']),$database->escapeString($_POST['fname']),$database->escapeString($_POST['email']),$gid,$uid));
						}
					}//end sql selection
					
					if($database->query()){
						echo message('User details have been updated');
						$_GET['edit'] = $_POST['usr'];
					}else{ 
						echo error('An unexpected error occured.<br />' . $database->getErrorMessage());
					}
				
				}//end valid gid
			}//end valid password		
			else{
				echo error('Specified passwords do not match.  Nothing updated');
			}
		}else{
			echo error('Not all required fields were entered');
		}
		echo '<br />';
	}
	
	$database->setQuery('SELECT #__users.id AS uid, group_id AS gid, fullname, username, email FROM #__users WHERE username = "'.$database->escapeString($_GET['edit']).'" LIMIT 1');
	$database->query();
	//user exist?
	if($database->getNumberRows()==1){
		$data = $database->getAssoc();
	}else{
		//guess not so fill with blank data
		$data['uid'] = '';
		$data['gid'] = '';
		$data['fullname'] = '';
		$data['username'] = '';
		$data['email']='';
	}
	
	//now display our form
	if($data['username']==''){
		echo '<form action="',$sscConfig_adminURI,'" method="post"><fieldset><legend>';
	}else{
		echo '<form action="',$sscConfig_adminURI,'/../',$data['username'],'" method="post"><fieldset><legend>';
	}
	
	//minor semantics...
	if($data['uid'] == ''){
		echo 'Create new administrator';
	}else{
		echo 'Edit existing administrator';
	}
	
	//populate form stuffs
	echo '</legend><input type="hidden" value="',$data['uid'],'" name="uid" /><!--[if IE]><br /><![endif]--><div><label for="usr">Username: </label><input type="text" maxlength="10" name="usr" id="usr" value="',$data['username'],'" /></div><br /><div><label for="fname">Full Name: </label><input type="text" maxlength="30" name="fname" id="fname" value="',$data['fullname'],'" /></div><br /><div><label for="pass">Set Password:</label><input type="password" name="pass" id="pass" /></div><br /><div><label for="pass2">Confirm Password:</label><input type="password" name="pass2" id="pass2" /></div><br /><div><label for="email">Email:</label><input type="text" name="email" id="email" value="',$data['email'],'" /></div><br /><div><label for="gid">Group: </label><select name="gid" id="gid">';
	//options for group...
	$database->setQuery('SELECT id, name, description FROM #__groups ORDER BY name ASC');
	$database->query();
	while($data2 = $database->getAssoc()){
		//display possible groups
		echo '<option title="',$data2['description'],'" value="',$data2['id'],'"';
		//this the one we belong to?
		if($data['gid'] == $data2['id']){echo ' selected="selected" ';}
		echo '>',$data2['name'],'</option>';
	}
	
	echo '</select></div><br /><div class="btn"><input type="submit" value="',($data['uid']==''?'Create':'Save'),' User" name="submit" id="submit" /></div>';
	echo '</fieldset></form><br class="clear" /><br /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="'.$sscConfig_adminImages.'/back.png" alt="" />Return</a> to users table';
	
}elseif(isset($_GET['group'])){
	//edit group by gid	
	//form submitted?
	if(isset($_POST['submit'])){
		//required fields?
		if(isset($_POST['name'],$_POST['desc'],$_POST['gid'])){
			//check if permissions set for group
			if(count($_POST)==4 && $_POST['gid'] != '1'){
				//NO PERMISSIONS SET!!!!
				echo warn('You have not selected any permissions for this group!'),'<br />';
			}
			$_POST['name']=$database->escapeString($_POST['name']);
			$_POST['desc']=$database->escapeString($_POST['desc']);

			//now - do the saving...
			//insert or update?
			if($_POST['gid']=='0'){
				//new group
				$database->setQuery(sprintf("INSERT INTO #__groups (name,description) VALUES ('%s','%s')",$_POST['name'],$_POST['desc']));
			}else{
				//update existing
				$database->setQuery(sprintf("UPDATE #__groups SET name = '%s', description = '%s' WHERE id = '%d' LIMIT 1",$_POST['name'],$_POST['desc'],$_POST['gid']));		
			}
			
			if($database->query()){
				//group details updated.  now set permissions
				if($_POST['gid'] != '1'){
					//don't change hardcoded permissions

					$keys = array_keys($_POST);

					/*
					 *  ok.  now we loop thru the $_POST array
					 *  only use numeric relating to module id keys
					 *
					 *  get a list of currently authorized sections
					 *  compare with form results
					 *  delete if not in form, add if only in form, skip if both present
					 *
					 *
					 *  fingers crossed...
					 */ 
												
					$database->setQuery('SELECT MAX(id)+1 AS module_id FROM #__modules');
					$database->query();
					$data = $database->getAssoc();
					$max = $data['module_id'];
					
					$database->setQuery(sprintf("SELECT #__permissions.id AS perm_id, module_id FROM #__permissions WHERE group_id = '%d' ORDER BY module_id ASC",$_POST['gid']));
					$perm_res = $database->query();
					
					if(!$data = $database->getAssoc()){
						//ensure everything is added - no permissions currently existing
						$data['module_id'] = $max;
					}
					
					// how many times to loop...
					$len = count($_POST) - 1;
					
					//use 3 since 3 elements before permission stuff
					for($i = 3; $i < $len; $i++){
						while(true){
							if($data['module_id'] < $keys[$i]){
								//the database came first.  database now out of date.  delete permisisons
								$database->setQuery('DELETE FROM #__permissions WHERE id = \''.$data['perm_id'].'\' LIMIT 1');
								$database->query();
								//and advance query by one to try and catch up
								if(!$data = $database->getAssoc($perm_res)){
									//in case no more database contents
									$data['module_id'] = $max;
								}
							}elseif($data['module_id'] > $keys[$i]){
								//the form came first.  need to add new permission
								$database->setQuery(sprintf("INSERT INTO #__permissions (module_id, group_id) VALUES ('%d','%d')",$keys[$i],$_POST['gid']));
								$database->query();
								//and advance the form
								break;
							}else{			
								//advance both
								if(!$data = $database->getAssoc($perm_res)){
									//in database runs out...
									$data['module_id'] = $max;
								}
								break;
							}
						}//end while loop
					}//end permission loop
					//do a cleanup - if permissions set in form ran out before db (ie, group permissions reduced
					while($data['module_id'] != $max){
						//delete extras
						$database->setQuery('DELETE FROM #__permissions WHERE id = \''.$data['perm_id'].'\' LIMIT 1');
						$database->query();
						if(!$data = $database->getAssoc($perm_res)){
							//database ran out
							$data['module_id'] = $max;
						}
					}

				}else{
					$perm_res = true;
				}
				if($perm_res)
				{
					$database->freeResult($perm_res);
					echo message('Group settings were successfully updated');
					
					//retrieve new group id for later use
					$database->setQuery('SELECT name, description,id FROM #__groups WHERE name = \''.$_POST['name'].'\' LIMIT 1');
					$database->query();
					$data = $database->getAssoc();
					$_GET['group'] = $data['id'];
				}else{
					echo error('Unexpected error while setting permission levels<br />'.$database->getErrorMessage());
				}
			}else{
				echo error('Unexpected error updating group details<br />'.$database->getErrorMessage());
			}//end sql adding stuffs
			
		}else{
			//lack of fields filled in
			echo error('Please fill in group name and description!');
		}	
		echo '<br />';
	}

	//now do the form
	if($_GET['group'] == 0){
		if(!isset($_POST['submit'])){
			//new
			$data['name'] = 'New Group';
			$data['description'] = '';
		}
	}elseif(!isset($data['id'])){
		//retrieve existing if not predone after a save
		$database->setQuery(sprintf("SELECT name,description FROM #__groups WHERE id = '%d' LIMIT 1",$_GET['group']));
		$database->query();
		$data = $database->getAssoc();
	}
	
	//form
	echo '<form action="'.$sscConfig_adminURI.'/../'.$_GET['group'].'" method="post"><fieldset><legend>Set group permissions for '.$data['name'].'</legend><input type="hidden" name="gid" value="'.$_GET['group'].'" /><div><label for="name">Group Name: </label><input type="text" id="name" name="name" value="'.$data['name'].'" /></div><br /><div><label for="desc">Description: </label><textarea cols="40" rows="3" name="desc" id="desc">'.$data['description'].'</textarea></div><br />';
	if($_GET['group'] == '1'){
		//can't set permissions for supergroup
		echo 'Group with ID 1 will always have all permissions set<br />';
	}else{
		//build checkbox array
		echo '<fieldset class="box"><legend>Able to change:</legend>';
		$database->setQuery(sprintf("SELECT #__modules.id, #__permissions.id AS perm_id, #__modules.admin_text FROM #__modules LEFT JOIN #__permissions ON #__modules.id = #__permissions.module_id AND #__permissions.group_id = '%d'",$_GET['group']));
		$database->query();
		while($data = $database->getAssoc()){
			echo '<input type="checkbox" ';
			if($data['perm_id'] != NULL){
				echo 'checked="checked" ';
			}
			echo 'name="',$data['id'],'" id="module',$data['id'],'" value="1" /><label for="module',$data['id'],'">',$data['admin_text'],'</label><br />';
		}
		echo '</fieldset>';
	}//end permission array
	echo '<div class="btn"><input type="submit" name="submit" value="Update permissions" /></div></fieldset></form><br class="clear" /><br /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to group summary table';
		
}else{
	//display tables
	echo'<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Users with admin access" width="100%"><tr><th>ID</th><th>Full Name</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Username</th><th>Group</th><th>Last Access</th></tr>';
	$database->setQuery('SELECT #__users.id, name, description, fullname, username, last_access FROM #__users LEFT JOIN #__groups ON #__users.group_id = #__groups.id ORDER BY id ASC');
	$database->query();
	while($data = $database->getAssoc()){
		echo '<tr><td>',$data['id'],'</td><td>',$data['fullname'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="',$sscConfig_adminURI,'/edit/',$data['username'],'" title="Change user settings">',$data['username'],'</a></td><td><span class="popup" title="',$data['description'],'">',$data['name'],'</span></td><td>',date('j M y, g:i:sa', strtotime($data['last_access'])-1800),'</td></tr>';
	}
	echo '</table><p><button type="submit" name="delete" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form><a title="Create new administrator" class="small-ico" href="',$sscConfig_adminURI,'/edit/new"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New Admin</span></a><br /></div>';
		
		
	//now for group permissions
	echo '<br /><img class="panel-icon-img" src="',$sscConfig_adminImages,'/user.png" alt="" /><span class="title">Admin Groups</span><hr class="admin" /><div class="indent"><form action="',$sscConfig_adminURI,'" method="post"><table summary="Permission groups" class="tab-admin"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Group Name</th><th>Description</th></tr>';
	$database->setQuery('SELECT id, name, description FROM #__groups ORDER BY id ASC');
	$database->query();
	while($data = $database->getAssoc()){
		echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-group-id[]" /></td><td><a href="',$sscConfig_adminURI,'/group/',$data['id'],'" title="Change permissions for group">',$data['name'],'</a></td><td>',$data['description'],'</td></tr>';
	}
		
	echo '</table><p><button type="submit" name="groupdel" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form><a title="Create new permission group" class="small-ico" href="',$sscConfig_adminURI,'/group/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New Permission Group</span></a><br />';
}
echo '</div>';
?>