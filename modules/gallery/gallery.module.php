<?php
/**
 * Image gallery/uploading
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Implementation of module_content()
 */
function gallery_content(){
	global $ssc_database, $ssc_site_url;
	if (is_array($_GET['param'])){
		$page = array_shift($_GET['param']);
		// Check page
		if ($page == 'page'){
			$page = (int)array_shift($_GET['param']);
		}
		else{
			ssc_not_found();
		}
	}
	else{
		$page = 1;
	}
	
	$gid = (int)$_GET['path-id'];
	
	// Check if gallery exists and is visible
	$result = $ssc_database->query("SELECT title, description FROM #__gallery WHERE id = %d AND visible = 1 LIMIT 1", $gid);
	if ($result && $data = $ssc_database->fetch_assoc($result))
		ssc_set_title($data['title']);
	else
		ssc_not_found();
	
	// Fetch contents
	$res = $ssc_database->query_paged($page, 20, "SELECT id, caption FROM #__gallery_content WHERE gallery_id = %d ORDER BY id ASC", $gid);
	$result =& $res['result'];
	$count = 20;
	$out = '';
	while (($data = $ssc_database->fetch_assoc($result)) && $count--){
		$out .= "<a href=\"$ssc_site_url/images/gallery/$gid/${data['id']}\"><img src=\"$ssc_site_url/images/gallery/$gid/${data['id']}_t\" alt=\"\" /></a> \n";
	}
	
	return $out;
	
}

/**
 * Implementation of module_admin()
 */
function gallery_admin(){
	global $ssc_database;

	// What aspect of admin? 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
		$out = ssc_generate_form('gallery_form');
		break;
		
	case 'page':
		// Cater for page calls
		array_unshift($_GET['param'], 'page');
	case '':
		// "Main" admin page - show galleries
		$out = ssc_admin_table(t('Current galleries'), 
			"SELECT g.id, title, description, COUNT(c.id) items, if (visible, 'Yes', 'No') Visible FROM 
			#__gallery g LEFT JOIN #__gallery_content c ON g.id = gallery_id GROUP BY gallery_id
			ORDER BY visible DESC, title asc", null, 
				array('perpage' => 10, 'link' => 'title', 
					'linkpath' => "/admin/gallery/edit/"));
				
		$out .= l(t('New gallery'),"/admin/gallery/edit/0");
		
	}
	
	return $out;
}

/**
 * Gallery content modification form
 */
