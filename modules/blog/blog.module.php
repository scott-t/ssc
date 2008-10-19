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
 * Comment submission flag: Comment has been read by post owner
 */
define('SSC_BLOG_READ', 4);
/**
 * Comment submission flag: Comment has been toggled before
 */
define('SSC_BLOG_SPAM', 2);
/**
 * Comment submission flag: Comment is a spam post
 */
define('SSC_BLOG_CAN_SPAM', 1);

function blog_meta(){
	if ($_GET['handler'] == 'blog')
		return '<link rel="alternate" type="application/atom+xml" title="Subscribe using Atom 1.0" href="' . $_GET['path'] . '/feed" />';
	else
		return;
}

function blog_cron(){
	global $ssc_site_path;
	include($ssc_site_path . "/modules/blog/rss.php");
}

function blog_widget($args){
	global $ssc_database;
	if ($_GET['handler'] != 'blog') return;

	//$args = 3;
	$block = array();
	if ($args == 1){
		$result = $ssc_database->query("SELECT tag, COUNT(post_id) AS cnt FROM #__blog_post p, #__blog_tag t LEFT JOIN #__blog_relation r ON tag_id = t.id WHERE post_id = p.id AND blog_id = %d GROUP BY t.id ORDER BY tag ASC", 3);
		if($result && $ssc_database->number_rows() > 0){
	
			while($data = $ssc_database->fetch_assoc($result)){
				$block[] = array('t'=>$data['tag'] . " ($data[cnt])", 'p' => $_GET['path'] . '/tag/' . $data['tag']);
			}

		return nav_widget($block, 'Tags');
	
		}
	}
	
	$result = $ssc_database->query("SELECT YEAR( FROM_UNIXTIME(created) ) AS yr, COUNT( FROM_UNIXTIME(created) ) AS cnt FROM #__blog_post p WHERE blog_id = %d GROUP BY YEAR( FROM_UNIXTIME(created) ) ORDER BY yr DESC", 3);
	if($result && $ssc_database->number_rows() > 0){

		while($data = $ssc_database->fetch_assoc($result)){
			$block[] = array('t'=>$data['yr'] . " ($data[cnt])", 'p' => $_GET['path'] . '/' . $data['yr']);
		}
				
		return nav_widget($block, 'Archive');
	}
}

/**
 * Implementation of module_admin()
 */
