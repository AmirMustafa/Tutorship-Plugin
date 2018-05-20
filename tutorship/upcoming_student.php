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

//$PAGE->requires->js('/course/format/sectional/module.js');
$PAGE->requires->js('/mod/tutorship/scripts/scripts_5.js');


//print_r($action);die();
    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['subpage'] = "upcoming";

	$currentTeacherID = $USER->id;//

	$nowUnixTime = mktime();//mktime(0, 0, 0, (int) date("m"), (int) date("d"), (int) date("Y"));
	
	$nextClassTime = $nowUnixTime + 10*60;


	// Next Event List=== ( in 10 min)
	$eventListSQL = "select * from event  where userid=$currentTeacherID AND timestart > $nowUnixTime AND timestart < $nextClassTime";

	$nextEventList  = $DB->get_records_sql($eventListSQL, array());

	$nextTable        = new html_table();
	$nextTable->head  = array();
	$nextTable->align = array();
	$nextTable->size  = array();
	$nextTable->attributes['class'] = 'generaltable borderClass';
	 
	$nextTable->head['0'] = "Topic";
	$nextTable->head['1'] = "Teacher Time";
	$nextTable->head['2'] = "Teacher Name";
	$nextTable->head['3'] = "Teacher ID";
	$nextTable->head['4'] = "Session ID";
	$nextTable->head['5'] = "Starting";
	$nextTable->head['6'] = "Enter Class";
	$nextTable->head['7'] = "Enter Course";

	// Table properties
	for ($i = 0; $i <= 7; $i++) {   // From column 0-Hours to column 5-Friday
		$nextTable->align[$i] = 'center';
		$nextTable->size[$i]  = '12%';
	}
	
	foreach($nextEventList  as $eventItem)
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

			$start_time = $eventItem->timestart;
			$delta_time = $start_time - $nowUnixTime;

				$roomParams = array();
				$roomParams['id'] = $cm->id;
				$roomParams['t'] = $course->id;
				$roomParams['subpage'] = "openmeeting";
				$roomParams['rid'] = $eventItem->uuid;

			$urlstr                   = '/mod/tutorship/view.php';
			$roomURL = new moodle_url($urlstr, $roomParams);

		

				$row[0] = $topicName;
				$row[1] = $studentTime;
				$row[2] = $teacherName;//$studentName;
				$row[3] = $teacherID;//$studentID;
				$row[4] = $sessionID;
				$row[5] = "<div class='classPValue'><div class='classTimeValue'>" . $delta_time . "</div><input type='hidden' value='". $delta_time."' class='classTimeSecondValue' /></div>" ;//$studentTime;
				$row[6] = "<a target='_blank' href='".$roomURL."'>Enter Class</a>"; //http://183.182.107.26:5080/openmeetings/signin
				$row[7] = "<a href='../".$courseURL."'>Enter Course</a>";
			$nextTable->data[] = $row;

							unset($row);

		}
	}
	
	echo '<div style="float:left;font-size: 20px;">Next Class</div><div style="float:left;width:25px;height:25px;background-color:#22B14C;margin-left:10px"></div>';
	echo html_writer::table($nextTable);




	///-- Upcoming Event List

	$upcomingListSQL  = "select * from event  where userid=$currentTeacherID AND timestart > $nextClassTime ";
	$upcomingEventList  = $DB->get_records_sql($upcomingListSQL, array());

	$upcomingTable        = new html_table();
	$upcomingTable->head  = array();
	$upcomingTable->align = array();
	$upcomingTable->size  = array();
	$upcomingTable->attributes['class'] = 'generaltable borderClass';
	 
	$upcomingTable->head['0'] = "Topic";
	$upcomingTable->head['1'] = "Teacher Time";
	$upcomingTable->head['2'] = "Teacher Name";
	$upcomingTable->head['3'] = "Teacher ID";
	$upcomingTable->head['4'] = "Session ID";
	$upcomingTable->head['5'] = "Starting";
	$upcomingTable->head['6'] = "Enter Class";
	$upcomingTable->head['7'] = "Enter Course";

	// Table properties
	for ($i = 0; $i <= 7; $i++) {   // From column 0-Hours to column 5-Friday
		$upcomingTable->align[$i] = 'center';
		$upcomingTable->size[$i]  = '12%';
	}
	
	foreach($upcomingEventList  as $eventItem)
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

			$start_time = $eventItem->timestart;
			$delta_time = $start_time - $nowUnixTime;
		
			$roomParams = array();
				$roomParams['id'] = $cm->id;
				$roomParams['t'] = $course->id;
				$roomParams['subpage'] = "openmeeting";
				$roomParams['rid'] = $eventItem->uuid;

			$urlstr                   = '/mod/tutorship/view.php';
			$roomURL = new moodle_url($urlstr, $roomParams);



				$row[0] = $topicName;
				$row[1] = $studentTime;
				$row[2] = $teacherName;
				$row[3] = $teacherID;
				$row[4] = $sessionID;
				$row[5] = "<div class='classPValue'><div class='classTimeValue'>" . $delta_time . "</div><input type='hidden' value='". $delta_time."' class='classTimeSecondValue' /></div>" ;//$studentTime;
				$row[6] = "<a target='_blank'  href='".$roomURL."'>Enter Class</a>"; //http://183.182.107.26:5080/openmeetings/signin
				$row[7] = "<a href='#'>Enter Course</a>";
			$upcomingTable->data[] = $row;

			unset($row);

		}
	}
	
	echo '<div style="float:left;font-size: 20px;">Upcoming Class</div><div style="float:left;width:25px;height:25px;background-color:#FF7F27;margin-left:10px"></div>';

	echo html_writer::table($upcomingTable);

	
	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';


