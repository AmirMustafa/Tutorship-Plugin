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
    $urlparams['subpage'] = "teachermanagement";


    echo '<p>';
    echo '<center>';


    // Years array
    $years = array();
    for ($i = 0; $i <= 10; $i++) { // From this year up to next year
        $years[$i] = date('Y', time()) + $i;
    }
	$months = array();
    for ($i = 1; $i <= 12; $i++) { // From 1 up to 12 months
        $months[$i] = $i;
    }

	$initreserves   = 2017;//(int) $DB->get_field('tutorship_configs', 'maxreserves', $teacherconditions);
	$attributes = array('onchange' => 'this.form.submit()');

	echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));

	echo html_writer::start_tag('fieldset');
	echo html_writer::label('Year ');
	echo html_writer::select($years, 'sel_years', $selYears , false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-right: 20px;margin-left: 20px'));//$attributes);

	echo html_writer::label('Month');
	echo html_writer::select($months, 'sel_months', $selMonth, false,  array('onchange' => 'this.form.submit()' ,'style'=>'margin-left: 20px;margin-right: 20px'));

	echo html_writer::label('Rate (USD):');
	echo '<input type="text" name="rate_value" value="'.$rate_value.'" style="margin-left: 20px;width: 60px" />';

	echo '<input type="submit" value="Apply" style="margin-left: 20px;width: 80px" />';

	echo html_writer::end_tag('fieldset');


	echo html_writer::end_tag('form');

    echo '</center>';
    echo '</p>';

	$fMonthDay =  $selYears.'-'.$selMonth.'-1';
	$eMonthDay =  $selYears.'-'.$selMonth.'-30';
	
	$first_weekNumber = date('W',strtotime($fMonthDay));
	$end_weekNumber = date('W',strtotime($eMonthDay));

	$sql  = "SELECT * FROM tutorship_timetables GROUP BY teacherid";
	$allUsers         = $DB->get_records_sql($sql, array());




	$payResultTable        = new html_table();
	$payResultTable->head  = array();
	$payResultTable->align = array();
	$payResultTable->size  = array();
	$payResultTable->attributes['class'] = 'generaltable borderClass';
	 
	$payResultTable->head['0'] = "Teacher Name";
	$payResultTable->head['1'] = "Status";
	$payResultTable->head['2'] = "Paykey";



	$paymentTable        = new html_table();
	$paymentTable->head  = array();
	$paymentTable->align = array();
	$paymentTable->size  = array();
	$paymentTable->attributes['class'] = 'generaltable borderClass';
	 
	$paymentTable->head['0'] = "Teacher Name";
	$paymentTable->head['1'] = "Class Count";
	$paymentTable->head['2'] = "Total Budget";

	// Table properties
	for ($i = 0; $i <= 2; $i++) {   // From column 0-Hours to column 5-Friday
		$paymentTable->align[$i] = 'center';
		$paymentTable->size[$i]  = '30%';
		
		$payResultTable->size[$i]  = '30%';
		$payResultTable->align[$i] = 'center';
	}
	$showedCnt = 0;
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

						$paymentTable->data[] = array($row[0], $row[1], $row[2]);
						unset($row);
						$showedCnt ++;

						// Add New Split row.
						$space_cell = new html_table_cell();
						$space_cell->text = '';
						$space_cell->colspan = 3;
						$space_row = new html_table_row();
						$space_row->cells[] = $space_cell;
						$space_row->style = "background-color:#FF0000";
						$paymentTable->data[] = $space_row;
					}
                    $row  = array();
				}
			}
		}
	}

	
	$urlstr                   = '/mod/tutorship/view.php';
	$urlparams['rate_value']         = $rate_value;//
	$urlparams['sel_months']         = $selMonth;//
	$urlparams['sel_years']         = $selYears;//
	

	if($action != 12)
	{

		 echo html_writer::table($paymentTable);

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

	}
	else
	{

		if(count($payerList) > 0)
		{
			$payResult = tutorship_salepayment($payerList);
			
			foreach($payerList as $payItem)
			{
				$payResultTable->data[] = array($payItem['email'] , $payResult['Ack'] , $payResult['PayKey'] );
			}
		}

		 echo html_writer::table($payResultTable);


			unset($urlparams['action']);//  = 0;
			$actionurl = new moodle_url($urlstr, $urlparams);
		 echo '<center>';
			echo html_writer::link(new moodle_url($urlstr, $urlparams), "Return to Management");
			echo '</center>';
	 //  redirect($actionurl);
		//$PAGE->set_url($actionurl);
	}

	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';


/*
else	if($action == 11)
	{
//		$PAGE->set_docs_path('mod/tutorship/export');
		// Download CSV
		$filename = "exportCSV";
		$headerColumn = array("Paypal EMAIL","Amount","Currency","Transaction ID" , "Mesage");
		$writer = new csv_export_writer(",");
        $writer->set_filename($filename);
        
		$writer->add_data($headerColumn);
		$writer->add_data($headerColumn);
		$writer->add_data($headerColumn);
		$writer->add_data($headerColumn);

     

        $writer->download_file();
//$csvexport->download_file();
	}
*/


