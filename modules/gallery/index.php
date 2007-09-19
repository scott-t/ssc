<?php
/**
 * Photo gallery module
 *
 * Display the photo gallery
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');
global $database, $sscConfig_webPath;

/* we'll need three modes - gallery select (will use first image of each gallery as thumb), thumbnail view 
 * and finally either slideshow mode or just "big" without anything else.  only problem is slideshow would
 * totally break smaller screens
 */
 
function createGalleryBox($url,$pid,$cap){
	global $sscConfig_webPath;
	echo '<div class="gal-img"><a href="',$url,'"><span><img src="',$sscConfig_webPath, '/modules/gallery/photos/',$pid,'_thumb.jpg" alt="" /></span>',$cap,'</a><br class="clear" /></div>';
}

$tmp = explode('/',$_GET['q']);
//set up the expected parameters as before
if(isset($tmp[1])){
	$_GET['sub'] = $tmp[1];
	$num = count($tmp);
	for($i = 2; $i < $num;){
		if(isset($tmp[$i+1])){
			//this plus next set... match them up
			$_GET[$tmp[$i]] = $tmp[$i+1];
		}
		$i+= 2;
	}
} 

if(isset($_GET['sub'])){
	//get gallery details
	$database->setQuery(sprintf("SELECT id, name FROM #__gallery WHERE name LIKE '%s' AND id > 1 LIMIT 1",$database->escapeString(str_replace('-',' ',$_GET['sub']))));
	if($database->query() && $data = $database->getAssoc()){
		echo '<h1>Photo Gallery</h1><h2>',$data['name'],'</h2>Click on an image to view an enlargement<br />';
		$gid = $data['id'];
		if(isset($_GET['page'])){
			//we got page numbers...
			$page = intval($_GET['page']);
			$page_s = $page * 20;
			$page_f = $page_s + 20;
		}else{
			$page = 0;
			$page_s = 0;
			$page_f = 20;
		}
		$database->setQuery(sprintf("SELECT id, owner, caption FROM #__gallery_content WHERE gallery_id = %d AND id > 1 ORDER BY id ASC LIMIT %d,%d", $gid, $page_s, $page_f));
		if($database->query()){
			echo '<div class="panel">';
			//paging
			if($page > 0)
				echo '<span class="header-l"><a href="',$sscConfig_webPath,'/gallery/',$_GET['sub'],'/page/',$page-1,'">Previous page</a>';
			$rows = $database->getNumberRows();
			if($rows == 21)
				echo '<span class="header-l"><a href="',$sscConfig_webPath,'/gallery/',$_GET['sub'],'/page/',$page+1,'">Next page</a>';
				
			//ok... now the photos
			if($rows == 0){
				echo message('Invalid gallery page number');
			}else{
				//start boxing up...
				while($data = $database->getAssoc()){
					createGalleryBox($sscConfig_webPath . '/modules/gallery/photos/'.$gid.'/'.$data['id'].'.jpg', $gid . '/' . $data['id'], $data['caption']);
				}
			}
			echo '<div class="clear" /></div></div>';
		}else{
			echo error('There was an error retrieving gallery contents');
		}
	}else{
		echo error('There was an error retrieving gallery contents');
	}
	echo '<div class="clear" /></div>';
	echo '<a href="',$sscConfig_webPath,'/gallery">Return</a> to the photo gallery<br />';

}else{
	//decide which gallery to open
	$database->setQuery("SELECT #__gallery.id, #__gallery_content.id AS pid, name FROM #__gallery RIGHT JOIN #__gallery_content ON gallery_id = #__gallery.id WHERE #__gallery.id > 1 GROUP BY gallery_id");
	if($database->query()){
		echo '<h1>Photo Gallery</h1>Choose a gallery to continue<br />';
		echo '<div class="panel">';
		if($database->getNumberRows() > 0){
		while($data = $database->getAssoc()){
			createGalleryBox($sscConfig_webPath . '/gallery/'.str_replace(' ','-',strtolower($data['name'])),$data['id'].'/'.$data['pid'],$data['name']);
		}
		}else{echo message('There are currently no galleries set up.  Please try again later');}
		echo '<div class="clear" /></div></div>';
	}else{
		echo error('There was an error retrieving gallery contents');
	}
}

?>