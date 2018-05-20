<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.print.css"> -->

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
 * Prints a particular instance of tutorship for students view.
 *
 * The tutorship instance view that shows the teacher's tutoring
 * timetable configuration with time slots for student to reserve.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 *
 */
defined('MOODLE_INTERNAL') || die(); // Direct access to this file is forbidden
global $subpage;
global $USER, $DB, $CFG;

// Prints the heading
echo $OUTPUT->heading($OUTPUT->help_icon('studentheading', 'tutorship').format_string($tutorship->name));

// Security priviledges
require_login($course, true, $cm);
require_capability('mod/tutorship:reserve', $PAGE->context);

$tabparams = array();
$tabparams['id'] = $cm->id;
$tabparams['t'] = $course->id;

$actionurl = new moodle_url('/mod/tutorship/view.php', $tabparams);
$inactive = array();
echo "<br>";
// shows 3 tabs - schedule, upcoming, history
echo $output->studentview_tabs($context , $actionurl, $subpage, $inactive);     



if($subpage == "schedule" || $subpage == "")
{
    // Gets teachers enrolled in this course
    $teachers = tutorship_get_teachers($course->id);
    /*echo "<pre>";
    print_r($teachers);
    echo "</pre>";
    die();*/



    if (isset($teachers)) {

        // Sets enrolled teachers array
        $i = 1;
        $teachersfullnames = array();
        foreach ($teachers as $teacher) {
            $teachersfullnames[$i] = fullname($teacher);
            $i++;
        }

        // Sets necessary parameters for select element
        $nothingselection = array('0' => get_string('chooseteacher', 'tutorship'));
        $attributes = array('onchange' => 'this.form.submit()');

        // Starts box element
        /*echo $OUTPUT->box_start();
        echo $OUTPUT->container_start();


        // Prints teacher select element
        echo '<center>';
        echo html_writer::start_tag('form', array('id' => 'teacherform', 'method' => 'post', 'action' => ''));
        echo html_writer::label(get_string('teacherselect', 'tutorship'), 'selectteacherlabel');
        echo $OUTPUT->help_icon('teacherselect', 'tutorship');
        echo html_writer::select($teachersfullnames, 'selectedteacher', '0', $nothingselection, $attributes);
    // Todo - implement sesskey in forms, may be using function is_post_with_sesskey().
    //    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo html_writer::end_tag('form');
        echo '</center>';
        // Ends box element
        echo $OUTPUT->container_end();
        echo $OUTPUT->box_end();*/

        // Prints selected teacher
        
        // fetching form data on submit - amir 05.05.2018 
        
        }

        $courseModule = $_GET['id'];        // course module id
        if(isset($_POST['submit'])) {
            // echo $selectedStartTime           = "09:30:0";        //static data
            // start time
            session_start();
            
            
            $_SESSION["formsubmit"] = $_POST['submit'];
            // $selectedStartTime             = $_POST['selectedStartTime'].":00";              // for text box start time
            $selectedStartTime             = gmdate('H:i', $_POST['selectedStartTime']).":00";  // for dropdown start time
            sscanf($selectedStartTime, "%d:%d:%d", $hours, $minutes, $seconds);
            $selectedStartTimestamp        = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
            $_SESSION["selectedStartTime"] = $_POST['selectedStartTime'];

            
            // end time
            // $selectedEndTime               = $_POST['selectedEndTime'].":00";                // for text box end time
            $selectedEndTime               = gmdate('H:i', $_POST['selectedEndTime']).":00";      // for dropdown end time
            sscanf($selectedEndTime, "%d:%d:%d", $hours, $minutes, $seconds);
            $selectedEndTimestamp          = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
            $_SESSION["selectedEndTime"]   = $_POST['selectedEndTime'];

            // start date
            $selectedStartDate             = $_POST['selectedStartDate'];
            $selectedStartDateTimestamp    = strtotime(trim($_POST['selectedStartDate']));
            $weekdayStart                  = (date('N', $selectedStartDateTimestamp)); // 1-7
            $_SESSION["selectedStartDate"] = $_POST['selectedStartDate'];
            

            // end date
            $selectedEndDate               = $_POST['selectedEndDate'];
            $selectedEndDateTimestamp      = strtotime(trim($_POST['selectedEndDate']));
            $weekdayEnd                    = (date('N', $selectedEndDateTimestamp)); // 1-7
            $enddate_replace               = str_replace('-', '/', $selectedEndDate);
            $selectedEndDateNew            = date('m-d-Y',strtotime($enddate_replace . "+1 days"));
            $selectedEndDateNew_slash      = str_replace('-', '/', $selectedEndDateNew);
            $_SESSION["selectedEndDate"]   = $_POST['selectedEndDate'];
            
            // selected teacher
            $selectedteacher               = $_POST['teacher'];
            $_SESSION["teacherselected"]   = $_POST['teacher'];

            // teacher object - details
            if ($selectedteacher) {
                foreach ($teachers as $teacher) {
                    if (fullname($teacher) == $teachersfullnames[$selectedteacher]) {
                        $teacherobject = $teacher;
                    }
                }
                echo $OUTPUT->notification(fullname($teacherobject), 'notifysuccess');
                   
            }
            $periodid = tutorship_get_current_period($today); 
            
            
            // fetching data datewise filter
            $teachertimeslots = $DB->get_records_sql("
                SELECT t.*, s.day, s.starttime FROM {tutorship_timetables} AS t
                LEFT JOIN {tutorship_timeslots} AS s ON t.timeslotid = s.id
                WHERE t.teacherid = $teacherobject->id AND ( s.starttime >=  $selectedStartTimestamp AND s.starttime <=  $selectedEndTimestamp )
            ");
            
            

            // start date to end date
            
        } // End if

        // when form data is not received
        else {
            // echo 'data not received';
        }

        

        /*$selectedteacher =  $_POST['teacher'];
        if(isset($selectedteacher)) {
            foreach ($teachers as $teacher) {
                if (fullname($teacher) == $teachersfullnames[$selectedteacher]) {
                    $teacherobject = $teacher;
                }
            }
            echo $OUTPUT->notification(fullname($teacherobject), 'notifysuccess');
        }

        } else {
            echo '<center>';
            echo $OUTPUT->error_text(get_string('noteachers', 'tutorship'));
            echo '</center>';
        }*/
     
     // default code for listing data
     /*if ($selectedteacher) {
            foreach ($teachers as $teacher) {
                if (fullname($teacher) == $teachersfullnames[$selectedteacher]) {
                    $teacherobject = $teacher;
                }
            }
            echo $OUTPUT->notification(fullname($teacherobject), 'notifysuccess');
        }

    } else {
        echo '<center>';
        echo $OUTPUT->error_text(get_string('noteachers', 'tutorship'));
        echo '</center>';
    }*/
    
        

        /* ===================================== MYCODE:START ===================================== */
        // show data in form if data is selected or not
        /*if($_SESSION["selectedStartTime"]) { $selectedStartTimeSession = $_SESSION["selectedStartTime"]; } else { $selectedStartTimeSession = '';             }
        if($_SESSION["selectedEndTime"])   { $selectedEndTimeSession   = $_SESSION["selectedEndTime"];   } else { $selectedEndTimeSession   = '';             }
        if($_SESSION["selectedStartDate"]) { $selectedStartDateSession = $_SESSION["selectedStartDate"]; } else { $selectedStartDateSession = date('Y-m-d"'); }
        if($_SESSION["selectedEndDate"])   { $selectedEndDateSession   = $_SESSION["selectedEndDate"];   } else { $selectedEndDateSession   = '';             }
        if($_SESSION["teacherselected"])   { $selectedTeacherSession   = $_SESSION["teacherselected"];   } else { $selectedTeacherSession   = '';             }*/

        // show data in form if data is selected or not
        if($_POST["selectedStartTime"]) { $selectedStartTimeSession = $_POST["selectedStartTime"]; } else { $selectedStartTimeSession = '';             }
        if($_POST["selectedEndTime"])   { $selectedEndTimeSession   = $_POST["selectedEndTime"];   } else { $selectedEndTimeSession   = '';             }
        if($_POST["selectedStartDate"]) { $selectedStartDateSession = $_POST["selectedStartDate"]; } else { $selectedStartDateSession = date('Y-m-d"'); }
        if($_POST["selectedEndDate"])   { $selectedEndDateSession   = $_POST["selectedEndDate"];   } else { $selectedEndDateSession   = '';             }
        if($_POST["teacher"])           { $selectedTeacherSession   = $_POST["teacher"];           } else { $selectedTeacherSession   = '';             }

        $periodsSql = $admin_email = $DB->get_records_sql('SELECT startdate, enddate FROM {tutorship_periods}' );
        foreach($periodsSql as $p) {
            $periodStartDateftch = $p->startdate;
            $periodEndDateftch   = $p->enddate;
        }
        echo $periodStartDate;

        // these date are used for limiting the date picker to a period range in step1 process
        // echo $periodStartDate;
        /*$periodStartDate      = date("Y-m-d", $periodStartDateftch);
        $periodHumanStartDate = date("jS F, Y", $periodStartDateftch);*/
        $periodStartDate      = date("Y-m-d");
        $periodHumanStartDate = date("jS F, Y");
        $periodEndDate        = date("Y-m-d", $periodEndDateftch);
        $periodHumanEndDate   = date("jS F, Y", $periodEndDateftch);
        


        // STEP1 - Available date time div: Start
        echo "<br><br>";
        echo '<form action="" name="time_teacher_form" method="post">';       // Form starts
        echo '<div class="parent_student">';
        echo '<div class="available_time">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step1.png" style="margin-left:5%"> <span class="header_student">Your available Time</span></h6>';
        echo '<span class="header_student_sub">Time </span> <br>';
        echo '<div class="time_parent">';
        // echo '<input type="text" id="selectedStartTime" name="selectedStartTime" class="selectedTime form-control" placeholder="19:00" value="'.$selectedStartTimeSession.'" required>&emsp;&emsp; ';
        
        // fetching all timeslots dynamically - 10.05.2018
        $timeSlotsSql = $admin_email = $DB->get_records_sql('SELECT starttime FROM {tutorship_timeslots}' );
        $timeSlotArray = array();
        // $timeSlotArray = [28800, 31500, 34200, 36900, 39600, 42300, 45000, 47700, 50400, 53100, 55800, 58500, 61200, 63900, 66600, 69300, 72000, 74700];
        foreach($timeSlotsSql as $key => $v) {
            $timeSlotArray[] = $key;
        }

        
        echo '<select id="selectedStartTime" name="selectedStartTime" class="selectedTime form-control" onchange="availableTeachersAjax('.$COURSE->id.')" title="" required>';
        echo '<option value="">Start Time</option>';
        foreach($timeSlotArray as $key => $timeSlotVal) {
            if($timeSlotVal == $_POST['selectedStartTime']) { $startTimeCheck = 'selected';} else { $startTimeCheck = ''; }
            echo '<option value="'.$timeSlotVal.'" '.$startTimeCheck.'>'.gmdate('H:i',$timeSlotVal).'</option>';
        }
        echo '</select>';
        // echo '<input type="text" id="selectedEndTime" name="selectedEndTime" class="selectedTime form-control" placeholder="21:00" value="'.$selectedEndTimeSession.'" required>';
        
        echo '<select id="selectedEndTime" name="selectedEndTime" class="selectedTime form-control" required>';
        echo '<option value="">End Time</option>';
        foreach($timeSlotArray as $key => $timeSlotVal2) {
            if($timeSlotVal2 == $_POST['selectedEndTime']) { $startTimeCheck2 = 'selected';} else { $startTimeCheck2 = ''; }
            echo '<option value="'.$timeSlotVal2.'" '.$startTimeCheck2.'>'.gmdate('H:i',$timeSlotVal2).'</option>';
        }
        echo '</select>';

        echo '</div>'; //End time span
        echo '<br><br>';
        echo '<span class="header_student_sub">Dates</span> <br>';
        echo '<div class="date_parent">';
        echo '<input type="date" id="selectedStartDate" name="selectedStartDate" class="selectedDate form-control" placeholder="2018-09-19" value="'.$selectedStartDateSession.'" min="'.$periodStartDate.'" max="'.$periodEndDate.'" title="You can chose dates from '.$periodHumanStartDate.' to '.$periodHumanEndDate.' time period only" style="cursor:pointer" required>&emsp;&emsp;';
        echo '<input type="date" id="selectedEndDate" name="selectedEndDate" class="selectedDate form-control" placeholder="2018-12-20" value="'.$selectedEndDateSession.'" min="'.$periodStartDate.'" max="'.$periodEndDate.'" title="You can chose dates from '.$periodHumanStartDate.' to '.$periodHumanEndDate.' time period only" style="cursor:pointer" required>';
        echo '</div>'; //End date span schdl_button
        echo '<br><br>';
        echo '<button type="submit" name="submit" class="btn btn-default schdl_button" id="schdl_button">Apply to schedule</button>';
        echo '</div>'; // End available_time div
        // Available date time div: End

        // STEP2 - Available teachers: start
        // form is automatically submitted on radio button click using javascript - 05.05.2018
        echo '<div class="available_teachers">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step2.png" style="margin-left:5%"><span class="header_student"> Available Teachers</span></h6>';
        /*if(isset($_POST["selectedStartDate"])) {
            echo "<style>.teachersavailable {display:block!important;}</style>";
        }*/

        echo '<div class="select_teachers" id="select_teachers"></div>';       // select_teachers start
        
        // echo '</div>'; // End select_teachers div
        echo '</div>'; // End available_teachers div
        echo '</form>';
        // Available teachers: end
        
        // STEP3 - Actions: Start
        echo '<div class="actions">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step3.png" style="margin-left:5%"><span class="header_student"> Actions</span></h6>';
        echo '<a href="#" class="btn btn-default schdl_button2" id="deleteSchedule">Delete Schedule</a>';
        echo '<br><br>';
        echo '<button type="button" class="btn btn-default schdl_button2">Get help from Administrator</button>';
        echo '<br><br>';
        // echo '<button type="button" class="btn btn-success schdl_button2">Confirm Schedule</button>';
        echo '<a href="#" class="btn btn-success schdl_button2" id="confirmSchedule">Confirm Schedule</a>';
        echo '</div>';// End actions div 
        // Actions: End
        echo '</div>';// End parent_student div 
        /* ===================================== MYCODE:END ===================================== */
    

    // Shows the teacher timetable
    $periodid = tutorship_get_current_period($today);
    
    /* ====================== Code for displaying table data: Start ================= */
        if(isset($_POST['submit'])) {
            $period = new DatePeriod(
            new DateTime($selectedStartDate),
            new DateInterval('P1D'),
            new DateTime($selectedEndDateNew_slash)
            );
            // below date is for showing above table
            $humanStartDate = date('F j, Y', strtotime($selectedStartDate));
            $humanEndDate   = date('F j, Y', strtotime($selectedEndDate));
            
            echo "<div class='stdnt_outer_div'>";
            echo "<span style='margin-left: 4%; font-weight: 600;'>$humanStartDate - $humanEndDate</span>";
            echo "<div class='stdnt_inner_div'>";
            echo "<table>";
            echo "<tr>";
            echo "<th>Teachers Time</th>";
            // echo "<th>Student Time</th>";
            $daysFilter = array();
            foreach($period as $key => $value) {
                $date = $value->format('d/m/Y');
                $timestamp = strtotime($date);
                $dt = DateTime::createFromFormat('!d/m/Y', $date);
                
                // converting days to its equivalent code i.e 0-mon, 6-sunday
                if($dt->format('D') == "Mon") { $daycode = 0; }
                else if($dt->format('D') == "Tue") { $daycode = 1; }
                else if($dt->format('D') == "Wed") { $daycode = 2; }
                else if($dt->format('D') == "Thu") { $daycode = 3; }
                else if($dt->format('D') == "Fri") { $daycode = 4; }
                else if($dt->format('D') == "Sat") { $daycode = 5; }
                else { $daycode = 6; }
                
                $daysFilter[] = $daycode;
                echo "<th>".$dt->format('D')."<br>".$dt->format('M d')."</th>"; # 24 DEC
            }
            /*echo "<pre>";
            print_r($daysFilter);
            echo "-----";*/
            $times =[];
           
           
            foreach ($teachertimeslots as $value) {
                $times[$value->starttime]['time'][$value->day] = (string) $value->id;
                /*if (array_key_exists($value->starttime, $times)){
                }else{
                    $times[$value->starttime]['time']['day'] = Array((string)$value->day);
                    $times[$value->starttime]['time']['id'] = Array((string) $value->id);
                }*/
            }
            

                     // sort array as per key i.e. time base sort
            ksort($times);
            
            foreach($times as $key=>$time){
                echo "<tr>";
                echo "<td class='timeDynamic'>". gmdate('H:i', $key)."</td>";
                // echo "<td>". gmdate('H:i', $key)."</td>";

                foreach($daysFilter as $day){
                    $periodid;
                    $maxreserves = 500;
                    $autoconfirm = 1;
                    $notify      = 1;
                    $week        = 1;
                    $day;
                    $daynew = $day+1;                   // day
                    $timeslotTimestamp = $key;


                    /*$timeslotsql = $DB->get_records_sql("
                        SELECT id FROM {tutorship_timeslots} WHERE day = '".$day."' AND starttime = '".$timeslotTimestamp."' LIMIT 1  
                    ");
                    
                    foreach($timeslotsql as $key2 => $timesloteach) {
                        $timetableid = $key2;       // timeslot
                    }*/
                    

                    
                    
                    /* <a href='http://localhost/amir/moodle_tutorship/mod/tutorship/view.php?id=9&selectedperiod=1&maxreserves=50&autoconfirm=1&notify=1&week=1&timetableid=15'>Reserve</a></td>*/
                    if (array_key_exists($day, $time['time'])){
                        $timetableid = $time['time'][$day];
                        $dynamicId = 'reserverow_'.$timetableid.$day;
                        $reserveLink = $CFG->wwwroot.'/mod/tutorship/view.php?id='.$courseModule.'&selectedperiod='.$periodid.'&maxreserves='.$maxreserves.'&autoconfirm='.$autoconfirm.'&notify='.$notify.'&week='.$week.'&timetableid='.$timetableid;
                        // echo $reserveLink;echo "<br>";
                        
                        // this query is for checking reserve and unreserve condition
                        $reserveUnreserveSQL = $DB->get_record_sql("SELECT timetableid, confirmed FROM {tutorship_reserves} WHERE timetableid = $timetableid LIMIT 1");
                        
                        $reserveUnreserve        = $reserveUnreserveSQL->timetableid;
                        $reserveConfirmUnconfirm = $reserveUnreserveSQL->confirmed;
                        
                        ?>
                        <td class="active_slots <?= $dynamicId ?>" onclick="selected_slot('<?= $reserveLink; ?>', '<?= $dynamicId ?>', '<?= $reserveUnreserve ?>')" id="<?= $dynamicId ?>">
                             <?php 
                              if(isset($reserveUnreserve)) {
                                if($reserveConfirmUnconfirm == 1) {
                                    echo "<span class='span_ru' style='background: #558000;'>Unreserve <br>(confirmed)</span>"; 
                                } else {
                                    echo "<span class='span_ru' style='background: #558000;'>Unreserve <br> (not confirmed)</span>";
                                }
                              }
                              else {
                                echo "Reserve";
                              }
                              ?>
                        </td>
                        <?php
                    }else{
                        echo "<td class='inactive_slots' onclick='selected_slot()' title='This time slot is not available.'></td>";
                    }
                }
                echo "</tr>";
            }
            
            echo "</tr>";
            echo "</table>"; /* End table */
            echo "</div>";
            
            
        }
/* ====================== Code for displaying table data: End ================= */
/* ======================================== REMOVE DEFAULT: START ======================================== */
    if (isset($teacherobject) and tutorship_has_timetable($teacherobject->id, $periodid)) {
        // Gets necessary data for reservetable rows
        $timeslotlength = tutorship_get_slot_length();
        $teachertimeslots = $DB->get_records('tutorship_timetables', array('teacherid' => $teacherobject->id,
                                             'periodid' => $periodid), 'timeslotid');
        
        /* ===================== COMMENT CODE: START ===================== */
        // table
        $reservetable        = new html_table();
        $reservetable->head  = array();
        $reservetable->align = array();
        $reservetable->size  = array();
        $reservetable->attributes['class'] = 'generaltable borderClass';

        // getting start visible date to end visible date 
        $startdate = tutorship_get_dateFromWeek($today , 0);
        $enddate = tutorship_get_dateFromWeek($today , 6);
        echo "<center>$startdate - $enddate</center>";



        
        // header of the table
        $reservetable->head['0'] = "Europe London";
        $reservetable->head['1'] = get_string('monday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 0);
        $reservetable->head['2'] = get_string('tuesday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 1);
        $reservetable->head['3'] = get_string('wednesday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today ,2);
        $reservetable->head['4'] = get_string('thursday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 3);
        $reservetable->head['5'] = get_string('friday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 4);
        $reservetable->head['6'] = get_string('saturday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 5);
        $reservetable->head['7'] = get_string('sunday', 'tutorship')."<br>".tutorship_get_dateFromWeek($today , 6);

        // Reservetable properties
        for ($i = 0; $i <= 7; $i++) {   // From column 0-Hours to column 5-Friday
            $reservetable->align[$i] = 'center';
            $reservetable->size[$i]  = '10%';
        }

        // Reserve rows
        if ($teachertimeslots) {
            $row      = array();
            $slots    = array();
            $numslots = 0;

            // Necessary information to reserve
            $daynumber  = date('N', $today) - 1;


            // Sets time slots object array
            foreach ($teachertimeslots as $teachertimeslot) {
                $slots[$numslots] = $DB->get_record('tutorship_timeslots', array('id' => $teachertimeslot->timeslotid));
                $numslots++;
            }
            

            $maxCountStatus = tutorship_get_reserveMaxStatus($USER->id, $course->id);

            // Sets and adds rows to reservetable
            for ($i = 0; $i <= $numslots; $i++) {
                if ($slots[$i]->starttime == $slots[$i + 1]->starttime) {   // Same row
                    if (empty($row[0])) {                                   // First column: Hours
                        $row[0]  = gmdate('H:i', $slots[$i]->starttime).' - ';
                        $row[0] .= gmdate('H:i', $slots[$i]->starttime + $timeslotlength);
                    }

                    // Can't reserve today nor previous days for current week
                    if ((($slots[$i]->day > $daynumber) and ($week == 1)) or ($week == 2)  or ($week == 3) or ($week == 4)) {
                        // Adds element row cell
                        // Information for making reserve links
                        $timetableconditions = array('teacherid' => $teacherobject->id, 'periodid' => $periodid,
                                                     'timeslotid' => $slots[$i]->id);
                        $timetableid = $DB->get_field('tutorship_timetables', 'id', $timetableconditions);

                        /*echo "<pre>";
                        print_r($timetableid);
                        echo "</pre>";
                        die();*/

                        if($maxCountStatus == true)
                        {
                            $row[$slots[$i]->day + 1] = tutorship_get_reserve_Maxlink($timetableid, $USER->id, $course->id, $today, $urlparams);
                        }
                        else
                        {
                            $row[$slots[$i]->day + 1] = tutorship_get_reserve_link($timetableid, $USER->id, $course->id, $today, $urlparams);
                        }
                    } else {
                        $noreserve = $DB->get_field('tutorship_configs', 'noreserves', array('teacherid' => $teacherobject->id));
                        if ($noreserve) {
                            $row[$slots[$i]->day + 1] = format_text(get_string('singletutorship', 'tutorship'));
                        } else {
                            $row[$slots[$i]->day + 1] = format_text(get_string('reserve', 'tutorship'));
                        }
                    }
                } else { // End row set
                    if (empty($row[0])) {                                   // First column: Hours
                        $row[0]  = gmdate('H:i', $slots[$i]->starttime).' - ';
                        $row[0] .= gmdate('H:i', $slots[$i]->starttime + $timeslotlength);
                    }

                    // Can't reserve today nor previous days for current week
                    if ((($slots[$i]->day > $daynumber) and ($week == 1)) or ($week == 2) or ($week == 3) or ($week == 4)) {
                        // Adds element row cell
                        // Information for making reserve links
                        $timetableconditions = array('teacherid' => $teacherobject->id, 'periodid' => $periodid,
                                                     'timeslotid' => $slots[$i]->id);
                        $timetableid = $DB->get_field('tutorship_timetables', 'id', $timetableconditions);
                        if($maxCountStatus == true)
                        {
                            //$row[$slots[$i]->day + 1] = format_text(get_string('reserve', 'tutorship'));
                            $row[$slots[$i]->day + 1] = tutorship_get_reserve_Maxlink($timetableid, $USER->id, $course->id, $today, $urlparams);
                        }
                        else
                        {
                            $row[$slots[$i]->day + 1] = tutorship_get_reserve_link($timetableid, $USER->id, $course->id, $today, $urlparams);
                        }
                    } else {
                        $noreserve = $DB->get_field('tutorship_configs', 'noreserves', array('teacherid' => $teacherobject->id));
                        if ($noreserve) {
                            $row[$slots[$i]->day + 1] = format_text(get_string('singletutorship', 'tutorship'));
                        } else {
                            $row[$slots[$i]->day + 1] = format_text(get_string('reserve', 'tutorship'));
                        }
                    }

                    // Sets empty row cells
                    for ($j = 1; $j <= 7; $j++) {
                        if (empty($row[$j])) {
                            $row[$j] = null;
                        }
                    }

                    // Adds row to reservetable
                    $reservetable->data[] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
                    unset($row);

                        // Add New Split row.
                        $space_cell = new html_table_cell();
                        $space_cell->text = '';
                        $space_cell->colspan = 8;
                        $space_row = new html_table_row();
                        $space_row->cells[] = $space_cell;
                        $space_row->style = "background-color:#FF0000";
                        $reservetable->data[] = $space_row;

                    $row  = array();
                }
            }
        }
        //end
        // pushing selected teacher in urlparams array for working next buttons - amir 07.05.2018
        $urlparams['teacher']= $selectedteacher;
        
        // Next/Current week top link
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '<div align=right>';
        }
        echo tutorship_get_week_link($week, $urlparams);
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '</div>';
        }

        // Prints timetable
        echo html_writer::table($reservetable);

        // Next/Current week bottom link
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '<div align=right>';
        }
        echo tutorship_get_week_link($week, $urlparams);
        if ($week == 1 || $week == 2 || $week == 3 || $week == 4) {
            echo '</div>';
        }

        // You have reached max reserves message
        if (isset($reachedmaxreserves) and $reachedmaxreserves) {
            echo $OUTPUT->error_text(get_string('errreserves', 'tutorship'));
        }
    } else if (isset($teacherobject)) {
        echo '<center>';
        echo $OUTPUT->error_text(fullname($teacherobject).' '.get_string('notimetable', 'tutorship'));
        echo '</center>';
    }
        echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';
/* ======================================== REMOVE DEFAULT: END ======================================== */
}
else if($subpage == "upcoming")
{
        include ($CFG->dirroot.'/mod/tutorship/upcoming_student.php');

}
else if($subpage == "history")
{
        include ($CFG->dirroot.'/mod/tutorship/upcoming_history.php');

}

/* ===================== COMMENT CODE: END ===================== */
/*}
}*/
?>
<!-- auto submit form on radio button click - 05.05.2018-->
<script>
    // this function handels reserves and unreserves timeslot
    function selected_slot(reserveLink, reserverowid, reserveUnreserve) {
         // alert(reserverowid);
         
         // changing background of slot on click
         $("."+reserverowid).css("background", '#558000');
         // $('td[class^="active_slots"]').not('.'+reserverowid);
         
         $.ajax({
            url: "studentviewReserveAjax.php",
            type: "post",
            data: {reserveLink:reserveLink} ,
            success: function (res) {
                // alert(res);
                // passing link to confirm and delete button on clicking respective slot
                if(res !== "") {
                     $("#confirmSchedule").attr("href", res+"&insert=1&delete=0")
                     $("#confirmSchedule").css("cursor", 'pointer')
                     $("#confirmSchedule").attr("title", '');

                     $("#deleteSchedule").attr("href", res+"&insert=0&delete=1")
                     $("#deleteSchedule").css("cursor", 'pointer')
                     $("#deleteSchedule").attr("title", '');
                }
                // when that slot has no time schedule
                else {
                    $("#confirmSchedule").attr("href", '#')
                    $("#confirmSchedule").css("cursor", 'not-allowed')
                    $("#confirmSchedule").attr("title", '');

                    $("#deleteSchedule").attr("href", '#')
                    $("#deleteSchedule").css("cursor", 'not-allowed')
                    $("#deleteSchedule").attr("title", '');
                }
                // clicking unreserve case disables confirm schedule button
                if(reserveUnreserve !== "") {
                    $("#confirmSchedule").attr("href", '#');
                    $("#confirmSchedule").attr("title", 'You cannot use Confirm Schedule button to Unreserve Schedule . ');
                    $("#confirmSchedule").css("cursor", 'not-allowed');
                }
                // clicking reserve case disabled delete schedule button
                else {
                    $("#deleteSchedule").attr("href", '#');
                    $("#deleteSchedule").attr("title", 'You cannot use Delete Schedule button to Reserve Schedule . ');
                    $("#deleteSchedule").css("cursor", 'not-allowed');
                }
            }
        });

         

         // $("#"+timetableid+day ).css("background-color", "green");
    }

    // this function handels the availability of the teachers
    function availableTeachersAjax(courseid) {
        var dataSendAjax = 1;
        var timefilter = $('#selectedStartTime').val();
        
        /*$('.teachersavailable').css('display', 'block').fadeIn(3000);*/
        $.ajax({
            url: 'studentviewAjax.php',
            data:{
             dataSendAjax:dataSendAjax,
             courseid:courseid,
             timefilter:timefilter
            },
            type:'get',
            success: function(res) {
                 // alert(res);
                if(res !== '') {
                    $("#schdl_button").removeAttr("disabled");
                    $('#schdl_button').load('studentview #schdl_button')
                    $('#select_teachers').html(res);
                }
                else {
                    $('#schdl_button').attr('disabled', 'disabled');
                    $('#select_teachers').html("<span style='color:#ccc; text-align:center'>No teachers available from this <br> time slot</span>");
                }
            }
        });
    }
    // for changing  parent class backgrund i.e. background of unreserve span's parent i.e. its td 
    $( ".span_ru" ).parent().css( "background-color", "#558000" );


</script>
<style>
    #confirmSchedule, #deleteSchedule {
        cursor:not-allowed;
    }
    .userpicture {
        width: 3.5%;
        height: 3.5%;
        border-radius:0;;
    }
    
    .select_teachers {
        
        height:200px;
        overflow:scroll;
        *border:1px solid #000;
    }
    .teachersavailable {
        display:block;
    }
    
    .teacherleft {
        display: inline-block;
        padding-left:1%;
        padding-right:1%;
        padding-top:1%;
        vertical-align:top;
        width:45%;
    }

    .teacherright {
        display: inline-block;
        padding-left:1%;
        padding-right:1%;
        padding-top:1%;
        width:55%;

    }
    .teacher_right_inner {
        margin-top: 10%;
    }

    .view_teacher {
        color:#AF`3A0;
    }

    .cell.c0.lastcol {
        display: none;
    }
    
    .parent_student {
        display:block;
        width:100%;
    }
    .available_time, .available_teachers, .actions {
        display:inline-block;
        width:33%;
        height:250px!important;
    }
    
    .available_time {
        /* background:lavender; */
    }
    .actions {
        /* background:#ccc; */
        vertical-align:top;
    }
    .available_teachers {
        /* background:#ddd; */
        vertical-align:top;
    }

    .schdl_button{
        margin-left: 10%;
        width:75%;
        /* text-align:center; */
    }

    .schdl_button2{
        margin-left: 15%;
        margin-right: 15%;
        width:70%;
        /* text-align:center; */
    }
    .header_student {
        margin-left: 20%;
        font-weight:600;
        color:#347db3;
    }

    .header_student_sub {
        text-align:center;
        font-weight:600;
        color:#347db3;
        margin-left: 40%;
        margin-bottom: 0.5%;
    }
    .time_parent, .date_parent {
        /* margin-left: 15%; */
    }
    .selectedTime, .selectedDate {
        width: 40%;
        float: left;
        margin: 1%;
    }
    .time_parent, .date_parent {
        margin-left: 10%;
    }

    

    .stdnt_outer_div { overflow: scroll; width: 100%; height: 400px; }
    .stdnt_inner_div { max-width: 10000px; }
    .stdnt_inner_div table, .stdnt_inner_div tr, .stdnt_inner_div td, .stdnt_inner_div th {
        border:1px solid #000;
        padding: 15px 25px!important;
    }

    .stdnt_inner_div tr, .stdnt_inner_div td {
        width:30px;
    }

    .active_slots{
        background:#347db4;
        color:#fff;
        cursor:pointer;
    }
    .inactive_slots {
        cursor:not-allowed;
    }
    .inactive_slots:hover {
        /* background:#FDAEAE!important; */
        background:#FFF!important;
    }
    .timeDynamic:hover {
        background:#fff!important;
    }
    /* .teachersavailable {
        display:none;
    } */

    .stdnt_inner_div td:hover {
        background:#558000;
    }    


</style>
