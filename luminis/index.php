<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * For a given question type, list the number of
 *
 * @package    report
 * @subpackage luminis
 * @copyright  2015 Ian Hamilton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get URL parameters.
$requestedmessagetype = optional_param('messageType', '', PARAM_SAFEDIR);
$requestedhours = optional_param('hoursback', '', PARAM_SAFEDIR);
$requestedlookup = optional_param('lookup', '', PARAM_SAFEDIR);

// Print the header & check permissions.
admin_externalpage_setup('reportluminis', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

$messagetypechoices = array('person'=>'Person', 'enrolment'=>'Enrolment', 'course'=>'Course', 'term'=>'Term');
$hoursbackchoices = array(1=>'1', 3=>'3', 8=>'8', 24=>'24', 72=>'72');


// Print the settings form.
echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo '<form method="get" action="." id="settingsform"><div>';
echo $OUTPUT->heading(get_string('reportsettings', 'report_luminis'));
echo '<p id="intro">', get_string('intro', 'report_luminis') , '</p>';
echo '<p><label for="menumessageType"> ' . get_string('messagetype', 'report_luminis') . '</label> ';
echo html_writer::select($messagetypechoices, 'messageType', $requestedmessagetype, '');
echo '</p>';
echo '<p><label for="hoursback"> ' . get_string('hoursback', 'report_luminis') . '</label> ';
echo html_writer::select($hoursbackchoices, 'hoursback', $requestedhours, '');
echo '</p>';
echo '<p><input type="submit" id="settingssubmit" value="' .
        get_string('getreport', 'report_luminis') . '" /></p>';
echo '</div></form>';
echo $OUTPUT->box_end();

if ($requestedmessagetype) {
	$starttime = time() - 60 * 60 * $requestedhours;
	$table = new html_table();
	
	if ($requestedmessagetype == 'person') {
		$title = get_string('personreporttitle', 'report_luminis');	
		$records = $DB->get_records_select('enrol_lmb_people', 'timemodified >= ' . $starttime);
		
	    $table->head = array(
            get_string('sourcedid', 'report_luminis'),
            get_string('fullname', 'report_luminis'),
            get_string('email', 'report_luminis'),
            get_string('username', 'report_luminis'),
			get_string('timemodified', 'report_luminis'),
			get_string('status', 'report_luminis'));	
			
		foreach($records as $rec) {
		    $date = new DateTime();
			$importStatus = $rec->recstatus == 1 ? 'Imported' : 'Not Imported';
			$formatteddate = $date->setTimestamp($rec->timemodified)->format('Y-m-d H:i:s');
			$table->data[] = array($rec->sourcedid, $rec->fullname, $rec->email, $rec->username, $formatteddate, $importStatus);
		}
		
		
		
	} else if ($requestedmessagetype == 'course') {
		$title = get_string('coursereporttitle', 'report_luminis');	
		$records = $DB->get_records_select('enrol_lmb_courses', 'timemodified >= ' . $starttime);
		
		$table->head = array(
            get_string('sourcedid', 'report_luminis'),
            get_string('coursenumber', 'report_luminis'),
            get_string('term', 'report_luminis'),
            get_string('fulltitle', 'report_luminis'),
			get_string('num', 'report_luminis'),
			get_string('section', 'report_luminis'),
			get_string('timemodified', 'report_luminis'));	
			
		foreach($records as $rec) {
		    $date = new DateTime();
			$formatteddate = $date->setTimestamp($rec->timemodified)->format('Y-m-d H:i:s');
			$table->data[] = array($rec->sourcedid, $rec->coursenumber, $rec->term, $rec->fulltitle, $rec->num, $rec->section, $formatteddate);
		}
	} else if ($requestedmessagetype == 'enrolment') {
		$title = get_string('enrolreporttitle', 'report_luminis');	
		
		$query = 'select e.id, e.coursesourcedid, e.personsourcedid, p.username, e.term, e.role, e.status, e.midtermgrademode, e.finalgrademode, e.timemodified
					from {enrol_lmb_enrolments} e
					inner join {enrol_lmb_people} p on p.sourcedid = e.personsourcedid
					where e.timemodified >= ' . $starttime;
					
		$records = $DB->get_records_sql($query);
		
		$table->head = array(
            get_string('coursenumber', 'report_luminis'),
            get_string('username', 'report_luminis'),
            get_string('term', 'report_luminis'),
            get_string('role', 'report_luminis'),
			get_string('midtermgrade', 'report_luminis'),
			get_string('finalgrade', 'report_luminis'),
			get_string('timemodified', 'report_luminis'),
			get_string('status', 'report_luminis'));
			
		foreach($records as $rec) {
		    $date = new DateTime();
			$formatteddate = $date->setTimestamp($rec->timemodified)->format('Y-m-d H:i:s');
			$importStatus = $rec->status == 1 ? 'Imported' : 'Not Imported';
			$table->data[] = array($rec->coursesourcedid, $rec->username, $rec->term, $rec->role, $rec->midtermgrademode, $rec->finalgrademode, $formatteddate, $importStatus);
		}
		
	} else if ($requestedmessagetype == 'term') {
		$title = get_string('termreporttitle', 'report_luminis');	
		$records = $DB->get_records_select('enrol_lmb_terms', 'timemodified >= ' . $starttime);

		$table->head = array(
            get_string('sourcedid', 'report_luminis'),
            get_string('title', 'report_luminis'),
            get_string('startime', 'report_luminis'),
            get_string('endtime', 'report_luminis'),
			get_string('activestatus', 'report_luminis'));
			
		foreach($records as $rec) {
		    $date = new DateTime();
			$formatteddate = $date->setTimestamp($rec->timemodified)->format('Y-m-d H:i:s');
			$start = $date->setTimestamp($rec->starttime)->format('Y-m-d H:i:s');
			$end = $date->setTimestamp($rec->endtime)->format('Y-m-d H:i:s');
			$active = $rec->active == 1 ? 'Active' : 'Not Active';
			$table->data[] = array($rec->sourcedid, $rec->title, $start, $end, $formatteddate);
		}
	}

	echo $OUTPUT->heading($title);
	echo '<p><label for="lookup"> ' . get_string('lookup', 'report_luminis') . '</label> ';
	echo '<input type="text" id="lookup" name="lookup" value="' . $requestedlookup . '"></input>';
	echo '</p>';
	echo html_writer::table($table);
}

// Footer.
echo $OUTPUT->footer();
