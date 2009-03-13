<?php
/**
 * Events modules
 * @package SSC
 * @subpackage Module
 */ 
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * Flag to show URI link
 */
define('SSC_EVENTS_SHOW_URI', 1);

/**
 * Implementation of module_admin
 */
function events_admin(){
	global $ssc_database;
	// Check what we're doing
	$action = array_shift($_GET['param']);
	switch ($action){
		case 'edit':
			// Editing a particular event
			$out = ssc_generate_form('events_edit');
			break;
			
		case 'page':
			// Allow for paging
			array_unshift($_GET['param'], 'page');
			
		case '':
			$out = ssc_admin_table(t('Event list'), 
				"SELECT id, title, date, description, uri FROM #__events ORDER BY date DESC",
				null, array('perpage' => 20, 'link' => 'title', 'linkpath' => '/admin/events/edit/'));
			$out .= l(t('New event'), '/admin/events/edit/0');
			break;
			
		default:
			ssc_not_found();
			$out = '';

	}
	
	return $out;
}

function events_widget($title, $lowerStr, $upperStr){
	global $ssc_database;
	// Use static array to avoid odd cases with items either skipped or in both sides when borderline
	static $dates = array();
		
	if (isset($dates[$lowerStr]))
		$lower = $dates[$lowerStr];
	else{
		$lower = date("Y-m-d", strtotime($lowerStr));
		$dates[$lowerStr] = $lower;
	}
	
	if (isset($dates[$upperStr]))
		$upper = $dates[$upperStr];
	else{
		$upper = date("Y-m-d", strtotime($upperStr));
		$dates[$upperStr] = $upper;
	}
	
	$result = $ssc_database->query("SELECT title, description, uri, date, flags FROM #__events WHERE date > '%s' AND date < '%s' ORDER BY date ASC", $lower, $upper);
	if (!$result)
		return NULL;
		
	$ret = '<ul class="events-list">';
	while ($data = $ssc_database->fetch_assoc($result))
		$ret .= _events_print_db_event($data);
		
	$ret .= '</ul>';
	
	return array('body' => $ret, 'title' => $title);
}

/**
 * Display the list of events - implementation of module_content
 * 
 * Only one type of display so don't need to parse any sort of input
 * 
 * @return string Main body content
 */
function events_content(){
	global $ssc_database;

	// Set up the event time borders
	$borders['past'] = date("Y-m-d", strtotime(ssc_var_get('events.recent', '-2 weeks')));
	$borders['current-past'] = date("Y-m-d", strtotime(ssc_var_get('events.current.old', '-1 week')));
	$borders['current-future'] = date("Y-m-d", strtotime(ssc_var_get('events.current.old', '+1 week')));
	$borders['future'] = date("Y-m-d", strtotime(ssc_var_get('events.current.old', '+2 months')));
	
	// Get all events within the range
	$result = $ssc_database->query("SELECT title, description, uri, date, flags FROM #__events WHERE date > '%s' AND date < '%s' ORDER BY date ASC", $borders['past'], $borders['future']);
	if (!$result){
		ssc_not_found();
		return;
	}
	
	ssc_set_title(ssc_var_get('events.title', 'Events'));
	
	// Load first event if possible
	if ($ssc_database->number_rows() > 0)
		$data = $ssc_database->fetch_assoc($result);
	else
		$data = null;

	// And start displaying the results
	$out = '<h3>' . t('Recent events') . '</h3>';
	$in = false;
	while ($data && $data['date'] < $borders['current-past']){
		if (!$in){
			$out .= '<ul class="events-list">';
			$in = true;
		}
		$out .= _events_print_db_event($data);
		$data = $ssc_database->fetch_assoc($result);
	}
	if ($in){
		$out .= '</ul>';
		$in = false;
	}
	else
		$out .= t('There are no recent events');
		
	$out .= '<h3>' . t('Current events') . '</h3>';
	while ($data && $data['date'] < $borders['current-future']){
		if (!$in){
			$out .= '<ul class="events-list">';
			$in = true;
		}
		$out .= _events_print_db_event($data);
		$data = $ssc_database->fetch_assoc($result);
	}
	if ($in){
		$out .= '</ul>';
		$in = false;
	}
	else
		$out .= t('There are no current events');
		
	$out .= '<h3>' . t('Upcoming events') . '</h3>';
				
	while ($data) {
		if (!$in){
			$out .= '<ul class="events-list">';
			$in = true;
		}
		$out .= _events_print_db_event($data);
		$data = $ssc_database->fetch_assoc($result);
	} 
	if ($in)
		$out .= '</ul>';
	else
		$out .= t('There are no upcoming events');
		
	return $out;
}

/**
 * Format an event list entry based on a db row passed in as an array
 * @param array $info Associative array extracted from the events table in the DB
 * @return string HTML representing the list element
 */
function _events_print_db_event(&$info){
	$info['date'] = date("d M Y", strtotime($info['date']));
	
	$out = "<li>$info[date] - ";
	if ($info['flags'] & SSC_EVENTS_SHOW_URI)
		$out .= l($info['title'], $info['uri']);
	else
		$out .= $info['title'];
		
	if ($info['description'] != '')
		$out .= "<br />$info[description]";
		
	return $out . '</li>';
}

