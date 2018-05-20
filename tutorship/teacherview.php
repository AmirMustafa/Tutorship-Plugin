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

 require_once($CFG->libdir . '/csvlib.class.php');

global $output;
global $subpage;

	$yearselect = (int) get_config('tutorship', 'firstperiodendyear');
    $e_year       = tutorship_to_year($yearselect);
	$e_day        = (int) get_config('tutorship', 'firstperiodendday');
	$e_month      = (int) get_config('tutorship', 'firstperiodendmonth');


    $f_day        = (int) get_config('tutorship', 'firstperiodstartday');
    $f_month      = (int) get_config('tutorship', 'firstperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'firstperiodstartyear');
    $f_year       = tutorship_to_year($yearselect);


if($action == 11)
{
		$filename = "exportCSV";
		$headerColumn = array("Paypal EMAIL","Amount","Currency","Transaction ID" , "Message");
		$writer = new csv_export_writer(",");
        $writer->set_filename($filename);

		$writer->add_data($headerColumn);


		$f_weekNumber = date('W',strtotime($selYears.'-'.$selMonth.'-1'));
		$e_weekNumber = date('W',strtotime($selYears.'-'.$selMonth.'-30'));


		$m_list         = $DB->get_records_sql("SELECT * FROM tutorship_timetables GROUP BY teacherid", array());
		if(isset($m_list) && count($m_list) > 0)
		{
			$indKey = 0;
			foreach($m_list as $itemData)
			{
				if(isset($itemData) && isset($itemData->teacherid) )
				{
					$m_userData = $DB->get_record('user', array('id' => $itemData->teacherid));

					if(isset($m_userData) && isset($m_userData->username) && $m_userData->username != "admin")
					{
						$indKey = $indKey  + 1;
						$row      = array();
						$row[0] = $m_userData->email;

						//$getReserveSQL = "select * from tutorship_reserves where courseid=$course->id AND week >= $first_weekNumber AND week <= $end_weekNumber AND confirmed = 1 GROUP BY timetableid";
						$m_getReserveSQL = "select a.* ,b.teacherid from tutorship_reserves a left join tutorship_timetables b on b.id= a.timetableid where a.courseid=$course->id AND a.week >= $f_weekNumber AND a.week <= $e_weekNumber AND a.confirmed = 1 AND b.teacherid=$m_userData->id  AND  a.year = $selYears GROUP BY a.timetableid";
						$m_allReserves         = $DB->get_records_sql($m_getReserveSQL, array());
						$cnt = count($m_allReserves);
						if($cnt > 0)
						{
							$row[1] = $cnt*$rate_value;
							$row[2] = "USD";
							$row[3] = $indKey;
							$row[4] = $m_userData->description;

							$writer->add_data($row);
						}
							unset($row);
					}
				}
			}
		}

        $writer->download_file();
}

// Prints the heading
echo $OUTPUT->heading($OUTPUT->help_icon('teacherheading', 'tutorship').format_string($tutorship->name));

// Security priviledges and layout
require_login($course, true, $cm);
require_capability('mod/tutorship:update', $PAGE->context);
$PAGE->set_pagelayout('admin');



$adminPermission = false;
if (has_capability('mod/tutorship:teachermanage', $context)) {
		$adminPermission = true;
}


$urlparams['id'] = $cm->id;
$actionurl = new moodle_url('/mod/tutorship/view.php', $urlparams);
$inactive = array();
/*
if ($DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id)) <=
         $DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this scheduler.
    $inactive[] = 'allappointments';
    if ($subpage = 'allappointments') {
        $subpage = 'myappointments';
    }
}*/
 //$subpage = 'schedule';
echo $output->teacherview_tabs($context , $actionurl, $subpage, $inactive);


if($subpage == 'schedule' &&  $adminPermission != true)
{
if($action  == 1)
	$action =2;
if ($action == 1) { // view timetable

    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['slotid'] = 0; // We don't want to enable a slot
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['maxreserves'] = 50;//$maxreserves;
    $urlparams['notify'] = 1;//$notify;
    $urlparams['action'] = 2;
    echo '<p>';
    echo '<center>';
    echo $OUTPUT->single_button(new moodle_url('/mod/tutorship/view.php', $urlparams), get_string('edit', 'tutorship'));
    echo '</center>';
    echo '</p>';

	//Get teacher TimzeZone Value
	$teacherTimeZone         = $DB->get_field('user', 'timezone', array('id' =>  $USER->id));
	if($teacherTimeZone == '99')
	{
		$teacherTimeZone = "Asia/Vientiane";
	}
	$offsetTimeZone = tutorship_get_timezone_offset($teacherTimeZone , 'Asia/Vientiane');

    // Shows the teacher timetable
    $periodid = tutorship_get_current_period($today);
    if (tutorship_has_timetable($USER->id, $selectedperiod)) {
        // Gets necessary data for reservetable rows
        $timeslotlength = tutorship_get_slot_length();
        $teachertimeslots = $DB->get_records('tutorship_timetables', array('teacherid' => $USER->id,
                                             'periodid' => $selectedperiod), 'timeslotid');

        // Preparing table
        $table        = new html_table();
        $table->head  = array();
        $table->align = array();
        $table->size  = array();
		$table->attributes['class'] = 'generaltable borderClass';

        // Table heading
        $table->head['0'] = "Teacher Time";//get_string('hours', 'tutorship');
        $table->head['1'] = "Laos Time";//get_string('hours', 'tutorship');
        $table->head['2'] = get_string('monday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 0);;
        $table->head['3'] = get_string('tuesday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 1);
        $table->head['4'] = get_string('wednesday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 2);
        $table->head['5'] = get_string('thursday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 3);
        $table->head['6'] = get_string('friday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 4);
        $table->head['7'] = get_string('saturday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 5);
        $table->head['8'] = get_string('sunday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 6);

        // Table properties
        for ($i = 0; $i <= 8; $i++) {   // From column 0-Hours to column 5-Friday
            $table->align[$i] = 'center';
            $table->size[$i]  = '10%';
        }

        // Reserve rows
        if ($teachertimeslots) {
            $row      = array();
            $slots    = array();
            $numslots = 0;

            // Necessary information to confirm
            $weeknumber = date('W', $today);
			$daynumber  = date('N', $today) - 1;

            // Sets time slots object array
            foreach ($teachertimeslots as $teachertimeslot) {
                $slots[$numslots] = $DB->get_record('tutorship_timeslots', array('id' => $teachertimeslot->timeslotid));
                $numslots++;
            }

            // Sets and adds rows to table
            for ($i = 0; $i <= $numslots; $i++) {
                if ($slots[$i]->starttime == $slots[$i + 2]->starttime) {   // Same row
                    if (empty($row[0]))
					{
						// First column: Hours
						$sTime = $slots[$i]->starttime;
						$sTime = $sTime - $offsetTimeZone;

						$row[0]  = gmdate('H:i', $sTime).' - ';
                        $row[0] .= gmdate('H:i', $sTime + $timeslotlength);
                    }
					// For Laos Time
					if (empty($row[1])) {                                   // First column: Hours
                        $row[1]  = gmdate('H:i', $slots[$i]->starttime).' - ';
                        $row[1] .= gmdate('H:i', $slots[$i]->starttime + $timeslotlength);
                    }

                    // Can't cofirm nor cancell previous days for current week
                    if ((($slots[$i]->day > $daynumber) and ($week == 1)) or ($week == 2) or ($week == 3) or ($week == 4)) {
                        // Adds element row cell with reservation information
                        $tableconditions = array('teacherid' => $USER->id, 'periodid' => $selectedperiod,
                                                 'timeslotid' => $slots[$i]->id);
                        $timetableid = $DB->get_field('tutorship_timetables', 'id', $tableconditions);
                        $row[$slots[$i]->day + 2] = tutorship_get_reservation_info_link($course->id, $timetableid,
                                                                                        $weeknumber, $urlparams);
                    } else {
                        $row[$slots[$i]->day + 2] = format_text(get_string('empty', 'tutorship'));
                    }
                } else { // End row set
                    if (empty($row[0])) {
						// First column: Hours
						// For Teacher Time
						$offsetTimeZone = tutorship_get_timezone_offset($teacherTimeZone , 'Asia/Vientiane');
						$sTime = $slots[$i]->starttime;
						$sTime = $sTime - $offsetTimeZone;

                        $row[0]  = gmdate('H:i', $sTime).' - ';
                        $row[0] .= gmdate('H:i', $sTime + $timeslotlength);
                    }
					if (empty($row[1])) {
						// First column: Hours
						// For Laos Time
                        $row[1]  = gmdate('H:i', $slots[$i]->starttime).' - ';
                        $row[1] .= gmdate('H:i', $slots[$i]->starttime + $timeslotlength);
                    }

                    // Can't cofirm nor cancell previous days for current week
                    if ((($slots[$i]->day > $daynumber) and ($week == 1)) or ($week == 2) or ($week == 3) or ($week == 4)) {
                        // Adds element row cell with reservation information
                        $tableconditions = array('teacherid' => $USER->id, 'periodid' => $selectedperiod,
                                                 'timeslotid' => $slots[$i]->id);
                        $timetableid = $DB->get_field('tutorship_timetables', 'id', $tableconditions);
                        $row[$slots[$i]->day + 2] = tutorship_get_reservation_info_link($course->id, $timetableid,
                                                                                        $weeknumber, $urlparams);
                    } else {
                        $row[$slots[$i]->day + 2] = format_text(get_string('empty', 'tutorship'));
                    }

                    // Sets empty row cells
                    for ($j = 1; $j <= 8; $j++) {
                        if (empty($row[$j])) {
                            $row[$j] = null;
                        }
                    }

                    // Adds row to table
                    $table->data[] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]);
                    unset($row);

					// Add New Split row.
					$space_cell = new html_table_cell();
					$space_cell->text = '';
					$space_cell->colspan = 9;
					$space_row = new html_table_row();
					$space_row->cells[] = $space_cell;
					$space_row->style = "background-color:#FF0000";
					$table->data[] = $space_row;

                    $row  = array();
                }
            }
        }

        // Prints timetable period
        echo $OUTPUT->box_start();
        echo $OUTPUT->container_start();
        echo '<center>';
        $introtextrecord      = $DB->get_record('tutorship_periods', array('id' => $selectedperiod));
        $introtextdescription = $introtextrecord->description;
		 $introtextstartdate   =  date('d/m/y', strtotime($f_year."-".$f_month."-".$f_day));//date('d/m/y', $introtextrecord->startdate);
		$introtextenddate     =  date('d/m/y', strtotime($e_year."-".$e_month."-".$e_day));//date('d/m/y', $introtextrecord->enddate);
        $introtext            = '<b>'.$introtextdescription.'</b> ('.$introtextstartdate.' - '.$introtextenddate.')';
        echo format_text($introtext);
        echo '</center>';
        echo $OUTPUT->container_end();
        echo $OUTPUT->box_end();

        // If selected period is current period, then show current/next week links
        // Next/Current week top link
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '<div align=right>';
        }
        echo tutorship_get_week_link($week, $urlparams);
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '</div>';
        }

        // Prints table
        echo html_writer::table($table);

        // If selected period is current period, then show current/next week links
        // Next/Current week top link
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '<div align=right>';
        }
        echo tutorship_get_week_link($week, $urlparams);
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '</div>';
        }
    } else if (! tutorship_has_timetable($USER->id, $selectedperiod)) {
        echo '<center>';
        echo $OUTPUT->error_text(fullname($USER).' '.get_string('notimetable', 'tutorship'));
        echo '</center>';
    }

} else if ($action == 2) { // edit timetable

    // Prints the view button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $t;
    $urlparams['slotid'] = 0; // We don't want to reserve or unreserve any slot now
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['maxreserves'] = 50;//$maxreserves;
    $urlparams['notify'] = 1;//$notify;
    $urlparams['action'] = 1;
   /* echo '<p>';
    echo '<center>';
    echo $OUTPUT->single_button(new moodle_url('/mod/tutorship/view.php', $urlparams), get_string('view', 'tutorship'));
    echo '</center>';
    echo '</p>';
*/
		//Get teacher TimzeZone Value
	$teacherTimeZone         = $DB->get_field('user', 'timezone', array('id' =>  $USER->id));
	if($teacherTimeZone == '99')
	{
		$teacherTimeZone = "Asia/Vientiane";
	}
	$offsetTimeZone = tutorship_get_timezone_offset($teacherTimeZone , 'Asia/Vientiane');


    // Preparing timetable
    $timetable        = new html_table();
    $timetable->head  = array();
    $timetable->align = array();
    $timetable->size  = array();
	$timetable->attributes['class'] = 'generaltable borderClass';

    // Timetable heading
    $timetable->head['0'] = "Teacher Time";//get_string('hours', 'tutorship');
    $timetable->head['1'] = "Laos Time";//get_string('hours', 'tutorship');
    $timetable->head['2'] = get_string('monday', 'tutorship');
    $timetable->head['3'] = get_string('tuesday', 'tutorship');
    $timetable->head['4'] = get_string('wednesday', 'tutorship');
    $timetable->head['5'] = get_string('thursday', 'tutorship');
    $timetable->head['6'] = get_string('friday', 'tutorship');
	$timetable->head['7'] = get_string('saturday', 'tutorship');
    $timetable->head['8'] = get_string('sunday', 'tutorship');

    // Timetable properties
    for ($i = 0; $i <= 8; $i++) {   // From column 0-Hours to column 5-Friday
        $timetable->align[$i] = 'center';
        $timetable->size[$i]  = '10%';
    }

    // Gets necessary data for timetable rows
    $timeslotlength = tutorship_get_slot_length();
    $timeslots = $DB->get_records('tutorship_timeslots');

    // Timetable rows
    foreach ($timeslots as $timeslot) {
        if ($timeslot->day == TUTORSHIP_MONDAY) {
            $row       = array();
            $starttime = gmdate('H:i', $timeslot->starttime);
            /*echo "<pre>";
            print_r($timeslot->starttime);
            echo "</pre>";
            die();*/
            $endtime   = gmdate('H:i', $timeslot->starttime + $timeslotlength);

		// For Teacher Time
				$sTime = $timeslot->starttime;
				$sTime = $sTime - $offsetTimeZone;
				$start_teacherTime = gmdate('H:i', $sTime);
				$end_teacherTime = gmdate('H:i', $sTime + $timeslotlength);

            $row['0']  = $start_teacherTime.' - '.$end_teacherTime; // First column -> Teacher Time
            $row['1']  = $starttime.' - '.$endtime; // Second column -> Laos Time
            $row['2']  = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
        }
        if ($timeslot->day == TUTORSHIP_TUESDAY) {
            $row['3'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
        }
        if ($timeslot->day == TUTORSHIP_WEDNESDAY) {
            $row['4'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
        }
        if ($timeslot->day == TUTORSHIP_THURSDAY) {
            $row['5'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
        }
        if ($timeslot->day == TUTORSHIP_FRIDAY) {
            $row['6'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
         //   $timetable->data[] = $row;
           // unset($row);
        }
		if ($timeslot->day == TUTORSHIP_SATURDAY) {
            $row['7'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
           // $timetable->data[] = $row;
          //  unset($row);
        }
		if ($timeslot->day == TUTORSHIP_SUNDAY) {
            $row['8'] = tutorship_get_slot_link($timeslot->id, $USER->id, $selectedperiod, $urlparams);
            $timetable->data[] = $row;

			// Add New Split row.
					$spaceEdit_cell = new html_table_cell();
					$spaceEdit_cell->text = '';
					$spaceEdit_cell->colspan = 9;
					$spaceEdit_row = new html_table_row();
					$spaceEdit_row->cells[] = $spaceEdit_cell;
					$spaceEdit_row->style = "background-color:#FF0000";
					$timetable->data[] = $spaceEdit_row;
            unset($row);
        }
    }

    // Prints timetable period
	/*   2018-01-04 For Edit View
    echo $OUTPUT->box_start();
    echo $OUTPUT->container_start();
    echo '<center>';
    $introtextrecord      = $DB->get_record('tutorship_periods', array('id' => $selectedperiod));
    $introtextdescription = $introtextrecord->description;


	//print_r($year );die();//firstperiod
    $introtextstartdate   =  date('d/m/y', strtotime($f_year."-".$f_month."-".$f_day));//date('d/m/y', $introtextrecord->startdate);
    $introtextenddate     =  date('d/m/y', strtotime($e_year."-".$e_month."-".$e_day));//date('d/m/y', $introtextrecord->enddate);
    $introtext            = '<b>'.$introtextdescription.'</b> ('.$introtextstartdate.' - '.$introtextenddate.')';
    echo format_text($introtext);
    echo '</center>';
    echo $OUTPUT->container_end();
    echo $OUTPUT->box_end();
*/
    // Prints timetable
    echo html_writer::table($timetable);

    // Retreives information to initialize select fields
    $initreserves   = 50;//(int) $DB->get_field('tutorship_configs', 'maxreserves', $teacherconditions);
    $initconfirm    = 0;//(int) $DB->get_field('tutorship_configs', 'autoconfirm', $teacherconditions);
    $initnotify     = 1;//(int) $DB->get_field('tutorship_configs', 'notifications', $teacherconditions);

    // Gets periods and sets period array for select element
    $periods = $DB->get_records('tutorship_periods');
    if (isset($periods)) {
        $i = 1;
        $periodsdesc = array();
        foreach ($periods as $period) {
            $perioddate = '('.date('d/m/y', $period->startdate).' - '.date('d/m/y', $period->enddate).')';
            $periodsdesc[$i] = $period->description.' '.$perioddate;
            $i++;
        }
    }

    // Sets reserves array for select element
    $reserves = array();
    for ($i = 1; $i <= 6; $i++) {
        $reserves[$i] = $i;
    }

    // Sets attributes for selection elements
    $attributes = array('onchange' => 'this.form.submit()');

    // Starts box element
    echo $OUTPUT->box_start();
    echo $OUTPUT->container_start();

    // Prints introductory text
   // echo format_text(get_string('confsettingintro', 'tutorship'));
    echo '<p><div align=right>';

    // Prints period select element
    echo html_writer::start_tag('form', array('id' => 'configform', 'method' => 'post', 'action' => ''));
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="t" value="'.$course->id.'" />';
    echo '<input type="hidden" name="slotid" value="0" />';
    echo '<input type="hidden" name="action" value="2" />';
// Todo - implement sesskey in forms, may be using function is_post_with_sesskey().
//    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';



 /*   echo html_writer::start_tag('fieldset');
    echo html_writer::label(get_string('periodselect', 'tutorship'), 'selectperiodlabel');
    echo $OUTPUT->help_icon('periodselect', 'tutorship');
    echo html_writer::select($periodsdesc, 'selectedperiod', $selectedperiod, false, $attributes);
    echo html_writer::end_tag('fieldset');
	*/

/*
    // Prints max reserves select element
    echo html_writer::start_tag('fieldset');
    echo html_writer::label(get_string('reservesselect', 'tutorship'), 'selectreserveslabel');
    echo $OUTPUT->help_icon('reservesselect', 'tutorship');
    echo html_writer::select($reserves, 'maxreserves', $initreserves, false, $attributes);
    echo html_writer::end_tag('fieldset');

    // Prints autoconfirmation select element
    echo html_writer::start_tag('fieldset');
    echo html_writer::label(get_string('confirmselect', 'tutorship'), 'selectconfirmlabel');
    echo $OUTPUT->help_icon('confirmselect', 'tutorship');
    echo html_writer::select_yes_no('autoconfirm', $initconfirm, $attributes);
    echo html_writer::end_tag('fieldset');

    // Prints notifications select element
    echo html_writer::start_tag('fieldset');
    echo html_writer::label(get_string('notifyselect', 'tutorship'), 'selectnotifylabel');
    echo $OUTPUT->help_icon('notifyselect', 'tutorship');
    echo html_writer::select_yes_no('notify', $initnotify, $attributes);
    echo html_writer::end_tag('fieldset');
*/
    // Prints  select element
  /*
	echo html_writer::start_tag('fieldset');
    echo html_writer::label(get_string('noreserves', 'tutorship'), 'selectnoreserveslabel');
    echo $OUTPUT->help_icon('noreserves', 'tutorship');
    echo html_writer::select_yes_no('noreserves', $noreserves, $attributes);
    echo html_writer::end_tag('fieldset');
*/

    echo html_writer::end_tag('form');

    // Ends box element
    echo '</div></p>';
    echo $OUTPUT->container_end();
    echo $OUTPUT->box_end();

	}
	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';

}
else if($subpage == "teachermanagement")
{

	if (has_capability('mod/tutorship:teachermanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/teachermanage.php');
	}
}
else if($subpage == "leavemanagement")
{

	if (has_capability('mod/tutorship:leavemanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/leavemanage.php');
	}
}
else if($subpage == "teacherpayment" && $adminPermission != true)
{

		include ($CFG->dirroot.'/mod/tutorship/teacherpayment.php');
}
else if($subpage == "upcoming")
{

	if (has_capability('mod/tutorship:leavemanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/upcoming.php');
	}
}
else if($subpage == "contract")
{

	if (has_capability('mod/tutorship:leavemanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/contract.php');
	}
}
else if($subpage == "faq")
{

	if (has_capability('mod/tutorship:leavemanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/faq.php');
	}
}
else if($subpage == "tm")
{
	if (has_capability('mod/tutorship:teachermanage', $context)) {
	   // include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
		include ($CFG->dirroot.'/mod/tutorship/teachermanagement.php');
	}
}
