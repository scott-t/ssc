<?php
/**
 * Dynamic/News blog posts
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
function blog_admin(){
	
	// Work out what we want to do 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
	
		if (!empty($_GET['param'][1]) && $_GET['param'][1] == 'post'){
			$out = ssc_generate_form('blog_post');	
		}else{
			if ($_GET['param'][0] == 0)
				$out = '';
			else{
				$out = ssc_admin_table(t('Current posts'),
					"SELECT p.id, title, COUNT(c.body) FROM #__blog_post p LEFT JOIN #__blog_comment c ON p.id = post_id
					WHERE blog_id = %d GROUP BY post_id ",
					array($_GET['param'][0]),
					array('perpage' => 10, 'link' => 'title', 
						'linkpath' => "/admin/blog/edit/{$_GET['param'][0]}/post/"));
				$out .= l(t('New post'),"/admin/blog/edit/{$_GET['param'][0]}/post/0");
			}
			// List off paged posts for the specified blog
			$out .= ssc_generate_form('blog_form');
		}
		break;
	case 'page':
		// Allow for paging
		array_unshift($_GET['param'], 'page');
	case '':
		$out = ssc_admin_table(t('News pages'), 
			"SELECT b.id, name title, path FROM #__blog b 
			LEFT JOIN #__handler h ON h.id = b.id ORDER BY path ASC",
			null,
			array('perpage' => 10, 'link' => 'title', 'linkpath' => '/admin/blog/edit/'));
		$out .= l(t('New page'),'/admin/blog/edit/0');
		
		break;
	default:
		ssc_not_found();
		$out = '';
	}
	
	return $out;
}

/**
 * Implementation of module_content()
 */