/**
 * New event form
 */
function events_edit(){
	global $ssc_site_url, $ssc_database;
	
	// Get event ID
	$id = intval(array_shift($_GET['param']));
	
	// Load information from POST if present
	if (isset($_POST['submit'])){
		$data->id = $id;
		$data->title = isset($_POST['name']) ? $_POST['name'] : '';
		$data->description = isset($_POST['desc']) ? $_POST['desc'] : '';
		$data->uri = isset($_POST['uri']) ? $_POST['uri'] : '';
		$data->flags = isset($_POST['link']) && ((int)$_POST['link'] > 0) ? 1 : 0;
		$data->date = isset($_POST['date']) ? ssc_parse_date($_POST['date']) : '';
	}
	else{
		// Otherwise fill from database
		$result = null;
		if ($id > 0)
			$result = $ssc_database->query("SELECT id, title, description, uri, flags, date FROM #__events WHERE id = %d LIMIT 1", $id);
			
		// If can't get results or new event, fill with blanks
		if ($id == 0 || !($data = $ssc_database->fetch_object($result))){
			$data = new stdClass();
			$data->id = 0;
			$data->title = '';
			$data->description = '';
			$data->uri = '';
			$data->flags = 0;
			$data->date = '';
		}
						
	}
	
	// Build form array structure
	$form = array('#method' => 'post', '#action' => '');
	$form['event'] = array(	'#type' => 'fieldset',
							'#title' => 'Event details',
							'#parent' => true);
							
	$fieldset = &$form['event'];
	
	$fieldset['id'] = array('#type' => 'hidden',
							'#value' => $data->id);
							
	$fieldset['name'] = array(	'#type' => 'text',
								'#title' => t('Event name'),
								'#description' => t('Enter a name for this event'),
								'#required' => true,
								'#value' => $data->title);
							
	$fieldset['date'] = array(	'#type' => 'text',
								'#title' => t('Date'),
								'#description' => t('Date the event occurs.  Can handle relative times (eg, \'tomorrow\' or \'next week\'), or absolute (eg, \'14 Apr 09\' or \'14-04-09\')'),
								'#required' => true,
								'#value' => date('d M Y', strtotime($data->date)));
								
	$fieldset['desc'] = array(	'#type' => 'text',
								'#title' => t('Event description'),
								'#description' => t('Optionally enter some information relating to the event'),
								'#value' => $data->description);
								
	$fieldset['uri'] = array(	'#type'	=> 'text',
								'#title' => t('Event link'),
								'#description' => t('Link where more information is available for the event.  Should exclude \'!site\'', array('!site' => $ssc_site_url . '/')),
								'#value' => $data->uri);
								
	$fieldset['link'] = array(	'#type' => 'checkbox',
								'#title' => t('Show link'),
								'#description' => t('Add the link specified above to the event information displayed to end users'),
								'#value' => 1,
								'#checked' => $data->flags & SSC_EVENTS_SHOW_URI);
								
	$fieldset['submit'] = array('#type' => 'submit',
								'#value' => $id == 0 ? 'Add event' : 'Update event');
							
	return $form;
}

/**
 * Validation routine for event editing
 * @return TRUE or FALSE depending on validation success
 */
function events_edit_validate(){
	// Ensure auth'd people only
	if (!login_check_auth('sailing'))
		return false;
		
	if (!isset($_POST['id'], $_POST['name'], $_POST['date'], $_POST['uri'], $_POST['submit']))
		return false;

	if ($_POST['id'] == '' || $_POST['name'] == '' || $_POST['date'] == ''){
		ssc_add_message(SSC_MSG_CRIT, t('Required fields were not filled in'));
		return false;
	}
	
		
	return true;
}

/**
 * Submit form contents for an event edit to the DB
 */
function events_edit_submit(){
	global $ssc_database;
	
	$id = (int)$_POST['id'];
	if ($id == 0){
		$result = $ssc_database->query("INSERT INTO #__events (title, description, uri, flags, date) VALUES ('%s', '%s', '%s', %d, '%s')",
			$_POST['name'], $_POST['desc'], $_POST['uri'], (isset($_POST['link']) && $_POST['link'] == '1' ? 1 : 0), date("Y-m-d", strtotime(ssc_parse_date($_POST['date']))));
		$id = $ssc_database->last_id();
	}
	else{
		$result = $ssc_database->query("UPDATE #__events SET title = '%s', description = '%s', uri = '%s', flags = %d, date = '%s' WHERE id = %d LIMIT 1",
			$_POST['name'], $_POST['desc'], $_POST['uri'], (isset($_POST['link']) && $_POST['link'] == '1' ? 1 : 0), date("Y-m-d", strtotime(ssc_parse_date($_POST['date']))), $id);
	}
	
	if ($result){
		ssc_add_message(SSC_MSG_INFO, t('Event saved successfully'));
	}
	else{
		ssc_add_message(SSC_MSG_CRIT, t('Event was unable to be saved - ' . $ssc_database->error()));
		return;
	}
		
	if ((int)$_POST['id'] == 0)
		ssc_redirect('/admin/events/edit/' . $id);
}
