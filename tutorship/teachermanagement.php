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

//require_once($CFG->dirroot.'/lib/excellib.class.php');
//require_once($CFG->dirroot.'/lib/odslib.class.php');
//require_once($CFG->dirroot.'/lib/csvlib.class.php');
//require_once($CFG->dirroot.'/lib/pdflib.php');
 require_once($CFG->libdir . '/csvlib.class.php');

global $output;

// Security priviledges and layout
require_login($course, true, $cm);
require_capability('mod/tutorship:update', $PAGE->context);

$searchUserNameList  = '';
$allUsernameList = array();
$listdatas = $DB->get_records('user', array());
foreach($listdatas as $userItem)
{
	if(isset($userItem) && isset($userItem->username))
	{
		$name = $userItem->username;
		if($name != "admin" && $name != "guest")
		{
			$f_name = $userItem->firstname;
			$l_name = $userItem->lastname;
			$fullname = $f_name.$l_name; 
			$searchUserNameList .= '"'.$fullname.'",';
			$allUsernameList[$userItem->id] = 	$fullname;
		}
	}
}
//print_R($allUsernameList);die();

echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
echo '<script>';
echo 'var listdata= ['.$searchUserNameList.'];';
echo '</script>';

$PAGE->requires->js('/mod/tutorship/scripts/searchfilter.js');
$PAGE->set_pagelayout('admin');


function getTopicName($classTime ,$cID)
{
	global $DB;
	$starttime   = TUTORSHIP_STARTTIME * 60 * 60;

	$deltaTime = $classTime - $starttime;

	$slotTimeValue = TUTORSHIP_TIMESLOT_MINUTES;
	$breakTimeValue = (int)TUTORSHIP_BREAKTIME_MINUTES;
	$totalOneTime = $slotTimeValue + $breakTimeValue;

	$sectionPos = "1";
	if ($deltaTime  == 0)
	{
		$sectionPos = "1";
	}
	else
	{
		$deltaMin =  $deltaTime/60;
		$pos = (int)($deltaMin / $totalOneTime);
		$sectionPos = $pos + 1;
	}
	
	$sectioname = $DB->get_record('course_sections', array('section' => $sectionPos, 'course'=>$cID));

	if ($sectioname->name) {
		$sectiontitle = $sectioname->name;
		
	} else {
		$sectiontitle = 'Topic '. $sectionPos;
	}
	return $sectiontitle;

}

