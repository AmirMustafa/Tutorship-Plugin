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

// Security priviledges and layout
require_login($course, true, $cm);
require_capability('mod/tutorship:reserve', $PAGE->context);
//$PAGE->requires->js('/mod/tutorship/scripts/scripts_history4.js');

	$historyYears    = optional_param('history_years', $currentYear, PARAM_INT);  
	$historyMonths    = optional_param('history_months', $currentMonth, PARAM_INT);
	
	$pageCnt = optional_param('history_showed', 10, PARAM_INT);

    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['subpage'] = "history";

	/*----- Create Search Year & Month Part Start---- */

		// Years array
		$years = array();
		for ($i = 0; $i <= 10; $i++) { // From this year up to next year
			$yData = date('Y', time()) - $i;
			$years[$yData] = $yData;
		}
		$months = array();
		for ($i = 1; $i <= 12; $i++) { // From 1 up to 12 months
			$months[$i] = $i;
		}

		$attributes = array('onchange' => 'this.form.submit()');
		
		echo '<p>';
		echo '<center>';

		echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));

		echo html_writer::start_tag('fieldset');
		echo html_writer::label('Year ');
		echo html_writer::select($years, 'history_years', $historyYears , false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-right: 20px;margin-left: 20px'));//$attributes);

		echo html_writer::label('Month');
		echo html_writer::select($months, 'history_months', $historyMonths, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));

	//	echo html_writer::label('Rate (USD):');
	//	echo '<input type="text" name="rate_value" value="'.$rate_value.'" style="margin-left: 20px;width: 60px" />';

		//echo '<input type="submit" value="Apply" style="margin-left: 20px;width: 80px" />';
		echo '<input type="hidden" value="'.$pageCnt.'" id="history_showed" name="history_showed" />';
		echo html_writer::end_tag('fieldset');


		echo html_writer::end_tag('form');

		echo '</center>';
		echo '</p>';

		$fDay =  $historyYears.'-'.$historyMonths.'-1';
		$eDay =  $historyYears.'-'.$historyMonths.'-30';
		$pf_weekNumber = date('W',strtotime($fDay));
		$ef_weekNumber = date('W',strtotime($eDay));
	
	/* ------  End Search Part Form ------ */




	$currentStudentID = $USER->id;//
	$nowUnixTime = mktime();//mktime(0, 0, 0, (int) date("m"), (int) date("d"), (int) date("Y"));
	
	$sf_UnixTime = mktime(0, 0, 0, (int) $historyMonths, 1, (int) $historyYears);
	$ef_UnixTime = mktime(0, 0, 0, (int) $historyMonths, 30, (int) $historyYears);
	//$nextClassTime = $nowUnixTime + 10*60;

	// Next Event List=== ( in 10 min)
	$eventListSQL = "select * from event  where userid=$currentStudentID AND timestart > $sf_UnixTime AND timestart < $ef_UnixTime  LIMIT 0 , $pageCnt";

	$historyEventList  = $DB->get_records_sql($eventListSQL, array());

	$historyEventTable        = new html_table();
	$historyEventTable->head  = array();
	$historyEventTable->align = array();
	$historyEventTable->size  = array();
	$historyEventTable->attributes['class'] = 'generaltable borderClass';
	 
	$historyEventTable->head['0'] = "Topic";
	$historyEventTable->head['1'] = "Student Time";
	$historyEventTable->head['2'] = "Teacher Name";
	$historyEventTable->head['3'] = "Teacher ID";
	$historyEventTable->head['4'] = "Session ID";
	$historyEventTable->head['5'] = "Enter Course";

	// Table properties
	for ($i = 0; $i <= 6; $i++) {   // From column 0-Hours to column 5-Friday
		$historyEventTable->align[$i] = 'center';
		$historyEventTable->size[$i]  = '13%';
	}
	$allHistoryCnt  = count($historyEventList);
	foreach($historyEventList  as $eventItem)
	{
		if(isset($eventItem))
		{
			$row = array();
			$desc = $eventItem->description;
			$teacherID = $eventItem->repeatid;
			$teacherName = "";
			if($teacherID != "")
			{	
				$teacherInfo =  $DB->get_record('user', array('id' => $teacherID));
				if(isset($teacherInfo))
				{
					$fName     = $teacherInfo->firstname;
					$lName     = $teacherInfo->lastname;
					$teacherName = $fName ." ". $lName ;
				}
			}

			$topicName = "";
			$studentTime = "";
			$studentName = "";
			$studentID = "";
			$sessionID = "";
			$courseURL = "";
			if($desc != "")
			{
				preg_match('/(href=.*\' )/', $desc, $courseURLs);
				if(isset($courseURLs))
				{
					$courseURL = $courseURLs[0];
					$courseURL = str_replace("href=" , "" ,$courseURL);
					$courseURL = str_replace("'" , "" ,$courseURL);
				}
				$spData = explode("</tr><tr>" ,$desc );
				if(isset($spData) && count($spData) > 1)
				{
					$sp_1 = $spData[1];
					if(isset($sp_1))
					{
						$sp_2 = explode("</td>" ,$sp_1);
						if(isset($sp_2))
						{
							if(isset($sp_2[0]))
								$topicName = $sp_2[0];
							if(isset($sp_2[1]))
								$studentTime = $sp_2[1];
							if(isset($sp_2[2]))
								$studentName = $sp_2[2];
							if(isset($sp_2[3]))
								$studentID = $sp_2[3];
							if(isset($sp_2[4]))
								$sessionID = $sp_2[4];
						}
					}
				}
			}
			$topicName = str_replace("<td>" ,"" , $topicName);
			$studentTime = str_replace("<td>" ,"" , $studentTime);
			$studentName = str_replace("<td>" ,"" , $studentName);
			$studentID = str_replace("<td>" ,"" , $studentID);
			$sessionID = str_replace("<td>" ,"" , $sessionID);

			/*$start_time = $eventItem->timestart;
			$delta_time = $start_time - $nowUnixTime;
			*/

				$row[0] = $topicName;
				$row[1] = $studentTime;
				$row[2] = $teacherName;//$studentName;
				$row[3] = $teacherID;//$studentID;
				$row[4] = $sessionID;
				//$row[5] = "<div class='classPValue'><div class='classTimeValue'>" . $delta_time . "</div><input type='hidden' value='". $delta_time."' class='classTimeSecondValue' /></div>" ;//$studentTime;
				//$row[6] = "<a href='http://183.182.107.26:5080/openmeetings/signin'>Enter Class</a>";
				$row[5] = "<a href='../".$courseURL."'>Enter Course</a>";
			$historyEventTable->data[] = $row;

			unset($row);
		}
	}
	
	//echo '<div style="float:left;font-size: 20px;">Next Class</div><div style="float:left;width:25px;height:25px;background-color:#22B14C;margin-left:10px"></div>';

	echo html_writer::table($historyEventTable);

	if($allHistoryCnt == $pageCnt )
	{
		echo '<div style="float:right"><input type="button" value="Show More" onclick="readmoreHistory();" /></div>';
		echo '<script>function readmoreHistory(){var oldvalue= document.getElementById("history_showed").value; var newvalue = parseInt(oldvalue) + 10;  document.getElementById("history_showed").value =newvalue;document.getElementById("searchform").submit();;}</script>';
	
	}
	
	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';

