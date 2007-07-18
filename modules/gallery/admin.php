<?php
/**
 * Photo Gallery admin page
 *
 * Add/Delete photo galleries, 
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');


define('SSC_GALLERY_THUMB_WIDTH',150);//150px wide
define('SSC_GALLERY_THUMB_SIZE',10240);//10kb big

define('SSC_GALLERY_MEDIUM_WIDTH', 350);

define('SSC_GALLERY_LARGE_WIDTH',1024);//1024px wide
define('SSC_GALLERY_LARGE_SIZE',153600);//150kb?
$sscGallery_path = $sscConfig_absPath . '/modules/gallery/photos/';//with trailing
global $sscConfig_imgWatermark;
/**
 * Recurses through a directory and inserts each found jpg and included thumb (creating as needed)
 * @param string Directory containing images
 * @param int Gallery id to insert into
 * @param boolean Whether or not to apply watermark
 */
function insertDirectory($dir, $gid, $watermark = false){
	global $database, $sscConfig_absPath;
	$sscGallery_path = $sscConfig_absPath .'/modules/gallery/photos/' . $gid;
	if(!file_exists($sscGallery_path))
		mkdir($sscGallery_path);
		
	$sscGallery_path .= '/';
	
	if(is_dir($dir) && $handle = opendir($dir)){
		$prev_file = '';
		$prev_thumb = true;
		$tmp = '';
		$ext = '';
		$pid = -1;
		while(($file = readdir($handle)) !== false){
			if($file == '..' || $file == '.'){continue;}	
			$file=$dir.'/'.$file;
			//now get down to parsing
			if(is_dir($file)){insertDirectory($file);}
			if(strpos($file,'_thumb') !== false){
				if(str_replace('_thumb','',$file) == $prev_file){
					//appropriate thumbnail
					$prev_thumb = true;
					if(sscImage::resize($file,$sscGallery_path . $pid . '_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
						break;
					}else{
						//couldn't use included - try gen our own
						if(sscImage::resize($prev_file,$sscGallery_path . $pid.'_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
							//success
						}else{
							echo error('Problem saving thumbnail.  ' . $prev_file . ' was not saved');
							$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
							$database->query();
							unlink($sscGallery_path . $pid . '.'. $ext);
							unlink($sscGallery_path . $pid . '_thumb.' . $ext);
						}
					}
				}
				//discard if thumb for unknown image
			}else{
				//prev imge had no thumb - create it
				if(!$prev_thumb){
					if(sscImage::resize($prev_file,$sscGallery_path . $pid.'_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
					
					}else{
						//problems
						echo error('Problem saving thumbnail.  ' . $prev_file . ' was not saved');
						$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
						$database->query();
							unlink($sscGallery_path . $pid . '.'. $ext);
							unlink($sscGallery_path . $pid . '_thumb.' . $ext);
					}
				}
				$ext = strtolower(sscImage::getExtension($file));
				if($ext != 'jpg' && $ext != 'jpeg'){echo error("Unknown file extension type $ext.  Ignoring...");$ext='jpg';continue;}
				//not a thumb - primary image
				$database->setQuery("INSERT INTO #__gallery_content (gallery_id, owner, caption) VALUES ($gid , '','')");
				$database->query();
				$pid = $database->getLastInsertID();
				//size if needed
				if(sscImage::resize($file,$sscGallery_path . $pid,$ext,SSC_GALLERY_LARGE_WIDTH,$watermark)){
					$prev_thumb = false;
				}else{
					//problems
					$prev_thumb = true;
					echo error('Problem with saving photo ' . $file);
					$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
					$database->query();
					unlink($sscGallery_path . $pid . '.'. $ext);
				}
			}
			
			$prev_file = $file;
		}
		
		if(!$prev_thumb){
			if(sscImage::resize($prev_file,$sscGallery_path . $pid.'_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
			
			}else{
				//problems
				echo error('Problem saving thumbnail.  ' . $prev_file . ' was not saved');
				$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
				$database->query();
					unlink($sscGallery_path . $pid . '.'. $ext);
					unlink($sscGallery_path . $pid . '_thumb.' . $ext);
			}
		}
		closedir($handle);
		
	}
	return;
}

echo '<img class="panel-icon-img" src="', $sscConfig_adminImages, '/gallery.png" alt="" /><span class="title">Photo Galleries</span><hr class="admin" /><div class="indent">';

if(isset($_GET['edit'])){
	require_once($sscConfig_absPath . "/includes/sscEdit.php");
	$gid = intval($_GET['edit']);
	
	if(isset($_POST['submit'], $_POST['title'], $_POST['desc'])){
		require_once($sscConfig_absPath . '/includes/sscImage.php');
		$error = false;
		if($gid > 0){
			//update
			$database->setQuery(sprintf("UPDATE #__gallery SET name = '%s', description = '%s' WHERE id = %d",$database->escapeString($_POST['title']),$database->escapeString($_POST['desc']),$gid));
		}else{
			//insert
			$database->setQuery(sprintf("INSERT INTO #__gallery (name,description) VALUES ('%s','%s')",$database->escapeString($_POST['title']),$database->escapeString($_POST['desc'])));
		}
		if($database->query()){
			if($gid <= 0)
			{
				$gid = $database->getLastInsertID();
				$sscConfig_adminURI .= "/../$gid";
			}
		}else{
			//couldn't save
			echo error('Error updating gallery details');
			$error = true;
		}
		
		//since we only add if gallery actually exists
		if(intval($_GET['edit']) > 0 && !$error){
			$sscGallery_path .= $gid;
			if(!file_exists($sscGallery_path))
				mkdir($sscGallery_path);
				
			$sscGallery_path .= '/';
			if(isset($_POST['own'])){
				//perform content update
				$keys_owner = array_keys($_POST['own']);
				$keys_caption = array_keys($_POST['cap']);
				$keys_medium = array_keys($_POST['gen-med']);
				if(isset($_POST['del']))
					$keys_delete = array_keys($_POST['del']);
				else
					$keys_delete[0] = -1;
				
				$size = sizeof($keys_owner);
				$del_size = sizeof($keys_delete);
				$med_size = sizeof($keys_medium);
				$pid = 0;
				for($i = 0, $j = 0, $k = 0; $i < $size; $i++){
					$pid = intval($keys_owner[$i]);
					if($j < $del_size && $keys_owner[$i] == $keys_delete[$j]){
						++$j;
						//delete the row
						$database->setQuery('DELETE FROM #__gallery_content WHERE id = '.$pid.' LIMIT 1');
						if($database->query()){
							//delete good... remove .jpg
							$photo = $sscConfig_absPath . '/modules/gallery/photos/'.$gid.'/'.$pid.'.jpg';
							if(file_exists($photo)){unlink($photo);}
							$photo = $sscConfig_absPath . '/modules/gallery/photos/'.$gid.'/'.$pid.'_thumb.jpg';
							if(file_exists($photo)){unlink($photo);}
							$photo = $sscConfig_absPath . '/modules/gallery/photos/'.$gid.'/'.$pid.'_med.jpg';
							if(file_exists($photo)){unlink($photo);}
						}else{
							echo error('Unable to delete selected image with id '.$pid.'<br />'.$database->getErrorMessage());
						}
					}else{
						//update only
						$database->setQuery(sprintf("UPDATE #__gallery_content SET owner = '%s', caption = '%s' WHERE id = %d LIMIT 1",$database->escapeString($_POST['own'][$pid]),$database->escapeString($_POST['cap'][$pid]),$pid));
						$database->query();
					}
					
					if($k < $med_size && $keys_owner[$i] == $keys_medium[$k]){
						++$k;
						//need to generate a medium level zoom
						sscImage::resize($sscConfig_absPath . '/modules/gallery/photos/'.$gid.'/'.$pid.'.jpg',$sscConfig_absPath . '/modules/gallery/photos/'.$gid.'/'.$pid.'_med','jpg',SSC_GALLERY_MEDIUM_WIDTH,$sscConfig_imgWatermark);
						$database->setQuery(sprintf("UPDATE #__gallery_content SET med = 1 WHERE id = %d LIMIT 1", $pid));
						$database->query();
						
					}
				}
			}
			//and finally add if required
			switch ($_FILES['up-single']['error']){
				case UPLOAD_ERR_OK:		
					//handle upload for single file

					$ext = strtolower(sscImage::getExtension($_FILES['up-single']['name']));
					if($ext != 'jpg' && $ext != 'jpeg'){echo error("Unknown file extension type $ext.  Ignoring...");break;}					
					//insert it to db
					$database->setQuery("INSERT INTO #__gallery_content (gallery_id,owner,caption) VALUES ($gid,'','')");
					$database->query();
					$pid = $database->getLastInsertID();
					
					//size if needed
					if(sscImage::resize($_FILES['up-single']['tmp_name'],$sscGallery_path . $pid,$ext,SSC_GALLERY_LARGE_WIDTH,$sscConfig_imgWatermark)){
						
						//now do thumbnail
						switch ($_FILES['up-single-th']['error']){
							case UPLOAD_ERR_OK:
								if(sscImage::getExtension($_FILES['up-single-th']['name']) == $ext){
									if(sscImage::resize($_FILES['up-single-th']['tmp_name'],$sscGallery_path . $pid . '_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
										break;
									}
								}else{
									echo warn('Either no thumbnail specified or uploaded thumbnail too big.  Automatically creating new one');
								}
								
							default:
								if(sscImage::resize($_FILES['up-single']['tmp_name'],$sscGallery_path . $pid . '_thumb', $ext,SSC_GALLERY_THUMB_WIDTH,false)){
									//yay
								}else{
									echo error('Problem saving thumbnail.  ' . $_FILES['up-single']['name'] . ' was not saved');
									$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
									$database->query();
									unlink($sscGallery_path . $pid . '.' . $ext);
									unlink($sscGallery_path . $pid . '_thumb.' . $ext);
								}
								break;
						}
						
					}else{
						echo error('Problem with saving photo ' . $_FILES['up-single']['name']);
						$database->setQuery("DELETE FROM #__gallery_content WHERE id = $pid");
						$database->query();
						unlink($sscGallery_path . $pid . '.'. $ext);
					}
					
					
					break;
				case UPLOAD_ERR_NO_FILE:
					//no file specified - ignore
					break;
				default:
					echo error('Problem with uploading primary photo.  Check file size is not larger than specified size and try again. If errors persist contact the developer');
					break;
			}
			
			
			//now attempt for zipped files
			switch($_FILES['up-zip']['error']){
				case UPLOAD_ERR_OK:
					//upload worked - attemp unzip
					require_once($sscConfig_absPath . '/includes/pclzip.lib.php');
					require_once($sscConfig_absPath . '/includes/dir.lib.php');
					$archive = new PclZip($_FILES['up-zip']['tmp_name']);
					
					$path = $sscConfig_absPath . '/tmp/' . $gid;
					if(!file_exists($path))
						mkdir($path);

					if($archive->extract(PCLZIP_OPT_PATH,$sscConfig_absPath . '/tmp/'.$gid)){
						//extract good
						insertDirectory($path,$gid,$sscConfig_imgWatermark);
					}else{
						//extract bad
						echo error("There was a problem extracting the zip file!");
					}
					rmdirRecursive($sscConfig_absPath . '/tmp/'.$gid);
					
					break;
				case UPLOAD_ERR_NO_FILE:
					//no file upped.  do nothing
					break;
				default:
					echo error('Problem uploading zipped photos.  Check file size and try again.  If problems persist, contact the developer');
					break;
			}
		}
	}
	
	if($gid <= 0){
		$gid = 0;
		$data['name'] = '';
		$data['description'] = '';
		//new
	}else{
		//existing
		$database->setQuery("SELECT name, description FROM #__gallery WHERE id = $gid LIMIT 1");
		$database->query();
		$data = $database->getAssoc();
	}
	echo '<form action="',$sscConfig_adminURI,'" enctype="multipart/form-data" method="post"><fieldset><legend>Gallery Details</legend><!--[if IE]><br /><![endif]--><div><label for="title">Gallery Title: </label><input type="text" maxlength="50" name="title" id="title" value="',$data['name'],'" /></div><div><label for="desc">Brief description: </label>';
	sscEdit::placeEditor('desc',$data['description']);
	echo '</div></fieldset><fieldset><legend>Gallery Contents</legend><!--[if IE]><br /><![endif]-->';
	if($gid > 0){
		//display gallery contents
		$database->setQuery("SELECT id, med, owner, caption FROM #__gallery_content WHERE gallery_id = $gid ORDER BY id ASC");
		if($database->query()){
			while($data = $database->getAssoc()){
				$iid = $data['id'];
				echo '<div class="clear"><img class="float" src="',$sscConfig_webPath,'/modules/gallery/photos/',$gid,'/',$iid,'_thumb.jpg" alt="" /><div class="noclear"><div><label for="owner-',$iid,'">Photographer: </label><input type="text" maxlength="50" value="',$data['owner'],'" name="own[',$iid,']" id="owner-',$iid,'" /></div><div><label for="cap-',$iid,'">Caption: </label><input type="text" maxlength="50" value="',$data['caption'],'" name="cap[',$iid,']" id="cap-',$iid,'" /></div><div><label>Delete: </label><input type="checkbox" name="del[',$iid,']" /></div><br /><div><label><span class="popup" title="Indicates a medium-zoom level exists for use elsewhere">Medium level?</span></label><input type="checkbox" name="gen-med[',$iid,']" ',($data['med']==1?'checked="checked" disabled="disabled" ':''),'id="gen-med-',$iid,'" /></div></div>';
			}
			$maxsize = ini_get('upload_max_filesize') * 1048576;
			echo '</fieldset><fieldset><legend>Upload New Photos (jpg\'s only)</legend><!--[if IE]><br /><![endif]--><div>Images will be resized/recompressed if primary images are larger than 1024px wide or 150kb</div><div><label for="up-single">Upload single:</label><input type="hidden" name="MAX_FILE_SIZE" value="',$maxsize,'" /><input type="file" name="up-single" id="up-single" />',ini_get('upload_max_filesize'),' max</div>'
				,'<div><label for="up-single-th"><span class="popup" title="Thumbnails will be automatically created if not specified">Thumbnail:</span></label><input type="hidden" name="MAX_FILE_SIZE" value="10240" /><input type="file" name="up-single-th" id="up-single-th" />10kB max</div>'
				,'<div class="btn">-- OR --</div>'
				,'<div><label for="up-zip"><span class="popup" title="Upload multiple images in a single zip file.  Thumbnails will be automatically created if needed">Upload zip:</span></label><input type="hidden" name="MAX_FILE_SIZE" value="',$maxsize,'" /><input type="file" name="up-zip" id="up-zip" />',ini_get('upload_max_filesize'),' max</div>';
		}else{echo error('Problems retrieving gallery contents');}
	}else{echo message('Please save the gallery to add items');}	
	echo '</fieldset><div class="clear btn"><input type="submit" name="submit" id="submit" value="Save changes" /></div></form><br class="clear" /><a class="small-ico" href="',$sscConfig_adminURI,'/../../"><img src="',$sscConfig_adminImages,'/back.png" alt="" />Return</a> to gallery list';
}else{
	//kill a gallery
	if(isset($_POST['del'],$_POST['del-id'])){
		$loop = count($_POST['del-id']);
		for($i = 0; $i < $loop; $i++){
			$gid = intval($_POST['del-id'][$i]);
			if($gid > 0){
				$database->setQuery("DELETE FROM #__gallery_content WHERE gallery_id = $gid");
				$database->query();
				$database->setQuery("DELETE FROM #__gallery WHERE id = $gid LIMIT 1");
				$database->query();
				require_once($sscConfig_absPath .'/includes/dir.lib.php');
				$dirtodel = $sscConfig_absPath . '/modules/gallery/photos/'.$gid;
				rmdirRecursive($dirtodel);
				if(file_exists($dirtodel)){
					echo error('Unable to delete files from gallery directory.  Some files may still remain');
				}
			}
		}

	}
	//show galleries
	$database->setQuery("SELECT #__gallery.id, name, description, COUNT(gallery_id) AS items FROM #__gallery LEFT JOIN #__gallery_content ON gallery_id = #__gallery.id GROUP BY gallery_id ORDER BY id ASC");
	if($database->query()){
		if($database->getNumberRows() > 0){
			echo '<form action="',$sscConfig_adminURI,'" method="post"><table class="tab-admin" summary="Photo galleries currently set up"><tr><th>ID</th><th>&nbsp;<img src="',$sscConfig_adminImages,'/delete.png" alt="Delete" /></th><th>Gallery Name</th><th>Gallery Description</th></tr>';
			while($data = $database->getAssoc()){
				echo '<tr><td>',$data['id'],'</td><td><input type="checkbox" value="',$data['id'],'" name="del-id[]" /></td><td><a href="', $sscConfig_adminURI,'/edit/',$data['id'],'">',$data['name'],'</a><br />(',$data['items'],' item', ($data['items'] == 1 ? '' : 's'),')</td><td>',$data['description'],'</td></tr>';
			}
			echo '</table><p><button type="submit" name="del" value="delete">Delete selected&nbsp;<img src="',$sscConfig_adminImages, '/delete.png" alt="" class="small-ico" /></button></p></form>';
		}else{
			echo message("There are currently no galleries set up"),'<br />';
		}
	}else{
		echo error("Database connectivity error<br />" . $database->getErrorMessage()),'<br />';
	}
	echo '<a title="Create a new photo gallery" class="small-ico" href="',$sscConfig_adminURI,'/edit/0"><img src="',$sscConfig_adminImages,'/new.png" alt="Add" /><span>New gallery</span></a><br />';
}
echo '</div>';
?>