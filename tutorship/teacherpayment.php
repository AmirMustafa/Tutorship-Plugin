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
$PAGE->set_pagelayout('admin');

//print_r($action);die();
    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['action'] = 2;
    $urlparams['subpage'] = "teacherpayment";


$payYears    = optional_param('pay_years', $currentYear, PARAM_INT);          // cancell reservation from teacherview
$payMonths    = optional_param('pay_months', $currentMonth, PARAM_INT);          // cancell reservation from teacherview
//print_r($payYear );die();
    echo '<p>';
    echo '<center>';


    // Years array
    $years = array();
    for ($i = -1; $i <= 10; $i++) { // From this year up to next year
		$yData = date('Y', time()) + $i;
        $years[$yData] = $yData;
    }
	$months = array();
    for ($i = 1; $i <= 12; $i++) { // From 1 up to 12 months
        $months[$i] = $i;
    }

	$attributes = array('onchange' => 'this.form.submit()');

	echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));

	echo html_writer::start_tag('fieldset');
	echo html_writer::label('Year ');
	echo html_writer::select($years, 'pay_years', $payYears , false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-right: 20px;margin-left: 20px'));//$attributes);

	echo html_writer::label('Month');
	echo html_writer::select($months, 'pay_months', $payMonths, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));

//	echo html_writer::label('Rate (USD):');
//	echo '<input type="text" name="rate_value" value="'.$rate_value.'" style="margin-left: 20px;width: 60px" />';

	//echo '<input type="submit" value="Apply" style="margin-left: 20px;width: 80px" />';

	echo html_writer::end_tag('fieldset');


	echo html_writer::end_tag('form');

    echo '</center>';
    echo '</p>';

	$fDay =  $payYears.'-'.$payMonths.'-1';
	$eDay =  $payYears.'-'.$payMonths.'-30';
	
	$pf_weekNumber = date('W',strtotime($fDay));
	$ef_weekNumber = date('W',strtotime($eDay));



	$all_courses = $DB->get_records('course');

	//$sql  = "SELECT * FROM tutorship_timetables GROUP BY teacherid";
	//$allUsers         = $DB->get_records_sql($sql, array());


	$historyTable        = new html_table();
	$historyTable->head  = array();
	$historyTable->align = array();
	$historyTable->size  = array();
	$historyTable->attributes['class'] = 'generaltable borderClass';
	 
	$historyTable->head['0'] = "Course Name";
	$historyTable->head['1'] = "Class Size";
	$historyTable->head['2'] = "Unit Price";
	$historyTable->head['3'] = "Quanity";
	$historyTable->head['4'] = "Additional";
	$historyTable->head['5'] = "Total";
	//$historyTable->head['6'] = "";

	// Table properties
	for ($i = 0; $i <= 5; $i++) {   // From column 0-Hours to column 5-Friday
		$historyTable->align[$i] = 'center';
		$historyTable->size[$i]  = '18%';
	}
	$showedCnt = 0;
	$payTeacherID = $USER->id;//

	$teacherTimeZone         = $DB->get_field('user', 'timezone', array('id' =>  $payTeacherID));
	if($teacherTimeZone == '99')
	{
		$teacherTimeZone = "Asia/Vientiane";
	}
	$offsetTimeZone = tutorship_get_timezone_offset($teacherTimeZone , 'Asia/Vientiane');
	$total_Price = 0;

	if(isset($all_courses) && count($all_courses) > 0)
	{
		foreach ($all_courses as $item)
		{
			if(isset($item) && $item->format == "topics")
			{
				$m_courseItemName = $item->fullname;
				$m_courseID = $item->id;
					//courseclasssize
				$course_maxSize = get_config('tutorship', 'courseclasssize'.$m_courseID);
				

				$getReserveSQL = "select a.* ,b.teacherid , st.day , st.starttime from tutorship_reserves a left join tutorship_timetables b on b.id= a.timetableid left join tutorship_timeslots st on st.id=b.timeslotid where a.courseid=$m_courseID AND a.week >= $pf_weekNumber AND a.week <= $ef_weekNumber AND a.confirmed = 1 AND a.year = $payYears AND b.teacherid=$payTeacherID GROUP BY a.timetableid";

				//print_r($getReserveSQL);die();
				$allReserves         = $DB->get_records_sql($getReserveSQL, array());
				
				$m_quanity = count($allReserves);//50;
				$unitPrice = 7.5;
				


				$m_additional = 0;
				if($m_quanity >= 50)
				{
					$m_additional = ($unitPrice  * $m_quanity)*0.03;
				}
				
				$total_Price += $unitPrice  * $m_quanity;
				$row      = array();
				$row[0] = "<a  data-toggle='collapse' data-target='#empty_row_".$m_courseID."' class='accordion-toggle fa-sort-desc' style='cursor: pointer;color: #00F'><img src='pix/collapse.png' width='20' height='20' />".$m_courseItemName."</a>";//$m_courseItemName;
				$row[1] = "1 to " . $course_maxSize;
				$row[2] = $unitPrice." USD";
				$row[3] = $m_quanity;
				$row[4] = $m_additional;
				$row[5] = $unitPrice * $m_quanity;
				//$row[6] = "<a data-toggle='collapse' data-target='#empty_row_".$m_courseID."' class='accordion-toggle fa-sort-desc' style='cursor: pointer'><span class='glyphicon glyphicon-circle-arrow-down'></span></a>";

				$historyTable->data[] = $row;
				unset($row);
			
				$showedCnt ++;


				$courseTopicList = $DB->get_records('course_sections', array('course' => $m_courseID));

				$subTable = '<table class="generaltable">';
				
				$yearStartTime = strtotime($payYears."-1-1");
				$rowData = "";
				// Create Sub Column Headers
				if($m_quanity > 0)
				{
					$rowData .= '<tr>';
					$rowData .= '<td colspan="2">Class Time</td>';
					$rowData .= '<td colspan="2">Local Time</td>';
					$rowData .= '<td colspan="1">Course Name</td>';
					$rowData .= '<td colspan="">Topic</td>';
					$rowData .= '</tr>';
				}

				$indKey = 0;
				foreach($allReserves as $classItem)
				{
					$classWeek = $classItem->week;
					$classDayNum = $classItem->day;
					$classStartTimeValue = $classItem->starttime;
					
					$classDayTime = $yearStartTime + ((7*$classWeek)+1-$classDayNum)*24*3600;
					$classDate = date('Y-m-d', $classDayTime);

					$classStartTime  = gmdate('H:i', $classStartTimeValue);

					$localTime = date('Y-m-d H:i', $classDayTime + $classStartTimeValue - $offsetTimeZone);

				
					/*--- Get Topic Name --- */	
						$indKey ++;
						$topicCnt = count($courseTopicList);
						$sectionPos = 1;
						if($indKey >= $topicCnt)
						{
							$indKey = 1;
						}
						$sectionPos = $indKey;
							$sectioname = $DB->get_record('course_sections', array('section' => $sectionPos, 'course' => $m_courseID ));
							$sectiontitle = "";
							if ($sectioname->name) {
								$sectiontitle = $sectioname->name;
								
							} else {
								$sectiontitle = 'Topic '. $sectionPos;
							}
						/*
					$tutorsip_starttime   = TUTORSHIP_STARTTIME * 60 * 60;
					$deltaTime = $classStartTimeValue - $tutorsip_starttime;
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
						$topicCnt = count($courseTopicList);
						if($sectionPos > $topicCnt)
						{
							$sectionPos = $sectionPos - $topicCnt;
						}
					}
					$sectioname = $DB->get_record('course_sections', array('section' => $sectionPos, 'course' => $m_courseID ));
					$sectiontitle = "";
					if ($sectioname->name) {
						$sectiontitle = $sectioname->name;
						
					} else {
						$sectiontitle = 'Topic '. $sectionPos;
					}*/
					/// ---- End


					$rowData .= '<tr>';
					$rowData .= '<td colspan="2">'.$classDate. ' '.$classStartTime .'</td>';
					$rowData .= '<td colspan="2">'.$localTime.'</td>';
					$rowData .= '<td colspan="1">'. $m_courseItemName .'</td>';
					$rowData .= '<td colspan="">'.$sectiontitle.'</td>';
					$rowData .= '</tr>';

					
				}

				$subTable .= $rowData.'</table>';

				// Add New Split row.
				$space_cell = new html_table_cell();
				$space_cell->text = '<div class="accordian-body collapse" id="empty_row_'.$m_courseID.'">'.$subTable.'</div>';
				$space_cell->colspan = 6;
				$space_cell->size  = '100%';

				$space_row = new html_table_row();
				$space_row->cells[] = $space_cell;
				//$space_row->style = "background-color:#FF0000";
				$historyTable->data[] = $space_row;
			}
		}	
	}

	// Total Row
	// Add New Split row.
				$totalLabel_cell = new html_table_cell();
				$totalLabel_cell->text = '<b>Total</b>';
				$totalLabel_cell->colspan = 3;

				$totalPrice_cell = new html_table_cell();
				$totalPrice_cell->text = '<b>'.$total_Price. ' USD </b>';
				$totalPrice_cell->colspan = 3;

				//$space_cell->size  = '100%';

				$total_row = new html_table_row();
				$total_row->cells[] = $totalLabel_cell;
				$total_row->cells[] = $totalPrice_cell;
				//$space_row->style = "background-color:#FF0000";
				$historyTable->data[] = $total_row;

		/*
	if(isset($allUsers) && count($allUsers) > 0)
	{
		$payerList = array();
		foreach($allUsers as $itemData)
		{
			if(isset($itemData) && isset($itemData->teacherid) )
			{
				$userData = $DB->get_record('user', array('id' => $itemData->teacherid));
				
				if(isset($userData) && isset($userData->username) && $userData->username != "admin")
				{
				
					$row      = array();
					$row[0] = $userData->username. " (" .$userData->email .")";

					//$getReserveSQL = "select * from tutorship_reserves where courseid=$course->id AND week >= $first_weekNumber AND week <= $end_weekNumber AND confirmed = 1 GROUP BY timetableid";
					$getReserveSQL = "select a.* ,b.teacherid from tutorship_reserves a left join tutorship_timetables b on b.id= a.timetableid where a.courseid=$course->id AND a.week >= $first_weekNumber AND a.week <= $end_weekNumber AND a.confirmed = 1 AND a.year = $selYears AND b.teacherid=$userData->id GROUP BY a.timetableid";
						$allReserves         = $DB->get_records_sql($getReserveSQL, array());
					//	print_r($allReserves);die();
					$totalClassCnt = count($allReserves);
					$row[1] = $totalClassCnt;
					$row[2] = $totalClassCnt*$rate_value . " USD";
					
					if( $totalClassCnt > 0)
					{
						if($action == 12)
						{
							$payerList[] = array('total'=>$totalClassCnt*$rate_value , 'email'=>$userData->email);
							//tutorship_masspayment($userData->email , $totalClassCnt*$rate_value ,$totalClassCnt);
						//	$payResult = tutorship_salepayment($userData->email , $totalClassCnt*$rate_value ,$totalClassCnt);
						//	$payResultTable->data[] = array($row[0] , $payResult['Ack'] , $payResult['PayKey'] );
						}

						$historyTable->data[] = array($row[0], $row[1], $row[2]);
						unset($row);
						$showedCnt ++;

						// Add New Split row.
						$space_cell = new html_table_cell();
						$space_cell->text = '';
						$space_cell->colspan = 3;
						$space_row = new html_table_row();
						$space_row->cells[] = $space_cell;
						$space_row->style = "background-color:#FF0000";
						$historyTable->data[] = $space_row;
					}
                    $row  = array();
				}
			}
		}
	}
	*/
	
	$urlstr                   = '/mod/tutorship/view.php';
	$urlparams['rate_value']         = $rate_value;//
	$urlparams['pay_months']         = $payMonths;//
	$urlparams['pay_years']         = $payYears;//
	


		 echo html_writer::table($historyTable);

	
	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';


