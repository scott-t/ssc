<?php
/**
 * Sailing results
 * @package SSC
 * @subpackage Module
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

define("SSC_SAILING_CLASS", 1);
define("SSC_SAILING_PREFIX", 2);
define("SSC_SAILING_CLUB", 4);

/**
 * Implementation of module_admin()
 */
function sailing_admin(){
	global $ssc_database;
	// Work out what we want to do 
	$action = array_shift($_GET['param']);
	switch ($action){
	case 'edit':
		// Edit existing series
		$out = ssc_generate_form('sailing_series');
		break;
		
	case 'page':
		// Allow for paging
		array_unshift($_GET['param'], 'page');

	case 'download':
		$id = array_shift($_GET['param']);
		$csv = _ssc_sailing_get_csv($id);
		if ($csv)
			ssc_custom_data("text/csv", $csv, "series-$id.csv");		
		else
			ssc_not_found();
		break;
	

	case '':
		$out = ssc_admin_table(t('Regattas/Series'), 
			"SELECT s.id, name, description, path FROM #__sailing_series s 
			LEFT JOIN #__handler h ON h.id = s.id ORDER BY path ASC",
			null,
			array('perpage' => 10, 'pagelink' => '/admin/sailing/page/', 'link' => 'name', 'linkpath' => '/admin/sailing/edit/',
				'customheads' => array('Download CSV'),'customcols' => array('Download' => '/admin/sailing/download/')));
		$out .= l(t('New series'),'/admin/sailing/edit/0');
		
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
 * Results content.  At this stage, no arguments so present results for entire regatta. Bracket refers to mouse-over
 * 
 *   - /
 *     No parameters.  Should show abbr'd |sail|class|name|skip (crew)|place(corr. time)[|place(corr. time)...]
 * 
 *   - /heat/<num>   or    /heat-<num
 *     Show detailed version for a heat perhaps?  Probably not feasable.
 */
function sailing_content(){
	global $ssc_database;
	
	ssc_add_js('/modules/sailing/sailing.js');
	
	// See if results exist
	$result = $ssc_database->query("SELECT name, description, updated, flags, heats FROM #__sailing_series WHERE id = %d LIMIT 1", $_GET['path-id']);
	if (!($result && $data = $ssc_database->fetch_assoc($result))){
		ssc_not_found();
		return;
	}
	
	// Set up some flags
	$flags = $data['flags'];
	$prefix = ($flags & SSC_SAILING_PREFIX ? "Division " : "");
	$show_class = ($flags & SSC_SAILING_CLASS) > 0;
	$show_club = ($flags & SSC_SAILING_CLUB) > 0;
	
	// Heat numbers
	$heats = explode(",", $data['heats']);
	
	// Description / title
	ssc_set_title($data['name']);
	$out = ""; 
	if (strlen($data['description']) > 0){
		if (!ssc_load_library('sscText'))
			$out .= check_plain($data['description']);
		else
			$out .= sscText::convert($data['description']);
	}

	// Prepare for table
	$result = $ssc_database->query("SELECT r.results, r.times, r.points, r.division, e.number, e.skipper, e.crew, e.name AS boatname, e.class, e.club FROM #__sailing_results r LEFT JOIN #__sailing_entries e ON e.id = r.uid WHERE r.series_id = %d ORDER BY r.division ASC, r.points ASC", $_GET['path-id']);
	if (!$result || $ssc_database->number_rows() < 1){
		// Empty or sql failure
		$out .= "There are no race results available for this series yet";
		return $out;
	}
	else
	{
		// Start outputting
		$out .= '<table class="sail-table" summary="Race results">';
		$col_header = _ssc_sailing_table_header($flags, $heats, $col_count);
		
		// Loop through results
		$div = '-1';
		while ($data = $ssc_database->fetch_assoc($result)) {

			// Re-echo headers for each division
			if ($div != $data['division']){
				if ($div == '-1'){
					$out .= "<thead><tr><th class=\"div-heading\" colspan=\"$col_count[total]\">$prefix$data[division]</th></tr>";
					$out .= "$col_header</thead><tbody>";
				}
				else {
					$out .= '<tr><th class="div-heading" colspan="' . $col_count['total'] . '">' . $prefix . $data['division'] . '</th></tr>';
					$out .= $col_header;
				}
					
				$div = $data['division'];
			}
			
			// Row contents
			$out .= "<tr><td>$data[number]</td>" . ($show_class ? "<td>$data[class]</td>" : '') . "<td>$data[boatname]</td>";
			if ($data['crew'] != '')
				$out .= "<td><span title=\"$data[crew]\">$data[skipper]</span></td>";
			else
				$out .= "<td>$data[skipper]</td>";
				
			if ($show_club){
				$out .= "<td>$data[club]</td>";
			}
			
			// Parse results
			$heats = explode(",", $data['results']);
			$times = explode(",", $data['times']);
			for ($i = 0; $i < $col_count['heats']; $i++){
				if ($times[$i] != ''){
					if ((float)($times[$i]) > 0)
						$out .= '<td><span title="' . sprintf("%1.1f", (float)($times[$i])) . " min\">$heats[$i]</span></td>";
					else
						$out .= "<td><span title=\"$times[$i]\">$heats[$i]</span></td>";
				}
				else
					$out .= "<td>$heats[$i]</td>";
			}
			
			$out .= '</tr>';
		}
		
		// Tidy up
		$out .= '</tbody></table>';
	}
		
	return $out;
}

/**
 * Generate the result table column headers
 * @return 
 * @param int $flags Object
 * @param array $heats Object
 */
function _ssc_sailing_table_header($flags, $heats, &$col_header){
	$out = "<tr><th>" . ssc_abbr("Sail No", "Sail Number") . "</th>";
	$col_header = array('id' => 1, 'details' => 2, 'heats' => 0);
	if ($flags & SSC_SAILING_CLASS) {
		$out .= "<th>Class</th>";
		$col_header['details']++;
	}
	$out .= "<th>Boat Name</th>";
	$out .= "<th>Skipper</th>"; 

	if ($flags & SSC_SAILING_CLUB) {
		$out .= "<th>Club</th>";
		$col_header['details']++;
	}
	
	$col_header['heats'] = count($heats);	
	while ($heat = array_shift($heats)){
		$out .= "<th>" . ssc_abbr("R" . $heat, "Race " . $heat) . "</th>";
	}
	$col_header['total'] = array_sum($col_header);
	return $out . '</tr>';
}

function sailing_series(){
	global $ssc_site_url, $ssc_database;

	if (isset($_POST['form-id']) && $_POST['form-id'] == 'sailing_series'){
		// populate from post
		$data = new stdClass();
		$data->id = isset($_GET['param'][0]) ? $_GET['param'][0] : 0;
		$data->name = isset($_POST['name']) ? $_POST['name'] : '';
		$data->description = isset($_POST['desc']) ? $_POST['desc'] : '';
		// Bitflags
		$data->flags = 0;
		if (isset($_POST['class']) && (int)$_POST['class'] == 1)
			$data->flags |= SSC_SAILING_CLASS;
	
		if (isset($_POST['club']) && (int)$_POST['club'] == 1)
			$data->flags |= SSC_SAILING_CLUB;
			
		if (isset($_POST['div']) && (int)$_POST['div'] == 1)
			$data->flags |= SSC_SAILING_PREFIX;
			
		$data->path = isset($_POST['url']) ? $_POST['url'] : 'results';
	}
	else
	{
		$result = $ssc_database->query("SELECT s.id, name, description, flags, path FROM #__sailing_series s LEFT JOIN #__handler h ON s.id = h.id WHERE s.id = %d LIMIT 1", $_GET['param'][0]);
		if (!($data = $ssc_database->fetch_object($result))){
			$data = new stdClass();
			$data->id = 0;
			$data->name = '';
			$data->description = '';
			$data->flags = 0;
			$data->path = 'results';
		}

		// grab from db
	}
	
	$form = array('#method' => 'post', '#action' => '', '#attributes' => array('enctype' => 'multipart/form-data'));
	$form['details'] = array(	'#type' => 'fieldset', 
								'#title' => 'Series details',
								'#parent' => true);
								
	$fieldset = &$form['details'];
	
	$fieldset['id'] = array('#type' => 'hidden',
							'#value' => $data->id);
							
	$fieldset['name'] = array(	'#type' => 'text',
								'#value' => $data->name,
								'#title' => t('Series name'),
								'#description' => t('Enter a name for this series or regatta'),
								'#required' => true);
	$fieldset['url'] = array(	'#type' => 'text',
								'#value' => $data->path,
								'#title' => t('Path to page'),
								'#required' => true,
								'#description' => t('Path that should be used to access the page.  Should exclude \'!site\'', array('!site' => $ssc_site_url . '/')));
	$fieldset['desc'] = array(	'#type' => 'textarea',
								'#value' => $data->description,
								'#title' => t('Series description'),
								'#description' => t('Enter a description for this series.  sscText is allowed.'));
	$fieldset['class'] = array(	'#type' => 'checkbox',
								'#title' => t('Show class'),
								'#description' => t('Show the class of each boat in this series'),
								'#value' => 1,
								'#checked' => $data->flags & SSC_SAILING_CLASS);
	$fieldset['club'] = array(	'#type' => 'checkbox',
								'#title' => t('Show club'),
								'#description' => t('Show the home club of each boat in this series'),
								'#value' => 1,
								'#checked' => $data->flags & SSC_SAILING_CLUB);
	$fieldset['div'] = array(	'#type' => 'checkbox',
								'#title' => t('Show division prefix'),
								'#description' => t('Show the \'Division\' prefix before the division field contents.  Check this box if you have a value such as \'C\' or \'2\' and would like the results to show \'Division C\' for example.'),
								'#value' => 1,
								'#checked' => $data->flags & SSC_SAILING_PREFIX);

	$fieldset['update'] = array('#type' => 'file',
								'#title' => t('Upload results'),
								'#description' => t('Upload results for this series.  This will replace any that may already exist'));
								
	$fieldset['submit'] = array('#type' => 'submit',
								'#value' => t('Save series'));
	return $form;
}

function sailing_series_validate(){
	if (!isset($_POST['id'], $_POST['name'], $_POST['submit'], $_POST['url']))
		return false;		// missing compulsory fields - drop quietly
		
	if (!login_check_auth('sailing'))
		return false;
	
	if (strlen($_POST['name']) == 0){
		ssc_add_message(SSC_MSG_CRIT, t('Series must have a name'));
		return false;	
	}
	
	return true;
}

function sailing_series_submit(){
	global $ssc_database;
	
	// Get id number
	$id = (int)($_POST['id']);

	// Bitflags
	$flags = 0;
	if (isset($_POST['class']) && (int)$_POST['class'] == 1)
		$flags |= SSC_SAILING_CLASS;

	if (isset($_POST['club']) && (int)$_POST['club'] == 1)
		$flags |= SSC_SAILING_CLUB;
		
	if (isset($_POST['div']) && (int)$_POST['div'] == 1)
		$flags |= SSC_SAILING_PREFIX;
		
	if ($id == 0){
		// Inserting fresh
		$result = $ssc_database->query("INSERT INTO #__handler (path, handler) VALUES ('%s', %d)", $_POST['url'], module_id('sailing'));
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into db');
			return false;
		}

		$id = $ssc_database->last_id();
		$result = $ssc_database->query("INSERT INTO #__sailing_series (id, name, description, updated, flags, heats) VALUES (%d, '%s', '%s', 0, %d, '')", $id, $_POST['name'], $_POST['desc'], $flags);
		if (!$result){
			ssc_add_message(SSC_MSG_CRIT, 'Error inserting into db');
			return false;
		}		
	}
	else
	{
		// Update existing
		$ssc_database->query("UPDATE #__handler SET path = '%s' WHERE id = %d LIMIT 1", $_POST['url'], $id);
		$ssc_database->query("UPDATE #__sailing_series SET name = '%s', description = '%s', flags = %d WHERE id = %d LIMIT 1", $_POST['name'], $_POST['desc'], $flags, $id);
	}

	if (isset($_FILES['update']['name'])){
		switch ($_FILES['update']['error']){
			case UPLOAD_ERR_OK:
				if (!_ssc_sailing_parse_csv($id)){
					ssc_add_message(SSC_MSG_CRIT, t('Unable to update race results'));
				}
				else
				{
					ssc_add_message(SSC_MSG_INFO, t('Regatta details and heats updated successfully'));
				}
				unlink($_FILES['update']['tmp_name']);
				break;
				
			case UPLOAD_ERR_NO_FILE:
				// No file, but other details should be saved
				ssc_add_message(SSC_MSG_INFO, t("Regatta details updated successfully"));
				break;
				
			default:
				ssc_add_message(SSC_MSG_ERROR, t('Unknown file upload error: !num', array('!num' => $_FILES['update']['error'])));
				break;
		}
	}
	
	
	if ((int)$_POST['id'] == 0)
		ssc_redirect('/admin/sailing/edit/' . $id);
}

/**
 * Parse an uploaded CSV file referred to via a form name of 'update'
 * @param object $id ID number of the sailing series/regatta in the DB
 * @return boolean TRUE on successful parse, false otherwise
 */
function _ssc_sailing_parse_csv($id){
	global $ssc_database;
	
	// Open the uploaded file
	$tmpFile = $_FILES['update']['tmp_name'];
	$f = fopen($tmpFile, 'r');
	$line[0] = '';
	
	// Skip empty rows
	do{
		$line = fgetcsv($f, 1024, ',');
		if ($line == false)
			return false;
	} while ($line[0] == '');
    
	// CSV content flags
	$hasClass = false;
	$hasClub = false;
	$hasCrew = false;
	$hasFinal = false;
	
	// Parse column headers
	$count = count($line);
	$fields = array();
	
	// Work out which columns have which data
	for ($i = 0; $i < $count; $i++){
		switch (strtolower($line[$i])){
			case 'sail no':
			case 'sail no.':
			case 'sail number':
			case 'sail':
				$fields['sail'] = $i;
				break;
				
			case 'div':
			case 'division':
			case 'div.':
				$fields['div'] = $i;
				break;
				
			case 'skipper':
			case 'sailor':
				$fields['skipper'] = $i;
				break;
				
			case 'name':
			case 'boat':
			case 'boat name':
				$fields['boat'] = $i;
				break;
				
			case 'class':
			case 'type':
				$fields['class'] = $i;
				$hasClass = true;
				break;
				
			case 'club':
			case 'origin':
				$fields['club'] = $i;
				$hasClub = true;
				break;
				
			case 'crew':
				$fields['crew'] = $i;
				$hasCrew = true;
				break;
				
			case 'final':
			case 'rank':
			case 'place':
			case 'final ranking':
				$fields['final'] = $i;
				$hasFinal = true;
				break;
				
			default:
				$race = intval($line[$i]);
				if ($race > 0)
					$fields['result'][$i] = $race;
					
				break;
				
		}
	}

	// Map heat numbers to columns
	$heats = array();
	foreach ($fields['result'] as $col => $heat){
		$heats[$heat] = $col;
	}
	
	// Check for missing columns...
	$missing = '';
	$crit = false;
	
	// First the critical ones
	$crits = array ('sail' => 'Sail No.', 'div' => 'Division', 'skipper' => 'Skipper',
					'boat' => 'Boat Name', 'result' => 'One or more result');
	foreach ($crits as $key => $val){
		if (!isset($fields[$key])){
			$missing .= $val . ', ';
			// Die now, can't proceed to parsing
			$crit = true;
		}
	}
	
	// Non-critical columns (either not being shown, not relative to some boat types, or calculate ourselves)
	$crits = array ('club' => 'Club', 'class' => 'Class', 'crew' => 'Crew', 'final' => 'Final ranking');
	foreach ($crits as $key => $val){
		if (!isset($fields[$key])){
			$missing .= $val . ', ';
		}
	}
	
	// Complain if some missing
	if (strlen($missing) > 0){
		if ($crit){
			ssc_add_message(SSC_MSG_CRIT, 
				t('There were some missing fields detected: !missing.  ' .
				'Some of these were required, so results have NOT been updated',
				 array('!missing' => substr($missing, 0, -2))));
				 return false;
		}
		else
		{
			ssc_add_message(SSC_MSG_WARN, 
				t('There were some missing fields detected: !missing.  ' .
				'These are optional fields, so if you omitted these fields, disregard this message',
				 array('!missing' => substr($missing, 0, -2))));
		}
	}
	
	// Define the optional missing columns so we have somewhere to store empty data later on
	if (!$hasClub)
		$fields['club'] = ++$count;
		
	if (!$hasClass)
		$fields['class'] = ++$count;
		
	if (!$hasCrew)
		$fields['crew'] = ++$count;
		
	if (!$hasFinal)
		$fields['final'] = ++$count;
	
	
	// Start parse of csv contents by moving everything into a nicely formatted array
	
	// First, get all the currently existing entrants
	$result = $ssc_database->query("SELECT id, series_id, division, number, uid FROM #__sailing_results WHERE series_id = %d", $id);
	$series_results = array();
	$div_count = array();
	if ($ssc_database->number_rows() > 0){
		while ($data = $ssc_database->fetch_assoc($result)){
			$series_results[$data['uid']] = $data;
		}
	}

	$valid_nonval = array("DNS", "DNC", "DSQ", "DNF");

	// Now loop through the CSV to update as necessary from updated information
	while (($line = fgetcsv($f, 1024, ","))){
		$count = count($line);

		if ($count == 1)
			continue;
			
		if (!$hasClub)
			$line[$fields['club']] = '';
			
		if (!$hasCrew)
			$line[$fields['crew']] = '';
			
		if (!$hasClass)
			$line[$fields['class']] = '';
			
		// Find a boat to match the result
		$sail = $ssc_database->query("SELECT id FROM #__sailing_entries WHERE number = '%s' AND class = '%s' AND name = '%s' AND skipper = '%s' AND crew = '%s' AND club = '%s' LIMIT 1", $line[$fields['sail']], $line[$fields['class']], $line[$fields['boat']], $line[$fields['skipper']], $line[$fields['crew']], $line[$fields['club']]);
		if ($ssc_database->number_rows() == 1){
			$data = $ssc_database->fetch_assoc($sail);
			$sailid = $data['id'];
		}
		else
		{
			// Insert if non-existant
			$sail = $ssc_database->query("INSERT INTO #__sailing_entries (number, class, name, skipper, crew, club) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $line[$fields['sail']], $line[$fields['class']], $line[$fields['boat']], $line[$fields['skipper']], $line[$fields['crew']], $line[$fields['club']]);
			$sailid = $ssc_database->last_id();
		}

		// Store results for each boat
		foreach ($heats as $heat => $col) {
			$missing = $line[$col];
			if ($missing == '') {
				ssc_add_message(SSC_MSG_WARN, t('No result was detected for \'!num\' - assuming DNC', array('!num' => $series_results[$sailid]['sail'])));
				$missing = 'DNC';
			}
			elseif (floatval($missing) != 0){
				$missing = floatval($missing);
			}
			else{
				$missing = strtoupper($missing);
				if (array_search($missing, $valid_nonval) === false){
					ssc_add_message(SSC_MSG_WARN, t('Unknown result for \'!num\': \'#place\'- assuming DNC', array('!num' => $series_results[$sailid]['sail'], '#place' => $missing)));
					$missing = 'DNC';
				}
					
			}

			$series_results[$sailid]['results'][$heat] = $missing;
		}
			
		// Count entrants per div
		$div = $line[$fields['div']];
		$series_results[$sailid]['div'] = $div;
		if (!isset($div_count[$div]))
			$div_count[$div] = 1;
		else
			$div_count[$div]++;
				
		// If final result present, store it
		if ($hasFinal)
			$series_results[$sailid]['final'] = $line[$fields['final']];
			
	}
	
	// Finished reading
	fclose($f);

	// If an empty CSV, return now
	if (count($series_results) == 0)
		return true;

	// Unset/mark-for-free'ing unneeded array(s)
	unset($fields);
	
	// Get all the heat numbers
	$heats = array_keys($heats);	
	$boats = array_keys($series_results);
	
	// For each heat, convert times to rankings
	foreach ($heats as $heat) {
		$result = array();

		// Get results for the heat
		foreach ($boats as $boat){
			$result[$series_results[$boat]['div']][$boat] = $series_results[$boat]['results'][$heat];
		}

		// Sort each div
		array_walk($result, 'asort');
		
		// Get a placing for each time in each div
		foreach ($result as $div => $div_result) {
			if (array_search(1, $div_result) === false){
				// Times
				$count = 0;
				foreach ($div_result as $sail_id => $time) {
					$series_results[$sail_id]['times'][$heat] = $time;
					if ((float)($time) > 0)
						$series_results[$sail_id]['results'][$heat] = ++$count;
				}			
			}
			else {
				// Places - insert blank times
				foreach ($div_result as $sail_id => $time) {
					$series_results[$sail_id]['times'][$heat] = '';
				}
			}
		}
	}
	
	// Work out rough final placings if no final result column given
	if (!$hasFinal){
		$final = array();

		foreach ($boats as $boat){
			// Replace DNC's, et al with rough points gained
			$result = strtoupper(implode(",", $series_results[$boat]['results']));
			if (preg_match("/[[:alpha:]]+/i", $result))
				$result = preg_replace("/[[:alpha:]]+/i","" . ($div_count[$series_results[$boat]['div']] + 1), $result);
				
			// Possible TODO: dropping races, and support for diff scores depending on DNF, DNC, etc
				
			// Get final score (based on low point system)
			$final[$series_results[$boat]['div']][$boat] = array_sum(explode(",", $result));
		}
		
		// Sort per div, then by score
		array_walk($final, 'asort');
		
		// Write out placings in results_div
		foreach ($final as $div => $div_result) {
			$count = 0;
			foreach ($div_result as $boat => $score) {
				$series_results[$boat]['final'] = ++$count;
			}
		}
	}

	$ssc_database->query("UPDATE #__sailing_series SET heats = '%s' WHERE id = %d", implode(',', $heats), $id);

	$ssc_database->query("UPDATE #__sailing_results SET points = 0 WHERE series_id = %d", $id);
	
	// Possible TODO: Optimize this so multiple replaces in one go
	foreach ($series_results as $boat => $results) {
		if (isset($results['id'])){
			$ret = $ssc_database->query(
				"UPDATE #__sailing_results SET uid = %d, series_id = %d, results = '%s', times = '%s', points = %d, division = '%s' WHERE id = %d",
				 		$boat, $id, implode(",", $results['results']), implode(",", $results['times']),
						$results['final'], $results['div'], $results['id']);
		}
		else {
			$ret = $ssc_database->query(
				"INSERT INTO #__sailing_results (uid, series_id, results, times, points, division) VALUES (%d, %d, '%s', '%s', %d, '%s')",
				 		$boat, $id, implode(",", $results['results']), implode(",", $results['times']),
						$results['final'], $results['div']);
		}
	}

	$ssc_database->query("DELETE FROM #__sailing_results WHERE points = 0 AND series_id = %d", $id);
	
	// if there is no final column, then need to convert DNC, DNF, etc to $div_count[$div] 
	// worth of points and tally up for approx rankings
	// could handle race drops, but they can wait.  if they were to be handled, drop the dnf, dns, and maybe dsq only.
	// not sure on exact rules - would need to look.
	
	
	/* At this point, arrays are as following:
	 * - $series_results = array(<boat_id> => 
	 * 							array("results" => array (<heat id> => <place>, [...], 
	 * 							"div" => <div>, "final" => <placing - optional>))
	 * 
	 * - $div_count = array(<div> => <entrant_count>)
	 */
	
	
	return true;
}

/**
 * Read the data from the tables to recreate a CSV file for download
 * @param int $id ID number of the sailing series/regatta in the DB
 * @return string CSV collection when successful, NULL otherwise
 */
function _ssc_sailing_get_csv($id){
	global $ssc_database;
	
	// Find heat numbers
	$result = $ssc_database->query("SELECT heats FROM #__sailing_series WHERE id = %d", $id);
	if (!$result || $ssc_database->number_rows() != 1) {
		ssc_add_message(SSC_MSG_CRIT, t('Unable to find specified series within database'));
		return NULL;
	}
	
	$data = $ssc_database->fetch_assoc($result);
	if (!$data) {
		ssc_add_message(SSC_MSG_CRIT, t('Unable to find specified series database details'));
		return NULL;
	}
	// Heat numbers!
	$heats = explode(',', $data['heats']);

	// Organise results
	$result = $ssc_database->query("SELECT r.uid, number, skipper, crew, class, name, club, r.results, r.times, points, division FROM #__sailing_results r LEFT JOIN #__sailing_entries e ON e.id = r.uid WHERE series_id = %d ORDER BY division ASC, number ASC", $id);

	$csv = 'Sail No., Division, Skipper, Crew, Class, Boat Name, Club, ' . $data['heats'] . ", Position\r\n";

	while ($data = $ssc_database->fetch_assoc($result)) {
		if (count($heats) < strlen($data['times'])) {
			// Interleave times and results
			$res = '';
			$pos = explode(',', $data['results']);
			$times = explode(',', $data['times']);
			for ($i = 0; $i < count($pos); $i++) {
				if ($times[$i] == '')
					$res .= "$pos[$i],";
				else
					$res .= "$times[$i],";
			}
			$res = substr($res, 0, -1);
		}
		else {
			// Final result only
			$res = $data['results'];
		}
		$csv .= "$data[number], $data[division], $data[skipper], $data[crew], $data[class], $data[name], $data[club], $res, $data[points]\r\n";
	}

	return $csv;
}

