<?php
/**
 * Static/simple page display
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Implementation of module_admin()
 */
function static_admin(){
	
	// Work out what we want to do 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
		$out = '';
		if (!empty($_POST['form-id']) && $_POST['form-id'] == 'static_form' && !empty($_POST['body'])){
			if (!ssc_load_library('sscText')){
				ssc_add_message(SSC_MSG_WARN, t('Unable to show page preview'));
			}
			else{
				$out = sscText::convert($_POST['body']);
			}
		}
		$out .= ssc_generate_form('static_form');
		break;
	case 'page':
		// Allow for paging
		array_unshift($_GET['param'], 'page');
	case '':
		$out = ssc_admin_table(t('Simple pages'), 
			"SELECT s.id, title, path FROM #__static s 
			LEFT JOIN #__handler h ON h.id = s.id ORDER BY path ASC",
			null,
			array('perpage' => 10, 'link' => 'title', 'linkpath' => '/admin/static/edit/'));
		$out .= l(t('New page'),'/admin/static/edit/0');
		break;
	default:
		ssc_not_found();
		$out = '';
	}
	
	return $out;
}

function static_widget($title, $body){
	$args = func_get_args();
	$title = array_shift($args);
	$body = array_shift($args);
	
	if (empty($body))
		return;
	
	while(!empty($args[0]) && !is_int($args[0]))
		$body .= array_shift($args);
	
	if (!empty($args[0]) && !in_array($args)){
	// Not in the allowed list... jump n run
		return;
	}
	if (!ssc_load_library('sscText')){
		return;
	}
	return array('title'=>$title, 'body'=>sscText::convert($body));
}

/**
 * Implementation of module_content()
 */
function static_content(){
	global $ssc_database;
	
	// We'll never accept params, so is gonna be a 404
	if (!empty($_GET['param']))
		ssc_not_found();
		
	// Find content
	$result = $ssc_database->query("SELECT title, created, modified, body FROM #__static WHERE id = %d LIMIT 1", $_GET['path-id']);
	if ($result && $data = $ssc_database->fetch_assoc($result)){
		if (!ssc_load_library('sscText')){
			ssc_not_found();	// Strictly speaking, the library /wasn't/ found...
		}
		ssc_set_title($data['title']);
		return sscText::convert($data['body']);
	}
	
	ssc_not_found();
}

/**
 * Simple page content editing
 */