function blog_admin(){
	global $ssc_database;
	// Work out what we want to do 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
	
		if (!empty($_GET['param'][1]) && $_GET['param'][1] == 'post'){
			// Edit a post
			if ((isset($_POST['prev']) || isset($_POST['sub'])) && ssc_load_library('sscText')){
				$out = "<h2>Preview - " . (empty($_POST['title']) ? "notitle" : $_POST['title']) . "</h2>\n";
				$out .= sscText::convert((empty($_POST['body']) ? "nobody" : $_POST['body']));
			}
			else
			{
				$out = "";
			}
			
			$out .= ssc_generate_form('blog_post');
			$out .= '<hr /><h3>Comments</h3><form action="" method="post"><table class="admin-table"><tr><th><input type="hidden" name="form-id" value="blog_spam_ham" />ID</th><th>Name</th><th>Email</th><th>Comment</th><th>Action</th></tr>';
			$result = $ssc_database->query("SELECT id, author, site, email, body, status FROM #__blog_comment WHERE post_id = %d ORDER BY created ASC", $_GET['param'][2]);
			
			// Button to hide comment
			$sub_hide = array('#value' => 'Hide comment', '#type' => 'submit');
			$sub_show = array('#value' => 'Show comment', '#type' => 'submit');
			$sub_spam = array('#value' => 'Mark spam', '#type' => 'submit');
			$sub_ham = array('#value' => 'Unmark spam', '#type' => 'submit');
			$row = 0;
			while ($data = $ssc_database->fetch_object($result)){
				$out .= "<tr class=\"row".$row++."\"><td>$data->id</td><td>" . l($data->author, $data->site) . "</td><td>" . check_plain($data->email) . "</td><td";
				$status = $data->status;

				if ($status & SSC_BLOG_SPAM){
					$out .= " class=\"blog-spam-icon\"";
				}
				else {
					$out .= " class=\"blog-notspam-icon\"";
				}
				$out .=">" . check_plain($data->body) . "</td><td>";
				// If tree for actions
				if ($status & SSC_BLOG_CAN_SPAM){
					// Hasn't been re-submitted yet
					if ($status & SSC_BLOG_SPAM){
						// Was marked as spam
						$sub_ham['#name'] = "ham[$data->id]";
						$out .= theme_render_input($sub_ham);
						$sub_show['#name'] = "show[$data->id]";
						$out .= theme_render_input($sub_show);
					}
					else{
						// Was not marked spam
						$sub_spam['#name'] = "spam[$data->id]";
						$out .= theme_render_input($sub_spam);
						$sub_hide['#name'] = "hide[$data->id]";
						$out .= theme_render_input($sub_hide);
					}
				}
				else{
					// Has already been resubmitted
					if ($status & SSC_BLOG_SPAM){
						// Currently spam/hidden
						$sub_show['#name'] = "show[$data->id]";
						$out .= theme_render_input($sub_show);
					}
					else{
						// Marked as normal currently
						$sub_hide['#name'] = "hide[$data->id]";
						$out .= theme_render_input($sub_hide);
					}
				}
				$out .= "</td></tr>";
			}
			$out .= '</table></form>';
		} else {
			// Return list of posts to edit
			if ($_GET['param'][0] == 0)
				$out = '';
			else{
				$out = ssc_admin_table(t('Current posts'),
					"SELECT p.id, title, COUNT(c.body) comments FROM #__blog_post p LEFT JOIN #__blog_comment c ON p.id = post_id
					WHERE blog_id = %d GROUP BY p.id ORDER BY p.created DESC",
					array($_GET['param'][0]),
					array('perpage' => 10, 'link' => 'title', 
						'linkpath' => "/admin/blog/edit/{$_GET['param'][0]}/post/",
						'pagelink' => "/admin/blog/edit/{$_GET['param'][0]}/page/"));
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
 * 
 * Blog content and parameters can be interpreted in several different methods
 * 
 *   - /
 *     No parameters.  Should show (paged) all posts in the blog.
 * 
 *   - /tag/xxx 
 *     Responds with paged posts relating to that tag.
 * 
 *   - /yyyy/mm/dd/post-name
 *     Retrieve the post based on the url safe-name
 * 
 *   - /id/123
 *     Used as permalink.  Perma-redirect to current /yyyy/mm/dd/post-name url
 * 
 *   - /yyyy
 *     Archival retrieval of posts (no content) in the specified year
 */
function blog_content(){
	global $ssc_database;
	
	$result = $ssc_database->query("SELECT name, comments, page FROM #__blog WHERE id = %d LIMIT 1", $_GET['path-id']);
	if ($result && $data = $ssc_database->fetch_assoc($result)){
		// Load display library
		if (!ssc_load_library('sscText')){
			ssc_not_found();
			return;
		}

		// Get blog settings
		ssc_set_title($data['name']);
		$_GET['param'] = explode("/", $_GET['param']);
		$_GET['blog_comments'] = (bool)$data['comments'];
		$action = array_shift($_GET['param']);

		if ($action == '' || $action == 'page'){
			// Show paged posts
			array_unshift($_GET['param'], 'page');
			if (count($_GET['param']) > 2)
				ssc_not_found();
				
			return _blog_gen_post($data['page'], $_GET['path'] . '/page/', 
				"SELECT p.id, p.title, p.created, p.urltext, u.displayname author, count(c.post_id) count, p.body FROM #__blog_post p LEFT JOIN
				#__user u ON u.id = p.author_id LEFT JOIN #__blog_comment c ON (post_id = p.id AND (status & %d = 0)) WHERE blog_id = %d  
				GROUP BY p.id ORDER BY p.created DESC", SSC_BLOG_SPAM, $_GET['path-id']);
		}		
		elseif ($action == 'tag'){
			// Show posts for the tag

			if (count($_GET['param']) == 2 || count($_GET['param']) > 3)
				ssc_not_found();
				
			$tag = array_shift($_GET['param']);
			if (empty($tag))
				ssc_not_found();	// If to parameter for the tag, die gracefully
				
			return _blog_gen_post($data['page'], $_GET['path'] . '/tag/'.$tag.'/page/', 
				"SELECT p.id, p.title, p.created, p.urltext, u.displayname author, count(c.post_id) count, p.body FROM #__blog_post p LEFT JOIN
				#__user u ON u.id = p.author_id LEFT JOIN #__blog_comment c ON (post_id = p.id AND (status & %d = 0)) LEFT JOIN 
				#__blog_relation r ON r.post_id = p.id LEFT JOIN #__blog_tag t ON t.id = r.tag_id WHERE blog_id = %d AND t.tag = '%s'
				GROUP BY p.id ORDER BY p.created DESC", SSC_BLOG_SPAM, $_GET['path-id'], $tag);
			
		}
		elseif ($action == 'id'){
			// Redirect as needed
			if (count($_GET['param']) != 1)
				ssc_not_found();	// Extra parameters
				
			$result = $ssc_database->query("SELECT created, urltext FROM #__blog_post WHERE id = %d LIMIT 1", (int)array_shift($_GET['param']));
			if ($data = $ssc_database->fetch_object($result)){
				ssc_redirect($_GET['path'] . date("/Y/m/d/", $data->created) . $data->urltext, 301);
				return;
			}
			// Post ID doesn't exist - kill
			ssc_not_found();
			
		}
		else {
			// Not those - is int?
			$action = (int)$action;
			// Check for bad first param
			if ($action == 0){
				ssc_not_found();
				return;
			}
			
			// Check if the post name exists?
			if (!empty($_GET['param'][2])){
				// Retrieve post
				$result = $ssc_database->query(
					"SELECT p.id, p.title, p.created, p.urltext, u.displayname author, p.body FROM #__blog_post p 
					LEFT JOIN #__user u ON u.id = p.author_id WHERE blog_id = %d AND p.urltext = '%s' 
					LIMIT 1", $_GET['path-id'], $_GET['param'][2]);

				if (!($data = $ssc_database->fetch_object($result))){
					// No post with name - kill output
					ssc_not_found();
					return;
				}

				$out = "\n<h3>$data->title</h3>\n";
				$out .= t("Posted !date at !time by !author\n", 
					array(	'!date' => date(ssc_var_get('date_med', SSC_DATE_MED), $data->created),
							'!time' => date(ssc_var_get('time_short', SSC_TIME_SHORT), $data->created),
							'!author' => $data->author)) . '<br />';

				$result = $ssc_database->query("SELECT tag FROM #__blog_relation r, #__blog_tag t WHERE r.tag_id = t.id AND r.post_id = %d ORDER BY tag ASC", $data->id);
				
				// Retrieve list of tags for the post
				if ($ssc_database->number_rows()){
					$out .= "Tagged: ";
					$txt = '';
					while($dat = $ssc_database->fetch_object($result))
						$txt .= ', ' . l($dat->tag, $_GET['path'] . '/tag/' . $dat->tag);
					
					$txt  = substr($txt, 2);
					$out .= $txt.'<br />';
				}

				$out .= sscText::convert($data->body);
			
				if ($_GET['blog_comments']){
					// Retrieve comments
					$out .= '<div class="clear"></div><h3 id="comments">Comments</h3>';
					$is_admin = login_check_auth("blog");
					if ($is_admin){
						$result = $ssc_database->query("SELECT id, author, site, created, status, body FROM #__blog_comment 
						WHERE post_id = %d ORDER BY created ASC", $data->id, SSC_BLOG_SPAM, SSC_BLOG_SPAM);
					}
					else{
						$result = $ssc_database->query("SELECT author, site, created, body FROM #__blog_comment 
						WHERE post_id = %d AND status & %d = 0 ORDER BY created ASC", $data->id, SSC_BLOG_SPAM);
					}
					$pid = $data->id;
					
					if (!$result || $ssc_database->number_rows($result) == 0){
						// Bad SQL
						$out .= 'There are no comments posted yet.';
					}
					else{
						// Print comments
						
						// Admin user - show spam/ham options
						if ($is_admin){
							$out .= '<form action="" method="post"><div><input type="hidden" name="form-id" value="blog_spam_ham" />';
							while ($data = $ssc_database->fetch_object($result)){
								$status = $data->status;	
								$out .= '<div class="' . 
								($status & SSC_BLOG_SPAM ? 'blog-spam-icon' : 'blog-notspam-icon') .
								'"><p>' . nl2br(check_plain($data->body)) . '</p><p>';
								$out .= t("Posted !date at !time by !author\n", 
										array(	'!date' => date(ssc_var_get('date_med', SSC_DATE_MED), $data->created),
												'!time' => date(ssc_var_get('time_short', SSC_TIME_SHORT), $data->created),
												'!author' => 
										(empty($data->site) ? $data->author : l($data->author, $data->site)))) . '</p>';

								$sub_hide = array('#value' => 'Hide comment', '#type' => 'submit');
								$sub_show = array('#value' => 'Show comment', '#type' => 'submit');
								$sub_spam = array('#value' => 'Mark spam', '#type' => 'submit');
								$sub_ham = array('#value' => 'Unmark spam', '#type' => 'submit');
								// If tree for actions
								if ($status & SSC_BLOG_CAN_SPAM){
									// Hasn't been re-submitted yet
									if ($status & SSC_BLOG_SPAM){
										// Was marked as spam
										$sub_ham['#name'] = "ham[$data->id]";
										$out .= theme_render_input($sub_ham);
										$sub_show['#name'] = "show[$data->id]";
										$out .= theme_render_input($sub_show);
									}
									else{
										// Was not marked spam
										$sub_spam['#name'] = "spam[$data->id]";
										$out .= theme_render_input($sub_spam);
										$sub_hide['#name'] = "hide[$data->id]";
										$out .= theme_render_input($sub_hide);
									}
								}
								else{
									// Has already been resubmitted
									if ($status & SSC_BLOG_SPAM){
										// Currently spam/hidden
										$sub_show['#name'] = "show[$data->id]";
										$out .= theme_render_input($sub_show);
									}
									else{
										// Marked as normal currently
										$sub_hide['#name'] = "hide[$data->id]";
										$out .= theme_render_input($sub_hide);
									}
								}
								$out .= '</div><hr />';
							}
							$out .= '</div></form>';
						}
						else{
							// Just show comments
							while ($data = $ssc_database->fetch_object($result)){
								$out .= '<p>' . nl2br(check_plain($data->body)) . '</p><p>';
								$out .= t("Posted !date at !time by !author\n", 
										array(	'!date' => date(ssc_var_get('date_med', SSC_DATE_MED), $data->created),
												'!time' => date(ssc_var_get('time_short', SSC_TIME_SHORT), $data->created),
												'!author' => 
											(empty($data->site) ? $data->author : l($data->author, $data->site)))) . '</p><hr />';
							}
						}
						

					}
					
					$out .= ssc_generate_form('blog_guest_comment', $pid);
				}
				return $out;
			}
			elseif(isset($_GET['param'][0])){
				// First param set not expecting anything - kill page
				ssc_not_found();
				return;
			} else {
				// Yearly archive
				return _blog_gen_post(10000, $_GET['path'] . '/page/', 
					"SELECT p.id, p.title, p.created, p.urltext, u.displayname author, count(c.post_id) count FROM #__blog_post p LEFT JOIN
					#__blog_comment c ON (post_id = p.id AND (c.status & %d = 0)) LEFT JOIN #__user u ON u.id = p.author_id WHERE blog_id = %d 
					AND p.created >= %d AND p.created < %d GROUP BY p.id ORDER BY p.created DESC",
					SSC_BLOG_SPAM, $_GET['path-id'], mktime(0, 0, 0, 1, 1, $action), mktime(0, 0, 0, 1, 0, $action + 1));
			}
		}
	}
		
	// Find content
	
	
	ssc_not_found();
}

/**
 * Private function to generate posts
 * @param int $perpage Number of posts per page to show
 * @param string $sql Query to retrieve the posts we want
 * @param mixed $args,... Query arguments
 * @return string Generated output
 */
function _blog_gen_post($perpage, $pagelink, $sql, $args = null){
	global $ssc_database;
	
	$args = func_get_args();
	$perpage = array_shift($args);
	$pagelink = array_shift($args);
	
	if (array_shift($_GET['param']) == 'page'){
		$page = array_shift($_GET['param']);
		if ((int)$page <= 0){
			$page = 1;
		}
	}
	else{
		$page = 1; 
	}
	
	array_unshift($args, $page, $perpage);
	
	$paged_result = call_user_func_array(array($ssc_database, 'query_paged') , $args);
	$result =& $paged_result['result'];
	$out = '';
	// For each blog post listed
	while (($data = $ssc_database->fetch_object($result)) && ($perpage-- > 0)){
		$posturl = $_GET['path'] . date("/Y/m/d/", $data->created) . $data->urltext;
		$out .= "\n<h3>" . l($data->title, $posturl) . "</h3>\n";
		$out .= t("Posted !date at !time by !author\n", 
			array(	'!date' => date(ssc_var_get('date_med', SSC_DATE_MED), $data->created),
					'!time' => date(ssc_var_get('time_short', SSC_TIME_SHORT), $data->created),
					'!author' => $data->author));

		// Get post tags
		$r = $ssc_database->query("SELECT tag FROM #__blog_relation r, #__blog_tag t WHERE r.tag_id = t.id AND r.post_id = %d ORDER BY tag ASC", $data->id);
		if ($ssc_database->number_rows()){
			$out .= "<br />Tagged: ";
			$txt = '';
			while($dat = $ssc_database->fetch_object($r))
				$txt .= ', ' . l($dat->tag, $_GET['path'] . '/tag/' . $dat->tag);
			
			$txt  = substr($txt, 2);
			$out .= $txt;
		}
		
		// Comments if listed
		if ($_GET['blog_comments'] == true){
			// Either show number or "Add One!" links direct to comments
			if ($data->count == 0){
				$out .= '<br />' . t("No comments - !action\n", array('!action' => l(t('Add one!'), $posturl . "#comments")));
			}
			else{
				$out .= '<br />' . l($data->count . ' comments', $posturl . "#comments");
			}
		}
		
		if (isset($data->body))
			$out .= '<br />' . sscText::convert($data->body) . '<hr class="clear"/>';
		else
			$out .= '<hr />';
		
	}
	
	// Page navigation
	$out .= '<div class="paging"><span>';
	// Is there a previous page?
	if ($page > 1)
		$out .= l(t('Previous page'), $pagelink . ($page - 1));

	$out .= '</span> <span><img src="/images/rss.png" alt="Subscribe using" /> ' . l(t('ATOM 1.0'), $_GET['path'] . '/feed') . '</span> <span>';

	// Next page?
	if ($paged_result['next'])
		$out .= l(t('Next page'), $pagelink . ($page + 1));
		
	$out .= '</span></div>';

	return $out;
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
			$data->url = '';
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


	$form['tags'] = array(	'#type' => 'fieldset',
							'#title' => t('Tagging'),
							'#parent' => true);
	$fieldset =& $form['tags'];


	// List of tags
	$res = $ssc_database->query("SELECT id, tag FROM #__blog_tag ORDER BY tag ASC");
	// List of connected tags
	$result = $ssc_database->query("SELECT tag FROM #__blog_relation LEFT JOIN #__blog_tag t ON tag_id = t.id WHERE post_id = %d ORDER BY tag ASC", $data->id);
	$dat = $ssc_database->fetch_assoc($result);
	
	// Loop through all available tags
	while($datb = $ssc_database->fetch_assoc($res)){
		$i = strcmp($datb['tag'], $dat['tag']);
		// If tag is connected ... 
		if ($i == 0){
			// ... add it to list as checked
			$fieldset["tid[$datb[id]]"] = array('#type' => 'checkbox', '#title' => $datb['tag'], '#id' => 'tid' . $datb['id'], '#value' => $datb['id'], '#checked' => true);
			// and move to next in connected list
			$dat = $ssc_database->fetch_assoc($result);
		}
		else {
			// ... else add it as uncheck
			$fieldset["tid[$datb[id]]"] = array('#type' => 'checkbox', '#title' => $datb['tag'], '#id' => 'tid' . $datb['id'], '#value' => $datb['id'], '#checked' => false);
		}
		
		while($i > 0 && $dat['tag'] != ''){
			// Uh...?
			$dat = $ssc_database->fetch_assoc($result);
			$i = strcmp($datb['tag'], $dat['tag']);
		}

/*
			echo '<div><label for="tid',$data['id'],'">',$data['tag'],'</label><input type="checkbox" name="tid[]" id="tid',$data['id'],'" value="',$data['id'],'" ';
			$i = strcmp($data['tag'],$dat['tag']);
			if($i == 0){
				echo 'checked="checked" ';
				$dat = $database->getAssoc();
			}
			while($i>0 && $dat['tag'] != ''){
		
				$dat = $database->getAssoc();
				$i = strcmp($data['tag'],$dat['tag']);
			}
				
			echo '/></div>';
*/

		}
	
	
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
	$tmp = str_replace(array("?", "%", "!", "@", "#", "$", '`', "~", "^", "&", "*", "\"", "'", ",", "."), "", $tmp);
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

	if (isset($_POST['prev'])){
		ssc_add_message(SSC_MSG_INFO, "Below is a preview of your post.  Nothing has been saved yet.");
		return;	
	}

	$require_redir = false;
	if ($id == 0){
		// Insert

		$result = $ssc_database->query("INSERT INTO #__blog_post (blog_id, title, created, modified, body, urltext, author_id) VALUES (%d, '%s', %d, %d, '%s', '%s', %d)",
			 $blog, $_POST['title'], time(), time(), $_POST['body'], $_POST['url'], $ssc_user->id);
		$_POST['id'] = $id = $ssc_database->last_id();
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into DB');
			return;
		}
		$require_redir = true;
		module_hook('mod_blog_post_publish', null, array($id, '', t($_POST['title'])));
	}
	else{
		// Update
		$ssc_database->query("UPDATE #__blog_post b SET title = '%s', body = '%s', urltext = '%s', modified = %d WHERE id = %d AND blog_id = %d", 
				$_POST['title'], $_POST['body'], $_POST['url'], time(), $id, $blog);
		module_hook('mod_blog_post_update', null, array($id, '', t($_POST['title'])));
	}

	// Tags
	$result = $ssc_database->query("SELECT tag_id FROM #__blog_relation WHERE post_id = %d", $id);
	$exist = array();
	
	// Retrieve existing list of tags
	while ($data = $ssc_database->fetch_assoc($result))
		$exist[] = $data['tag_id'];

	$exist = ','.implode(',',$exist).',';
	if(isset($_POST['tid'])){
		$tID = $_POST['tid'];
		// Loop through each tag id
		foreach($tID as $key => $value){
			$key = (int)$key;
			
			if($key > 0 && strpos($exist,','.$key.',') === false){
				// If not present already, add to the relation table
				$ssc_database->query("INSERT INTO #__blog_relation (post_id, tag_id) VALUES (%d, %d)", $id, $key);
			}else{
				// Else, it's already there so don't need to add. 
				// Remove from todelete list
				$exist = str_replace(','.$key,'',$exist);
			}
		}
	}
	$exist = explode(',',$exist);
	$total = count($exist);
	for($i = 0; $i < $total; $i++){
		ssc_add_message(SSC_MSG_INFO, "tag cull list: " . $tID . ", " . intval($exist[$i]));
		if($tID = intval($exist[$i])){
			$ssc_database->query("DELETE FROM #__blog_relation WHERE post_id = %d AND tag_id = %d LIMIT 1",$id,$tID);
		}
	}

	if ($require_redir)
		ssc_redirect("/admin/blog/edit/$blog/post/$id");
		
	ssc_add_message(SSC_MSG_INFO, t('Post saved'));
}

/**
 * Comments form for guests or users posting comments in posts
 */
function blog_guest_comment($pid){
	global $ssc_user, $ssc_site_url;
	
	// Retrieve visitor details
	if (isset($_POST['form-id']) && $_POST['form-id'] == 'blog_guest_comment'){
		// Get from POST
		$details =& $_POST;
		// Check for missing fields
		if (!isset($_POST['n']))
			$_POST['n'] = '';
		
		if (!isset($_POST['s']))
			$_POST['s'] = '';
		
		if (!isset($_POST['e']))
			$_POST['e'] = '';
		
		if (!isset($_POST['c']))
			$_POST['c'] = '';
	}
	else {
		// Try in cookie
		if (!empty($_COOKIE['comment_details'])){
			$details = unserialize($_COOKIE['comment_details']);
			if (!empty($details['save']) && $details['save'] == 1 && isset($details['n'], $details['e'])){
				// In case site not set
				if (!isset($details['s']))
					$details['s'] = '';
					
			}
			else{
				$details['n'] = '';
				$details['s'] = '';
				$details['e'] = '';
			}
		}
		else{
			$details['n'] = '';
			$details['s'] = '';
			$details['e'] = '';
		}
		$details['c'] = '';
	}
	$form = array('#method' => 'post', '#action' => '');
	$form['comment'] = array(	'#parent' => true,
								'#type' => 'fieldset',
								'#title' => t('Post a comment'));
	$fieldset =& $form['comment'];
	
	$fieldset['n'] = array(	'#title' => t('Name'),
							'#description' => t('Name to list as author of the comment.'),
							'#required' => true,
							'#value' => $details['n'],
							'#type' => 'text');
	$fieldset['e'] = array(	'#title' => t('Email'),
							'#description' => t('Your email.  Will be kept private.'),
							'#required' => true,
							'#value' => $details['e'],
							'#type' => 'text');
	$fieldset['s'] = array(	'#title' => t('Website'),
							'#description' => t('Website to link your name to.  Optional.'),
							'#value' => $details['s'],
							'#type' => 'text');
	$fieldset['c'] = array(	'#title' => t('Comment'),
							'#description' => t('Plain text only - no HTML markup'),
							'#required' => true,
							'#value' => $details['c'],
							'#type' => 'textarea');
	$fieldset['i'] = array(	'#type' => 'hidden',
							'#value' => $pid);
	$fieldset['perma'] = array(	'#type' => 'hidden',
								'#value' => $ssc_site_url . '/' . $_GET['path'] . "/id/$pid");
	$fieldset['sub'] = array(	'#type' => 'submit',
								'#value' => t('Submit comment'));
	return $form;
}

/**
 * Comment validation
 */
function blog_guest_comment_validate(){
	global $ssc_site_url;
	if (strpos($_POST['perma'], $ssc_site_url) !== 0 || empty($_POST['i']))
		return false;

	// Validate website
	if (empty($_POST['s'])){
		$_POST['s'] = '';
	}
	elseif (strpos($_POST['s'], "http") !== 0){
		// Either http:// not present or bad URL
		if (strpos($_POST['s'], ":") > 0){
			ssc_add_message(SSC_MSG_WARN, t('The website you entered appears invalid and has been removed from your comment'));
			$_POST['s'] = '';
		}
		else{
			// No protocol so prefix with one
			$_POST['s'] = 'http://' . $_POST['s'];
		}
	}

	if (empty($_POST['n']) || empty($_POST['e']) || empty($_POST['c'])){
		ssc_add_message(SSC_MSG_CRIT, t('You need to fill in all the required fields'));
		return false;
	}

	$email = filter_var($_POST['e'], FILTER_VALIDATE_EMAIL);
	if (empty($email) || !$email || strpos($email, "\n") !== false || strpos($email, ":") !== false){
		ssc_add_message(SSC_MSG_CRIT, t('The email address provided was invalid'));
		return false;
	}
	
	return true;
}

/**
 * Comment submission
 */
function blog_guest_comment_submit(){
	global $ssc_database, $ssc_site_url;
	
	$details['n'] = $_POST['n'];
	$details['s'] = $_POST['s'];
	$details['e'] = $_POST['e'];
	ssc_cookie('comment_details', serialize($details), 15552000);

		// Load antispam
	if (ssc_load_library('sscAkismet')){
		$spam = new sscAkismet($ssc_site_url, ssc_var_get('wordpress_api', ''));
		if (!$spam){
			// No API key - submit but mark for moderation
			$is_spam = SSC_BLOG_SPAM;
		}
		else{
			$spam->setContent($_POST['c'], 'comment');
			$spam->setAuthor($_POST['n'], $_POST['e'], $_POST['s']);
			$spam->setRemote($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
			$spam->setBlog($_POST['perma']);
			$is_spam = ($spam->isSpam() ? SSC_BLOG_SPAM | SSC_BLOG_CAN_SPAM : SSC_BLOG_CAN_SPAM);
			// Increment caught count
			if ($is_spam & SSC_BLOG_SPAM)
				ssc_var_set('akismet_count',(int)ssc_var_get('akismet_count',1) + 1);
		}
	}
	else{
		// No Akismet library - submit but mark for moderation
		$is_spam = SSC_BLOG_SPAM;
	}
	
	$result = $ssc_database->query("INSERT INTO #__blog_comment (post_id, author, email, site, created, status, body, ip)
		VALUES (%d, '%s', '%s', '%s', %d, %d, '%s', '%s')", 
		$_POST['i'], $_POST['n'], $_POST['e'], $_POST['s'], time(), $is_spam, $_POST['c'], $_SERVER['REMOTE_ADDR']);
		
	// Result tree
	if ($result){
		// Submission successful
		if ($is_spam & SSC_BLOG_SPAM){
			// Comment was marked as spam
			if ($is_spam & SSC_BLOG_CAN_SPAM){
				// ... by Akismet
				ssc_add_message(SSC_MSG_WARN, t('Your comment has been submitted but marked as spam and queued for moderation.  Do not resubmit your comment.'));
			}
			else{
				// Akisment unavailable - manual moderation
				ssc_add_message(SSC_MSG_INFO, t('Your comment has been submitted and queued for moderation.  Do not resubmit as it should be checked soon.'));
			}
		}
		else{
			ssc_add_message(SSC_MSG_INFO, t('Your comment was successfully added'));
		}
	}
	else{
		ssc_add_message(SSC_MSG_CRIT, t('There was a server error encountered while submitting your comment'));
	}
}

/**
 * Comment moderation form validation
 */
function blog_spam_ham_validate(){
	if (!login_check_auth("blog"))
		return false;
		
	$count = 0;
	if (isset($_POST['spam'])){
		$_POST['action'] = 'spam';
		$keys = array_keys($_POST['spam']);
		if (count($keys) > 1)
			return false;
		$count++;
	}
	
	if (isset($_POST['ham'])){
		$_POST['action'] = 'ham';
		$keys = array_keys($_POST['ham']);
		if (count($keys) > 1)
			return false;
		$count++;
	}
		
	if (isset($_POST['show'])){
		$_POST['action'] = 'show';
		$keys = array_keys($_POST['show']);
		if (count($keys) > 1)
			return false;
		$count++;
	}
		
	if (isset($_POST['hide'])){
		$_POST['action'] = 'hide';
		$keys = array_keys($_POST['hide']);
		if (count($keys) > 1)
			return false;
		$count++;
	}
		
	if ($count != 1)
		return false;
		
	$_POST['i'] = $keys[0];
		
	return true;
}

/**
 * Comment moderation submission
 */
function blog_spam_ham_submit(){
	global $ssc_database, $ssc_site_url;
	
	$result = $ssc_database->query("SELECT author, email, site, body, status, ip FROM #__blog_comment WHERE id = %d LIMIT 1", $_POST['i']);
	// Bad sql or comment doesn't exist
	if (!$result || !($data = $ssc_database->fetch_object($result)))
		return;
		
	if (($_POST['action'] == 'spam') && ($data->status & SSC_BLOG_CAN_SPAM) > 0){
		// Marking as spam + Akismet submit
		if (ssc_load_library('sscAkismet')){
			$spam = new sscAkismet($ssc_site_url, ssc_var_get('wordpress_api', ''));
			if ($spam){
				$spam->setContent($data->body, 'comment');
				$spam->setAuthor($data->author, $data->email, $data->site);
				$spam->setRemote($data->ip, null);
				$spam->markIncorrect('markSpam');
			}
		}
	}
	elseif (($_POST['action'] == 'ham') && ($data->status & SSC_BLOG_CAN_SPAM) > 0){
		// Mark not spam + Akismet submit
		if (ssc_load_library('sscAkismet')){
			$spam = new sscAkismet($ssc_site_url, ssc_var_get('wordpress_api', ''));
			if ($spam){
				$spam->setContent($_POST['c'], 'comment');
				$spam->setAuthor($_POST['n'], $_POST['e'], $_POST['s']);
				$spam->setRemote($data->ip, null);
				$spam->markIncorrect('markHam');
			}
		}
	}
	
	$data->status = $data->status & ~SSC_BLOG_CAN_SPAM;
	switch ($_POST['action']){
	case 'spam':
	case 'hide':
		$data->status = $data->status | SSC_BLOG_SPAM;
		$ssc_database->query("UPDATE #__blog_comment SET status = %d WHERE id = %d", $data->status, $_POST['i']);
		break;
		
	case 'show':
	case 'ham':
		$data->status = $data->status & ~SSC_BLOG_SPAM;
		$ssc_database->query("UPDATE #__blog_comment SET status = %d WHERE id = %d", $data->status, $_POST['i']);
		break;
	}
	
}