function blog_content(){
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
function blog_form(){
	global $ssc_site_url, $ssc_database;
	if (isset($_POST['form-id']) && $_POST['form-id'] == 'blog_form'){
		$data = new stdClass();
		$data->title = (empty($_POST['title']) ? '' : $_POST['title']);
		$data->path = (empty($_POST['url']) ? '' : $_POST['url']);
		$data->id = (empty($_POST['id']) ? 0 : intval($_POST['id']));
		$data->page = (empty($_POST['page']) ? 5 : intval($_POST['page']));
		$data->comment = (empty($_POST['comment']) ? false : (bool)$_POST['comment']);
	}
	else{
		// Retrieve from DB
		$result = $ssc_database->query("SELECT name title, path, b.id, comments comment, page FROM #__blog b LEFT JOIN #__handler h ON b.id = h.id WHERE b.id = %d LIMIT 1", intval(array_shift($_GET['param'])));
		if (!($data = $ssc_database->fetch_object($result))){		
			$data = new stdClass();
			$data->title = '';
			$data->path = '';
			$data->id = 0;
			$data->page = 5;
			$data->comment = false;
		}
		else{

		}
	}

	$form = array('#method' => 'post', '#action' => '');
	$fieldset = array(	'#parent' => true,
						'#type' => 'fieldset',
						'#title' => t('Page settings'));
	$fieldset['title'] = array(	'#type' => 'text',
								'#value' => $data->title,
								'#title' => t('Page title'),
								'#required' => true,
								'#description' => t('Title to give the news/blog page'));
	$fieldset['url'] = array(	'#type' => 'text',
								'#value' => $data->path,
								'#title' => t('Path to page'),
								'#required' => true,
								'#description' => t('Path that should be used to access the page.  Should exclude \'!site\'', array('!site' => $ssc_site_url . '/')));
	$fieldset['page'] = array(	'#type' => 'text',
								'#value' => $data->page,
								'#title' => t('Posts per page'),
								'#required' => true,
								'#description' => t('Number of posts to be displayed on a page before adding previous/next page links'));
	$fieldset['comment'] = array(	'#type' => 'checkbox',
									'#value' => 1,
									'#checked' => $data->comment,
									'#title' => t('Allow comments'),
									'#description' => t('Check to allow visitors to write comments on things you post'));
	$fieldset['id'] = array('#type' => 'hidden',
							'#value' => $data->id);
	
	$fieldset['sub'] = array(	'#type' => 'submit',
								'#value' => 'Save settings');
	
	$form['data'] = $fieldset;
	return $form;
}

/**
 * Page validation
 */
function blog_form_validate(){
	if (!login_check_auth("blog"))
		return false;

	if (empty($_POST['title']) || !isset($_POST['url']) || !isset($_POST['id']) || empty($_POST['page']) ){
		ssc_add_message(SSC_MSG_CRIT, t('Not all required fields were filled in'));
		if (!empty($_POST['url']) && $_POST['url'][0] == '/')
			$_POST['url'] = substr($_POST['url'], 1);
			
		if (intval($_POST['page']) == 0)
			$_POST['page'] = 5;
			
		return false;
	}
	
	if (intval($_POST['page']) == 0)
		$_POST['page'] = 5;
	
	if (!empty($_POST['url']) && $_POST['url'][0] == '/')
		$_POST['url'] = substr($_POST['url'], 1);
	
	return true;
}

/**
 * Page submission
 */
function blog_form_submit(){
	global $ssc_database;
	$id = intval($_POST['id']);
	if ($id == 0){
		// Insert
		$result = $ssc_database->query("INSERT INTO #__handler (path, handler) VALUES ('%s', %d)", $_POST['url'], module_id('blog'));
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		
		$id = $ssc_database->last_id();
		$result = $ssc_database->query("INSERT INTO #__blog (id, name, comments, page) VALUES (%d, '%s', %d, %d)", $id, $_POST['title'], (empty($_POST['comment']) ? 0 : 1), $_POST['page']);
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		ssc_add_message(SSC_MSG_INFO, t('Page settings saved'));
		ssc_redirect('/admin/blog/edit/' . $id);
	}
	else{
		// Update
		$ssc_database->query("UPDATE #__blog b, #__handler h SET b.name = '%s', b.comments = %d, h.path = '%s', b.page = %d WHERE b.id = h.id AND b.id = %d", 
				$_POST['title'], (empty($_POST['comment']) ? 0 : 1), $_POST['url'], $_POST['page'], $id);
		echo $ssc_database->error();
	}
	ssc_add_message(SSC_MSG_INFO, t('Page settings saved'));
}

/**
 * Form to write blog/news posts
 */
function blog_post(){
	global $ssc_site_url, $ssc_database;
	if (isset($_POST['form-id']) && $_POST['form-id'] == 'blog_post'){
		$data = new stdClass();
		$data->title = (empty($_POST['title']) ? '' : $_POST['title']);
		$data->id = (empty($_POST['id']) ? 0 : intval($_POST['id']));
		$data->blog_id = (empty($_POST['bid']) ? (int)$_GET['param'][0] : intval($_POST['bid']));
		$data->url = (empty($_POST['url']) ? '' : $_POST['url']);
		$data->body = (empty($_POST['body']) ? '' : $_POST['body']);
		$data->keywords = (empty($_POST['keywords']) ? '' : $_POST['keywords']);
		$data->desc = (empty($_POST['desc']) ? '' : $_POST['desc']);
	}
	else{
		// Retrieve from DB
		$result = $ssc_database->query("SELECT title, urltext url, id, blog_id, body FROM #__blog_post b WHERE id = %d AND blog_id = %d LIMIT 1", intval($_GET['param'][2]), (int)$_GET['param'][0]);
		if (!($data = $ssc_database->fetch_object($result))){	
			$data = new stdClass();
			$data->title = '';
			$data->path = '';
			$data->id = 0;
			$data->blog_id = (int)$_GET['param'][0];
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
	$form['post'] = array(	'#type' => 'fieldset',
							'#title' => t('Post content'),
							'#parent' => true);
	$fieldset =& $form['post'];
	
	$fieldset['title'] = array(	'#type' => 'text',
								'#value' => $data->title,
								'#title' => t('Post title'),
								'#required' => true,
								'#description' => t('Name to give this post'));
	$fieldset['body'] = array(	'#type' => 'textarea',
								'#title' => t('Post body'),
								'#description' => t('Formatted text representing the content for this post'),
								'#required' => true,
								'#value' => $data->body);

	$fieldset['id'] = array('#type' => 'hidden',
						'#value' => $data->id);
	$fieldset['bid'] = array('#type' => 'hidden',
					'#value' => $data->blog_id);

	$fieldset['tags'] = array('#type' => 'none', '#value' => 'tags');
	
	$form['meta'] = array(	'#type' => 'fieldset',
							'#title' => t('Meta'),
							'#parent' => true);
	$fieldset =& $form['meta'];
	
	$fieldset['url'] = array(	'#type' => 'text',
								'#value' => $data->url,
								'#title' => t('Link-safe url'),
								'#description' => t('URL safe version of the post title - will be automatically generated if blank'));
	
	$fieldset['keywords'] = array(	'#type' => 'textarea',
									'#title' => t('Post keywords'),
									'#description' => t('Short list of comma separated keywords relating to the post'),
									'#value' => $data->keywords);
	$fieldset['desc'] = array(	'#type' => 'textarea',
								'#title' => t('Post summary'),
								'#description' => t('Short summary of the post'),
								'#value' => $data->desc);
	
	$form['sub'] = array(	'#type' => 'submit',
							'#value' => t('Save page'));
	$form['prev'] = array(	'#type' => 'submit',
							'#value' => t('Preview changes'));
	$form['rev'] = array(	'#type' => 'reset',
							'#value' => t('Revert changes'));
	
	return $form;
}

/**
 * Post validation
 */
function blog_post_validate(){
	if (!login_check_auth("blog"))
		return false;
		
	if (empty($_POST['title']) || empty($_POST['body']) || !isset($_POST['id'])){
		ssc_add_message(SSC_MSG_CRIT, t('Both post title and content need to be filled in'));
		return false;
	}

	// Validate url values
	if (empty($_POST['url'])){
		// Generate one
		$tmp = strtolower($_POST['title']);
	}else{
		$tmp = $_POST['url'];
	}
	$tmp = str_replace(array("/", "\\", " ", '_'), '-', $tmp);
	$tmp = str_replace(array("?", "%", "!", "@", "#", "$", '`', "~", "^", "&", "*"), "", $tmp);
	do {
		$tmp = str_replace("--", "-", $tmp, $count);
	} while($count > 0);
	$_POST['url'] = $tmp;

	return true;
}

/**
 * Post submission
 */
function blog_post_submit(){
	global $ssc_user, $ssc_database;
	$blog = (int)$_POST['bid'];
	$id = (int)$_POST['id'];
	// Someone trying to circumvent things
	if ($blog == 0)
		return;
	
	if ($id == 0){
		// Insert

		$result = $ssc_database->query("INSERT INTO #__blog_post (blog_id, title, created, modified, body, urltext, author_id) VALUES (%d, '%s', %d, %d, '%s', '%s', %d)",
			 $blog, $_POST['title'], time(), time(), $_POST['body'], $_POST['url'], $ssc_user->id);
		$_POST['id'] = $id = $ssc_database->last_id();
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		ssc_add_message(SSC_MSG_INFO, t('Post saved'));
		ssc_redirect("/admin/blog/edit/$blog/post/$id");
	}
	else{
		// Update
		$ssc_database->query("UPDATE #__blog_post b SET title = '%s', body = '%s', urltext = '%s', modified = %d WHERE id = %d AND blog_id = %d", 
				$_POST['title'], $_POST['body'], $_POST['url'], time(), $id, $blog);
	}
	ssc_add_message(SSC_MSG_INFO, t('Post saved'));
}