//print_r($action);die();
    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['action'] = 2;
    $urlparams['subpage'] = "tm";


	$sF_year	= optional_param('s_from_year', 0, PARAM_INT);
	$sF_month	= optional_param('s_from_months', 0, PARAM_INT);
	$sF_day		= optional_param('s_from_days', 0, PARAM_INT);

	$sT_year	= optional_param('s_to_year', 1, PARAM_INT);
	$sT_month	= optional_param('s_to_months', 0, PARAM_INT);
	$sT_day 	= optional_param('s_to_days', 0, PARAM_INT);

	$searchWord 	= optional_param('search_txt', '', PARAM_TEXT);
	

    echo '<p>';
   // echo '<center>';


    // Years array
    $years = array();
    for ($i = 0; $i <= 10; $i++) { // From this year up to next year
        $years[$i] = date('Y', time()) + $i;
    }
	$months = array();
    for ($i = 1; $i <= 12; $i++) { // From 1 up to 12 months
        $months[$i] = $i;
    }
	$days = array();
    for ($i = 1; $i <= 31; $i++) { // From 1 up to 12 months
        $days[$i] = $i;
    }

	$attributes = array('onchange' => 'this.form.submit()');

	echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));

	echo html_writer::start_tag('fieldset');

	echo '<p>';
	echo html_writer::label('From : ');
	echo html_writer::select($years, 's_from_year', $sF_year , false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-right: 20px;margin-left: 20px'));//$attributes);

	echo html_writer::label('Month');
	echo html_writer::select($months, 's_from_months', $sF_month, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));

	echo html_writer::label('Day');
	echo html_writer::select($days, 's_from_days', $sF_day, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));
	echo '</p>';
	

	echo '<p>';
	echo html_writer::label('To : ');
	echo html_writer::select($years, 's_to_year', $sT_year , false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-right: 20px;margin-left: 35px'));//$attributes);

	echo html_writer::label('Month');
	echo html_writer::select($months, 's_to_months', $sT_month, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));

	echo html_writer::label('Day');
	echo html_writer::select($days, 's_to_days', $sT_day, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));
	echo '</p>';
	

	echo html_writer::label('Teacher/Student Name/ID/Session');
	echo '<input type="text" name="search_txt" id="search_txt" value="'.$searchWord.'" style="margin-left: 20px;width: 200px" />';

	echo '<input type="submit" value="Search For Classes" style="margin-left: 20px;width: 180px" class="btn btn-primary" />';

	echo html_writer::end_tag('fieldset');


	echo html_writer::end_tag('form');

  //  echo '</center>';
    echo '</p>';

	$fYearValue = $years[$sF_year];
	$tYearValue = $years[$sT_year];

	$fMonthDay =  $fYearValue.'-'.$sF_month.'-'.$sF_day;
	$tMonthDay =  $tYearValue.'-'.$sT_month.'-'.$sT_day;

	$from_time = strtotime($fMonthDay);
	$fo_time = strtotime($tMonthDay);
	
	/*$f_weekNumber = date('W',$from_time);
	$t_weekNumber = date('W',$fo_time);
	$f_weekDay = date('w',$from_time);
	$t_weekDay = date('w',$fo_time); */

	$searchCond = " (1=1) ";
	if($tYearValue >= $fYearValue)
		$searchCond = " tr.year >= " .$fYearValue . " AND tr.year <= ".$tYearValue . " " ;
	
	$sql  = "SELECT tr.*, tt.teacherid, ts.day, ts.starttime ,ts.id as sid 
					FROM `tutorship_reserves` tr 
					Inner join tutorship_timetables tt ON tt.id=tr.timetableid
					Inner Join tutorship_timeslots ts on ts.id=tt.timeslotid 
					where $searchCond
					order by tt.teacherid , tr.week,ts.day,ts.starttime DESC";
	$allReserveList         = $DB->get_records_sql($sql, array());


	$reserveTable       = new html_table();
	$reserveTable->head  = array();
	$reserveTable->align = array();
	$reserveTable->size  = array();
	$reserveTable->attributes['class'] = 'generaltable borderClass';
	 
	$reserveTable->head['0'] = "<input type='checkbox' id='chk_all' name='chk_all' />";
	$reserveTable->head['1'] = "Teacher";
	$reserveTable->head['2'] = "Date";
	$reserveTable->head['3'] = "Student";
	$reserveTable->head['4'] = "Time";
	$reserveTable->head['5'] = "Session";
	$reserveTable->head['6'] = "Topic";
	$reserveTable->head['7'] = "Course";
	// Table properties
	$reserveTable->align[0] = 'center';
	$reserveTable->size[0]  = '5%';
	for ($i = 1; $i <= 8; $i++) {   // From column 0-Hours to column 5-Friday
		$reserveTable->align[$i] = 'center';
		$reserveTable->size[$i]  = '11%';
	}

	$allCnt  = count($allReserveList);
	foreach ($allReserveList  as $reserveItem)
	{
		if(isset($reserveItem))
		{
			$reserveID = $reserveItem->id;
			$starttimeValue = $reserveItem->starttime;
			$starttime = gmdate('H:i', $starttimeValue);

			$r_Year  = $reserveItem->year;
			$r_Week  = $reserveItem->week;
			$r_Day   = $reserveItem->day;
			$r_CourseID = $reserveItem->courseid;
			$r_teacherID = $reserveItem->teacherid;
			$r_studentID = $reserveItem->studentid;

			
			$calctime = strtotime("1 January $reserveYear", time());
			$deltaday = date('w', $calctime);
			$calctime += ((7*$r_Week)+1-$r_Day)*24*3600;
			//$calctime += $r_Day*24*3600;
			$reserveDate = date('Y-m-d', $calctime); 


			$teacherName = "";
			if(isset($allUsernameList[$r_teacherID]))
				$teacherName = $allUsernameList[$r_teacherID];

			$studentName = "";
			if(isset($allUsernameList[$r_studentID]))
				$studentName = $allUsernameList[$r_studentID];

			$r_SessionID = $reserveItem->id;

			// Search Condition Filter
			if($calctime < $from_time || $calctime > $fo_time)
			{
				continue;
			}
			if($searchWord != "")
			{			
				if(is_numeric ($searchWord))
				{
					//Filter Teacher ID  or Student ID or Session ID
					$searchID = $searchWord;
					if($searchID != "")
					{
						if($r_teacherID == $searchID ||  $r_studentID == $searchID || $r_SessionID == $searchID)
						{
						}
						else
						{
							continue;
						}
					}
				
				}
				else
				{
					// Filter Teacher Name or Student Name
					$searchName = $searchWord;
					if($searchName != "")
					{
						if($teacherName == $searchName ||  $studentName == $searchName)
						{
						}
						else
						{
							continue;
						}
					}
				
				}

			}

			$topicName = getTopicName($starttimeValue , $r_CourseID );
			$courseName = "";
			$courseInfo = $DB->get_record('course', array('id' => $r_CourseID ));
			if(isset($courseInfo) && isset($courseInfo->fullname))
			{
				$courseName = $courseInfo->fullname;
			}

			$row = array();
				$row[0] = '<input type="checkbox" id="sel_'.$reserveID.'"  name="chk_'.$reserveID.'" />';
				$row[1] = $teacherName;
				$row[2] = $reserveDate;//$reserveItem->week;
				$row[3] = $studentName;//$studentID;
				$row[4] = $starttime;
				$row[5] = $r_SessionID;
				$row[6] = $topicName;//"Topic";
				$row[7] = $courseName;
			$reserveTable->data[] = $row;
		}
	
	}

	$endRow = array();

	echo html_writer::table($reserveTable);




	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';
