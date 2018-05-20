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
 * Prints a particular instance of tutorship for teachers view.
 *
 * The tutorship instance view that shows the teacher's tutoring
 * timetable configuration with time slots for student to reserve.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die(); // Direct access to this file is forbidden

global $output;
require_once($CFG->dirroot.'/mod/tutorship/slotforms.php');

// Security priviledges and layout
require_login($course, true, $cm);
require_capability('mod/tutorship:leavemanage', $PAGE->context);
$PAGE->set_pagelayout('admin');

$adminPermission = false;
	if (has_capability('mod/tutorship:teachermanage', $context)) {
			$adminPermission = true;
	}

//print_r($action);die();
    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['action'] = 2;
    $urlparams['subpage'] = "leavemanagement";



    echo '<p>';
    echo '<center>';


	unset($urlparams['action']);//  = 0;

	$actionurl = new moodle_url($urlstr, $urlparams);

	if($adminPermission  == false)
	{

		$mform = new tutorship_leave_form($actionurl, $context, $cm);
		 if ($mform->is_cancelled())
		{
		   // redirect($actionurl);
		} else if ($formdata = $mform->get_data()) {
		
			//$mform->save_slot($slotid, $formdata);
			$checkStatus = tutorship_check_leave($USER->id ,$formdata);
			if($checkStatus == false)
			{
				$insertID = tutorship_insert_leave($USER->id ,$formdata);
				if($insertID != "")
				{
					echo $output->action_message("Registered Leave Request!");
				}
			}
			else
			{
				echo $output->action_message("Duplication Leave Request!");
			}
			//
		} 
		$mform->display();
	}

	echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));

	echo html_writer::start_tag('fieldset');



	$fMonthDay =  $selYears.'-'.$selMonth.'-1';
	$eMonthDay =  $selYears.'-'.$selMonth.'-30';
	
	$first_weekNumber = date('W',strtotime($fMonthDay));
	$end_weekNumber = date('W',strtotime($eMonthDay));

	$sql = "SELECT * FROM tutorship_leaveinfo where (1=1) ";
	if($adminPermission  == false)
	{
		$sql  .= " AND teacherid=$USER->id ";
	}
	$sql .= "order by timecreated desc";
	
	$allleaveList         = $DB->get_records_sql($sql, array());

//print_R($USER->id);die();


	$leaveTable        = new html_table();
	$leaveTable->head  = array();
	$leaveTable->align = array();
	$leaveTable->size  = array();
	$leaveTable->attributes['class'] = 'generaltable borderClass';
	
	if($adminPermission  == true)
	{
		$leaveTable->head[] = "Teacher";
	}
	$leaveTable->head[] = "Leave Start";
	$leaveTable->head[] = "Leave End";
	$leaveTable->head[] = "Duration";
	$leaveTable->head[] = "Affected Class";
	$leaveTable->head[] = "Reason";
	//---$leaveTable->head[] = "Status";

	$headerCnt =  count($leaveTable->head);
	// Table properties
	for ($i = 0; $i <= $headerCnt; $i++) {   // From column 0-Hours to column 5-Friday
		$leaveTable->align[$i] = 'center';
		$leaveTable->size[$i]  = '16%';
	}

	$reasonList = tutorship_get_leave_reason_list();
	
	if(isset($allleaveList) && count($allleaveList) > 0)
	{
		foreach($allleaveList as $itemData)
		{
			if(isset($itemData) && isset($itemData->teacherid) )
			{
					$row      = array();
					$startTimeValue = $itemData->startdate + $itemData->starttime ;
					$endTimeValue = $itemData->enddate + $itemData->endtime ;
					$deltaValue = $endTimeValue - $startTimeValue;
					if($adminPermission  == true)
					{
						$m_userData = $DB->get_record('user', array('id' =>$itemData->teacherid));
						$row[] = $m_userData->username;
					}
					$row[] = date("Y-m-d H:i" ,$startTimeValue);
					$row[] = date("Y-m-d H:i" ,$endTimeValue);
					
					if($deltaValue > 0)
					{
						$d_d = gmdate("d" ,$deltaValue);
						$d_hours = gmdate("H" ,$deltaValue);
						$d_min = gmdate("i" ,$deltaValue);
							$row[] = $d_d."day," .$d_hours."hour,".$d_min."min" ;
					}
					else
					{
						$row[] = 0;
					}
					
					$row[] = tutorship_get_affectedclass_count($itemData->teacherid ,$itemData->startdate , $itemData->enddate,$itemData->starttime , $itemData->endtime);
					$row[] = $reasonList[$itemData->reason];
					
					/*if($itemData->confirmed == 0)
					{
						$row[] = "No";
					}
					else
					{
						$row[] = "Confirmed";
					}*/
					
					$leaveTable->data[] = $row;//array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
					unset($row);

						// Add New Split row.
						$space_cell = new html_table_cell();
						$space_cell->text = '';
						$space_cell->colspan = $headerCnt;
						$space_row = new html_table_row();
						$space_row->cells[] = $space_cell;
						$space_row->style = "background-color:#FF0000";
						//$leaveTable->data[] = $space_row;
                    $row  = array();
			}
		}
	}

	
	$urlstr                   = '/mod/tutorship/view.php';
	$urlparams['rate_value']         = $rate_value;//
	$urlparams['sel_months']         = $selMonth;//
	$urlparams['sel_years']         = $selYears;//


		 echo html_writer::table($leaveTable);

		if($showedCnt > 0)
		{
			$urlparams['action']         = 11;//
			echo '<div style="float:left">';
			echo html_writer::link(new moodle_url($urlstr, $urlparams), "Download CSV");
			echo '</div>';

			$urlparams['action']         = 12;//

			echo '<div style="float:right">';
			echo html_writer::link(new moodle_url($urlstr, $urlparams), "Send API");
			echo '</div>';
		}


	echo html_writer::end_tag('fieldset');


	echo html_writer::end_tag('form');

    echo '</center>';
    echo '</p>';
	
	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}.felement{text-align: left !important;}</style>';