function static_form(){
	global $ssc_site_url, $ssc_database;
	if (isset($_POST['form-id']) && $_POST['form-id'] == 'static_form'){
		$data = new stdClass();
		$data->title = (empty($_POST['title']) ? '' : $_POST['title']);
		$data->path = (empty($_POST['url']) ? '' : $_POST['url']);
		$data->id = (empty($_POST['id']) ? 0 : intval($_POST['id']));
		$data->body = (empty($_POST['body']) ? '' : $_POST['body']);
		$data->keywords = (empty($_POST['keywords']) ? '' : $_POST['keywords']);
		$data->desc = (empty($_POST['desc']) ? '' : $_POST['desc']);
	}
	else{
		// Retrieve from DB
		$result = $ssc_database->query("SELECT title, path, s.id, body FROM #__static s LEFT JOIN #__handler h ON s.id = h.id WHERE s.id = %d LIMIT 1", intval(array_shift($_GET['param'])));
		if (!($data = $ssc_database->fetch_object($result))){
			$data = new stdClass();
			$data->title = '';
			$data->path = '';
			$data->id = 0;
			$data->body = '';
			$data->keywords = '';
			$data->desc = '';
		}
		else{
			$data->keywords = '';
			$data->desc = '';
		}
	}

	$form = array('#method' => 'post', '#action' => '');
	$fieldset = array(	'#parent' => true,
						'#type' => 'fieldset',
						'#title' => t('Simple page'));
	$fieldset['title'] = array(	'#type' => 'text',
								'#value' => $data->title,
								'#title' => t('Page title'),
								'#required' => true,
								'#description' => t('Text to appear in the title bar'));
	$fieldset['url'] = array(	'#type' => 'text',
								'#value' => $data->path,
								'#title' => t('Path to page'),
								'#required' => true,
								'#description' => t('Path that should be used to access page beginning.  Should exclude \'!site\'', array('!site' => $ssc_site_url . '/')));
	$fieldset['id'] = array('#type' => 'hidden',
							'#value' => $data->id);
	
	$form['details'] = $fieldset;
	
	$attrib = array('class' => 'collapse');
	$fieldset = array(	'#parent' => true,
						'#type' => 'fieldset',
						'#title' => t('Content'),
						'#attributes' => $attrib);
	$fieldset['body'] = array(	'#type' => 'textarea',
								'#title' => t('Page content'),
								'#description' => t('Formatted text representing the page body'),
								'#required' => true,
								'#value' => $data->body);
	$form['content'] = $fieldset;
	
	$attrib = array('class' => 'collapse collapsed');
	$fieldset = array(	'#parent' => true,
						'#type' => 'fieldset',
						'#title' => t('Meta'),
						'#attributes' => $attrib);
	$fieldset['keywords'] = array(	'#type' => 'textarea',
									'#title' => t('Page keywords'),
									'#description' => t('Short list of comma separated keywords relating to the page'),
									'#value' => $data->keywords);
	$fieldset['desc'] = array(	'#type' => 'textarea',
								'#title' => t('Page description'),
								'#description' => t('Short summary of page content'),
								'#value' => $data->desc);
	
	$form['meta'] = $fieldset;
		
	$form['sub'] = array(	'#type' => 'submit',
							'#value' => t('Save page'));
	$form['prev'] = array(	'#type' => 'submit',
							'#value' => t('Preview changes'));
	$form['rev'] = array(	'#type' => 'reset',
							'#value' => t('Revert changes'));
	return $form;
}

/**
 * Page validation
 */
function static_form_validate(){
	if (!login_check_auth("static"))
		return false;

	// Only saved if properly submitted - not preview
	if (empty($_POST['sub'])){
		ssc_add_message(SSC_MSG_WARN, t('This is a preview - the form continues below.'));
		if (!empty($_POST['url']) && $_POST['url'][0] == '/')
			$_POST['url'] = substr($_POST['url'], 1);
			
		return false;
	}

	if (empty($_POST['title']) || !isset($_POST['url']) || empty($_POST['body'])){
		ssc_add_message(SSC_MSG_CRIT, t('Not all required fields were filled in'));
		if (!empty($_POST['url']) && $_POST['url'][0] == '/')
			$_POST['url'] = substr($_POST['url'], 1);
			
		return false;
	}
	
	if (!empty($_POST['url']) && $_POST['url'][0] == '/')
		$_POST['url'] = substr($_POST['url'], 1);
	
	return true;
}

/**
 * Page submission
 */
function static_form_submit(){
	global $ssc_database;
	$id = intval($_POST['id']);
	if ($id == 0){
		// Insert
		$result = $ssc_database->query("INSERT INTO #__handler (path, handler) VALUES ('%s', %d)", $_POST['url'], module_id('static'));
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		
		$id = $ssc_database->last_id();
		$result = $ssc_database->query("INSERT INTO #__static (id, title, created, modified, body) VALUES (%d, '%s', %d, %d, '%s')", $id, $_POST['title'], time(), time(), $_POST['body']);
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		ssc_add_message(SSC_MSG_INFO, t('Page saved'));
		ssc_redirect('/admin/static/edit/' . $id);
	}
	else{
		// Update
		$ssc_database->query("UPDATE #__static s, #__handler h SET s.title = '%s', s.body = '%s', h.path = '%s', s.modified = %d WHERE s.id = h.id AND s.id = %d", 
				$_POST['title'], $_POST['body'], $_POST['url'], time(), $id);
	}
	ssc_add_message(SSC_MSG_INFO, t('Page saved'));
}