function gallery_form(){
	global $ssc_database, $ssc_site_url;
	
	if (isset($_GET['param'][0])){
		$galID = (int)array_shift($_GET['param']);
	}
	if (!empty($_POST['form-id']) && $_POST['form-id'] == 'gallery_form'){
		$data = new stdClass();
		$data->name = (empty($_POST['name']) ? '' : $_POST['name']);
		$data->descr = (empty($_POST['desc']) ? '' : $_POST['desc']);		
		$data->visible = (empty($_POST['vis']) ? 0 : (int)$_POST['vis']);
		$data->path = (empty($_POST['url']) ? '' : $_POST['url']);
	}
	elseif ($galID > 0){
		$result = $ssc_database->query("SELECT path, title name, description descr, visible FROM #__gallery g 
			LEFT JOIN #__handler h ON h.id = g.id WHERE h.id = %d LIMIT 1", $galID);
		if (!$result || !($data = $ssc_database->fetch_object($result))){
			// Something borked
			$data = new stdClass();
			$data->name = '';
			$data->descr = '';
			$data->visible = 1;
			$data->path = '';
		}
	}
	else{
		// New
		$data = new stdClass();
		$data->name = '';
		$data->descr = '';
		$data->visible = 1;
		$data->path = '';
		$galID = 0;
	}
	
	$form = array('#action' => '', '#method' => 'post',
					'#attributes' => array('enctype' => 'multipart/form-data'));
	
	$fieldset =& $form['details'];
	$fieldset = array(	'#type' => 'fieldset',
						'#title' => t('Gallery details'),
						'#parent' => true);
	$fieldset['name'] = array(	'#title' => t('Gallery name'),
								'#description' => t('Name to display at top of the page'),
								'#type' => 'text',
								'#required' => true,
								'#value' => $data->name);
	
	$fieldset['url'] = array(	'#type' => 'text',
								'#value' => $data->path,
								'#title' => t('Path to gallery'),
								'#required' => true,
								'#description' => t('Path that should be used to access the gallery.  Should exclude \'!site\'', array('!site' => $ssc_site_url . '/')));
	
	$fieldset['desc'] = array(	'#type' => 'textarea',
								'#title' => t('Gallery description'),
								'#description' => t('Short optional description relating to the gallery.  Plain-text only!'),
								'#value' => $data->descr);
	
	
	$fieldset['vis'] = array(	'#type' => 'checkbox',
								'#title' => t('Enabled'),
								'#description' => t('If checked, the gallery will be enabled for viewing'),
								'#value' => 1,
								'#checked' => $data->visible);
	$fieldset['gid'] = array(	'#type' => 'hidden',
								'#value' => $galID);
	
	$fieldset['sub'] = array(	'#type' => 'submit',
								'#value' => t('Save changes'));
	$fieldset['rev'] = array(	'#type' => 'reset',
								'#value' => t('Revert changes'));
	
	// Return only first half for new gallery
	if ($galID == 0)
		return $form;
	
	$fieldset =& $form['upload'];
	$fieldset = array(	'#type' => 'fieldset',
						'#title' => t('Upload photos'),
						'#parent' => true);
	
	$fieldset['single'] = array('#type' => 'file',
							'#title' => t('Upload single image'),
							'#description' => t('Add a single image to the gallery.  Image will be automatically resized as needed.'));
	
	$fieldset['sub'] = array(	'#type' => 'submit',
								'#value' => t('Save and upload'));

	$result = $ssc_database->query("SELECT id, caption, mid FROM #__gallery_content WHERE gallery_id = %d", $galID);
	
	if (!$result)
		return $form;
		
	$fieldset =& $form['content'];
	$fieldset = array(	'#type' => 'fieldset',
						'#title' => t('Gallery content'),
						'#parent' => true);
	
	// Generate caption listing
	$input = array('#type' => 'text','#maxlength' => 150);
	$input_border = array('#title' => t('Caption'),
						'#description' => t('Short caption for the image'));
		
	while ($data = $ssc_database->fetch_object($result)){
		$input['#name'] = "item[$data->id][cap]";
		$input['#value'] = $data->caption;
		$input_border['#value'] = theme_render_input($input);
		$out = "<div class=\"form-img\"><img src=\"$ssc_site_url/images/gallery/$galID/{$data->id}_t\" alt=\"\" />";
		
		$out .= theme_render_form_element($input_border);
		
		$out .= '</div>';
	
		$fieldset["item$data->id"] = array(	'#type' => '',
											'#value' => $out);

	}
	
	return $form;

}

/**
 * Gallery edit validation 
 */
function gallery_form_validate(){
	global $ssc_database;
	// Drop invalid user
	if (!login_check_auth("gallery")){
		return false;
	}
	
	if (empty($_POST['name']) || !isset($_POST['url'], $_POST['gid'])){
		ssc_add_message(SSC_MSG_CRIT, t('Gallery name can\'t be empty'));
		return false;
	}
	
	// Check valid form combo
	$gid = $_POST['gid'] = (int)($_POST['gid']);
	if ($gid < 0 || ($gid == 0 && isset($_POST['item'])))
		return false;
		
	if (empty($_POST['desc']))
		$_POST['desc'] = '';
		
	if (isset($_POST['vis'])){
		$_POST['vis'] = 1;
	}
	else{
		$_POST['vis'] = 0;
	}
	
	$result = $ssc_database->query("SELECT id FROM #__handler WHERE path = '%s' LIMIT 1", $_POST['url']);
	if (!$result)
		return false;
		
	$data = $ssc_database->fetch_object($result);
	if ($data && $data->id != $gid){
		ssc_add_message(SSC_MSG_CRIT, t('That path name has already been used elsewhere'));
		return false;
	}
	
	if (!empty($_FILES['single'])){
		switch ($_FILES['single']['error']){
		case UPLOAD_ERR_OK:
			// Upload good
			
			break;
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			ssc_add_message(SSC_MSG_WARN, t('The image you uploaded was too large'));
			unset($_FILES['single']);
			break;
		case UPLOAD_ERR_PARTIAL:
		case UPLOAD_ERR_NO_TMP_DIR:
		case UPLOAD_ERR_CANT_WRITE:
		case UPLOAD_ERR_EXTENSION:
		default:
			ssc_add_message(SSC_MSG_WARN, t('There was an error uploading the image'));
			unset($_FILES['single']);
			break;
		}
	}
	
	return true;
}

/**
 * Gallery edit submission
 */
function gallery_form_submit(){
	global $ssc_database, $ssc_site_path;
	
	if ($_POST['gid'] == 0){
		// Insert new
		$result = $ssc_database->query("INSERT INTO #__handler (status, handler, path) 
				VALUES (0, %d, '%s')", module_id('gallery'), $_POST['url']);
				
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		$id = $ssc_database->last_id();
				
		$result = $ssc_database->query("INSERT INTO #__gallery (id, title, description, visible) 
				VALUES (%d, '%s', '%s', %d)", $id, $_POST['name'], $_POST['desc'], $_POST['vis']);
		
		if (!$result){
			$ssc_database->query("DELETE FROM #__handler WHERE id = %d LIMIT 1", $id);
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		
		mkdir($ssc_site_path . '/images/gallery/' . $id);
		
		ssc_add_message(SSC_MSG_INFO, t('Gallery saved'));
		ssc_redirect('/admin/gallery/edit/' . $id);
		
	}
	else{
		$result = $ssc_database->query("UPDATE #__gallery g, #__handler h SET title = '%s', description = '%s', 
				visible = %d, path = '%s' WHERE g.id = %d AND g.id = h.id ",
				$_POST['name'], $_POST['desc'], $_POST['vis'], $_POST['url'], $_POST['gid']);
				
	}

	if (isset($_FILES['single'])){
		// Uploading single file
		$ext = pathinfo($_FILES['single']['name']);
		$ext = "." . $ext['extension'];
		$file = $ssc_site_path . '/tmp/' . time() . "$ext";
		if (!move_uploaded_file($_FILES['single']['tmp_name'], $file))
			return;
						
		$image = new sscImage($file);
		// Possibly messy, but insert before resizing
		$result = $ssc_database->query("INSERT INTO #__gallery_content (gallery_id, caption, mid) VALUES (%d, '', 0)", $_POST['gid']);

		if (!$result)
			return;
			
		$id = $ssc_database->last_id();
		$path = $ssc_site_path . '/images/gallery/' . $_POST['gid'] . '/';
		if (!$image->resize($path . $id . $ext, 1024, -1)){
			$ssc_database->query("DELETE FROM #__gallery_content WHERE id = %d LIMIT 1", $id);
			unlink($file);
			return;
		}
		
		if (!$image->resize($path . $id . "_m.$ext", 350, -1)){
			$ssc_database->query("DELETE FROM #__gallery_content WHERE id = %d LIMIT 1", $id);
			unlink($file);
			unlink($path.$id.$ext);
			return;
		}
			
		if (!$image->resize($path . $id . "_t.$ext", 150, -1)){
			$ssc_database->query("DELETE FROM #__gallery_content WHERE id = %d LIMIT 1", $id);
			unlink($file);
			unlink($path . $id . $ext);
			unlink($path . $id . "_m.$ext");
			return;
		}
		ssc_add_message(SSC_MSG_INFO, t('Image uploaded'));
		unlink($file);
	}
}