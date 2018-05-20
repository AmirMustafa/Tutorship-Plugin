<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

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
global $USER, $DB, $CFG, $COURSE;

// Prints the heading
echo $OUTPUT->heading($OUTPUT->help_icon('studentheading', 'tutorship').format_string($tutorship->name));

// Security priviledges
require_login($course, true, $cm);
require_capability('mod/tutorship:reserve', $PAGE->context);

$tabparams = array();
$tabparams['id'] = $cm->id;
$tabparams['t']  = $course->id;

$actionurl = new moodle_url('/mod/tutorship/view.php', $tabparams);
$inactive = array();
echo "<br>";
// shows 3 tabs - schedule, upcoming, history
echo $output->studentview_tabs($context , $actionurl, $subpage, $inactive);     



if($subpage == "schedule" || $subpage == "")
{
    // Gets teachers enrolled in this course
    $teachers = tutorship_get_teachers($course->id);
    
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

        }

        $courseModule = $_GET['id'];        // course module id
        if(isset($_POST['submit'])) {
            session_start();
            $_SESSION["formsubmit"]        = $_POST['submit'];
            $st                            = $_POST['selectedStartTime'];
            $selectedStartTime             = gmdate('H:i', $_POST['selectedStartTime']).":00";  // for dropdown start time
            sscanf($selectedStartTime, "%d:%d:%d", $hours, $minutes, $seconds);
            $selectedStartTimestamp        = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
            $_SESSION["selectedStartTime"] = $_POST['selectedStartTime'];

            
            // end time
            $et                            = $_POST['selectedEndTime'];
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
        } // End if

        // show data in form if data is selected or not
        if($_POST["selectedStartTime"]) { $selectedStartTimeSession = $_POST["selectedStartTime"]; } else { $selectedStartTimeSession = '';             }
        if($_POST["selectedEndTime"])   { $selectedEndTimeSession   = $_POST["selectedEndTime"];   } else { $selectedEndTimeSession   = '';             }
        if($_POST["selectedStartDate"]) { $selectedStartDateSession = $_POST["selectedStartDate"]; } else { $selectedStartDateSession = date('Y-m-d"'); }
        if($_POST["selectedEndDate"])   { $selectedEndDateSession   = $_POST["selectedEndDate"];   } else { $selectedEndDateSession   = '';             }
        if($_POST["teacher"])           { $selectedTeacherSession   = $_POST["teacher"];           } else { $selectedTeacherSession   = '';             }

        $periodsSql = $DB->get_records_sql('SELECT startdate, enddate FROM {tutorship_periods}' );
        foreach($periodsSql as $p) {
            $periodStartDateftch = $p->startdate;
            $periodEndDateftch   = $p->enddate;
        }
        //echo $periodStartDate;

        // these date are used for limiting the date picker to a period range in step1 process
        $periodStartDate      = date("Y-m-d");
        $periodHumanStartDate = date("jS F, Y");
        $periodEndDate        = date("Y-m-d", $periodEndDateftch);
        $periodHumanEndDate   = date("jS F, Y", $periodEndDateftch);
        
        // getting student's country and timezone
        $studentCountrySql = $DB->get_record_sql('SELECT country, timezone FROM {user} WHERE id = ?', array($USER->id));
        $studentTimezone = $stz = $studentCountrySql->timezone;
        // $studentTimezone   = 'Asia/Kolkata';
        // these timezone are set as per admin configuration in mod/tutorship/view.php
        if($studentTimezone == '99')
        {
            $studentTimezone = $stz = 'Asia/Vientiane';
        }
        $offsetTimeZone = tutorship_get_timezone_offset($studentTimezone , 'Asia/Vientiane');

        $student_ID =  $studentInfo->username;
        $student_Name = $studentInfo->firstname . " " .$studentInfo->lastname;
        $nowUnixTime = strtotime("now");
        $deltaUnixTime = $unixtime - $nowUnixTime; 
    
        // getting teacher's country and timezone
        if(isset($_POST['availableSelectedTeacher'])) {
            $teacherCountrySql = $DB->get_record_sql('SELECT country, timezone FROM {user} WHERE id = ?', array($_POST['availableSelectedTeacher']));
            
           $ttz = $teacherCountrySql->timezone;
            if($ttz == '99')
            {
                $ttz = 'Asia/Vientiane';
            }
            $offsetTimeZoneTeacher = tutorship_get_timezone_offset($ttz , 'Asia/Vientiane');
            
        }

        // STEP1 - Available date time div: Start
        echo '<br><br>';
        echo '<form action="" name="time_teacher_form" id="time_teacher_form" method="post">';       // Form starts
        echo '<input type="hidden" name="availableSelectedTeacher" id="availableSelectedTeacher" value="2">';
        echo '<div class="parent_student">';
        echo '<div class="available_time">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step1.png" style="margin-left:5%"> <span class="header_student">Your available Time</span></h6>';
        echo '<span class="header_student_sub">Time </span> <br>';
        echo '<div class="time_parent">';
        
        // fetching all timeslots dynamically - 10.05.2018
        $timeSlotsSql = $admin_email = $DB->get_records_sql('SELECT starttime FROM {tutorship_timeslots}' );
        $timeSlotArray = array();
        
        foreach($timeSlotsSql as $key => $v) {
            $timeSlotArray[] = $key;
        }

        echo '<select id="selectedStartTime" name="selectedStartTime" class="selectedTime form-control" onchange="startTimeFunction()"  title="" required>';
        echo '<option value="">Start Time</option>';
        foreach($timeSlotArray as $key => $timeSlotVal) {
            if($timeSlotVal == $_POST['selectedStartTime']) { $startTimeCheck = 'selected';} else { $startTimeCheck = ''; }
            echo '<option value="'.$timeSlotVal.'" '.$startTimeCheck.'>'.gmdate('H:i',$timeSlotVal-$offsetTimeZone).'</option>';
        }
        echo '</select>';
        
        echo '<span style="cursor: not-allowed;"><select id="selectedEndTime" name="selectedEndTime" class="selectedTime form-control" onchange="availableTeachersAjax('.$COURSE->id.')" style="pointer-events:none; background: #ECEEEF;" required>';
        echo '<option value="">End Time</option>';
        foreach($timeSlotArray as $key => $timeSlotVal2) {
            if($timeSlotVal2 == $_POST['selectedEndTime']) { $startTimeCheck2 = 'selected';} else { $startTimeCheck2 = ''; }
            echo '<option value="'.$timeSlotVal2.'" '.$startTimeCheck2.'>'.gmdate('H:i',$timeSlotVal2-$offsetTimeZone).'</option>';
        }
        echo '</select></span>';

        echo '</div>'; //End time span
        echo '<br><br>';
        echo '<span class="header_student_sub">Dates</span> <br>';
        echo '<div class="date_parent">';
        echo '<input type="date" id="selectedStartDate" name="selectedStartDate" class="selectedDate form-control" placeholder="2018-09-19" value="'.$selectedStartDateSession.'" min="'.$periodStartDate.'" max="'.$periodEndDate.'" title="You can chose dates from '.$periodHumanStartDate.' to '.$periodHumanEndDate.' time period only" style="pointer-events:none; background: #ECEEEF" required>&emsp;&emsp;';

        echo '<input type="date" id="selectedEndDate" name="selectedEndDate" class="selectedDate form-control" placeholder="2018-12-20" value="'.$selectedEndDateSession.'" min="'.$periodStartDate.'" max="'.$periodEndDate.'" title="You can chose dates from '.$periodHumanStartDate.' to '.$periodHumanEndDate.' time period only" style="pointer-events:none; background: #ECEEEF"; required>';
        echo '</div>'; //End date span schdl_button
        echo '<br><br>';
        echo '</div>'; // End available_time div
        // Available date time div: End

        // STEP2 - Available teachers: start 
        // form is automatically submitted on radio button click using javascript - 05.05.2018
        echo '<div class="available_teachers">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step2.png" style="margin-left:5%"><span class="header_student"> Available Teachers</span></h6>';
        echo '<div class="parent">';       // select_teachers start
        // after submit getting data getting teacher's data
        if(isset($_POST['submit'])) {
            echo '<div class="select_teachers" id="select_teachers">';
            $teacherdetails = $DB->get_records_sql("
                SELECT u.*, ts.starttime  FROM {tutorship_timetables} AS tt
                LEFT JOIN {tutorship_timeslots} AS ts ON tt.timeslotid = ts.id
                LEFT JOIN {user} AS u ON tt.teacherid = u.id
                WHERE ts.starttime >= $st AND ts.starttime <= $et ORDER BY u.id
            ");
            $i=1;
                
            foreach($teacherdetails as $teacher) {
                    $userData = $DB->get_record_sql("SELECT country, picture FROM {user} WHERE id = '$teacher->id'");
                    $countryCode = $userData->country;
                    $userPicture = $userData->picture;
                    $countryCodeLowercase = strtolower($userData->country);
                    $countryFullnameArray = $DB->get_record_sql("SELECT country FROM {country_code} WHERE code = '$countryCode'");
                    $countryFullname = $countryFullnameArray->country;
                    
                    
                    $user = $DB->get_record('user', array('id' => $teacher->id));
                    $userpicture = $OUTPUT->user_picture($user);
                    $userurl = new moodle_url('/user/view.php', array('id' => $user->id));

                    // if teacher pic does not exist
                    if($userPicture == 0) {
                        $userpic = $CFG->wwwroot."/mod/tutorship/pix/teacher_dafault.png";
                    }
                    // if teacher pic exists
                    else {
                        $userpic = $CFG->wwwroot."/pluginfile.php/2".$teacher->id."/user/icon/f1";   
                    }
                    echo "<div class='teachersavailable'>"; // teachersavailable start
                    echo "<div class='teacherleft'>";       // teacher_left start
                    echo "<label for='".$teacher->id."'><img src='$userpic' width='82%'>";
                    echo "</div>";                          // teacher_left end
                    echo "<div class='teacherright'>";      // teacher_right start
                    
                    // for selecting currently submitted data
                    if($selectedTeacherSession == $i) {$currentcheck = 'checked';} else { $currentcheck = '';}
                    echo "<input type='radio' id='$teacher->id' class='teacherRadioInput' data-name='RadioButtons' name='teacher' value='".$i."' $currentcheck>&nbsp;<label for='$teacher->id'> $teacher->firstname $teacher->lastname </label>";
                    
                    
                    echo "<br>";

                    // if country is filled by the teacher, then get country name and flags 
                    if($countryCode !== "") {
                        echo "<img src='".$CFG->wwwroot."/mod/tutorship/flags/4_3/".$countryCodeLowercase.".svg' width='12%'>";
                        echo  "&nbsp;&nbsp;".$countryFullname;
                        // echo  "&nbsp;&nbsp;".$countryCode;
                        echo "<br>";
                    }
                    echo "<a href='".$userurl."' class='view_teacher'>View</a>";
                    
                    echo "</div>";                          // teacher_right end
                    echo "</div>";                          // teachersavailable end
                    
                    $i++;
            }   // End Foreach
            echo '</div>';
        }
        // first data is coming from AJAX request 
        else {
            echo '<div class="select_teachers" id="select_teachers"></div>';
        }
        // second step submit button
        echo '<div class="applybtndiv" id="applybtndiv"><button type="submit" name="submit" class="btn btn-default schdl_button" style="width:100%; margin-left:0; margin-top:8%" id="schdl_button" disabled>Apply to schedule</button></div></div>';

        echo '';       // select_teachers start
        
        echo '</div>'; // End available_teachers div
        echo '</form>';
        // Available teachers: end
        
        // STEP3 - Actions: Start
        echo '<div class="actions">';
        echo '<h6 class=""><img src="'.$CFG->wwwroot.'/mod/tutorship/pix/step3.png" style="margin-left:5%"><span class="header_student"> Actions</span></h6>';
        echo '<a href="#" class="btn btn-default schdl_button2" id="deleteSchedule" >Delete Schedule</a>';
        echo '<br><br>';
        echo '<button type="button" class="btn btn-default schdl_button2">Get help from Administrator</button>';
        echo '<br><br>';
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
            echo "<div style='text-align:center; font-weight: 600;margin-top:5%;'>$humanStartDate - $humanEndDate</div>";
            echo "<div class='stdnt_outer_div'>";
            echo "<div class='stdnt_inner_div'>";
            echo "<table>";
            echo "<tr>";
            echo "<th title='Teachers time slot ($ttz)'>Teachers Time</th>";
            echo "<th title='Yours equivalent time slot ($stz)'>Student Time</th>";
            
            $times =[];
            $daysFilter = array();
            $num = -1;
            foreach($period as $key => $value) {
                $num+=1;
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
                
                $daysFilter[$num]['day'] = $daycode;
                $daysFilter[$num]['week']=$dt->format('W');
                // echo "<th>".$dt->format('D')."<br>".$dt->format('M d')."<br>".$dt->format('W')."</th>"; # 24 DEC
                echo "<th>".$dt->format('D')."<br>".$dt->format('M d')."</th>"; # 24 DEC
                
                foreach ($teachertimeslots as $value) {
                    if($daycode == $value->day) {
                        /*echo $dt->format('W');
                        echo "<br>";*/
                        $times[$value->starttime]['week'][$dt->format('W')][$value->day]  = (string) $value->id;
                    }
                }
            }
            
            // sort array as per key i.e. time base sort
            ksort($times);
            foreach($times as $key=>$time){
                echo "<tr>";
                echo "<td class='timeDynamic'>". gmdate('H:i', $key - $offsetTimeZoneTeacher)."</td>";
                
                echo "<td class='timeDynamic'>". gmdate('H:i', $key - $offsetTimeZone)."</td>";
                // echo "<td>". gmdate('H:i', $key)."</td>";
                foreach($daysFilter as $dayArray){
                   $day = $dayArray['day'];
                   $week = $dayArray['week'];
                    
                    $periodid;
                    $maxreserves = 500;
                    $autoconfirm = 1;
                    $notify      = 1;
                    //$week        = 1;
                    
                    $daynew = $day+1;                   // day
                    $timeslotTimestamp = $key;

                        if (array_key_exists($day, $time['week'][$week])){
                        $timetableid = $time['week'][$week][$day];
                        // echo "week = ".$week;
                        $dynamicId = 'reserverow_'.$timetableid.$day.$week;
                        $reserveLink = $CFG->wwwroot.'/mod/tutorship/view.php?id='.$courseModule.'&selectedperiod='.$periodid.'&maxreserves='.$maxreserves.'&autoconfirm='.$autoconfirm.'&notify='.$notify.'&week='.$week.'&timetableid='.$timetableid;
                        $reserveUnreserveSQL = $DB->get_record_sql("SELECT timetableid, confirmed FROM {tutorship_reserves} WHERE timetableid = $timetableid AND week = $week LIMIT 1");
                        
                        $reserveUnreserve        = $reserveUnreserveSQL->timetableid;
                        $reserveConfirmUnconfirm = $reserveUnreserveSQL->confirmed;
                        
                        ?>
                        <td class="active_slots <?= $dynamicId ?>" onclick="selected_slot('<?= $reserveLink; ?>', '<?= $dynamicId ?>', '<?= $reserveUnreserve ?>', '<?= $CFG->wwwroot ?>')" id="<?= $dynamicId ?>">
                             <?php 
                             // reserved case
                              if(isset($reserveUnreserve)) {
                                // for confirm case of reserve
                                if($reserveConfirmUnconfirm == 1) {
                                    echo "<span class='span_ru' style='background: #558000;'>Unreserve <br>(confirmed)</span>"; 
                                }
                                // for not confirm case of reserve
                                else {
                                    echo "<span class='span_ru' style='background: #558000;'>Unreserve<br>(not confirmed)</span>";
                                }
                              }
                              // unreserved case
                              else {
                                echo "Reserve";
                              }
                              ?>
                        </td>
                        <?php
                    }else{
                        echo "<td class='inactive_slots' onclick='selected_slot()' style='color:#fff;' title='This time slot is not available.'>Reserve Unreserve</td>";
                    }
                }
                echo "</tr>";
            }
            
            echo "</tr>";
            echo "</table>"; /* End table */
            echo "</div>";
            
            
        }
/* ====================== Code for displaying table data: End ================= */



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

    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
    // this function handels reserves and unreserves timeslot
    function selected_slot(reserveLink, reserverowid, reserveUnreserve, projectBaseURL) {
         
         // changing background of slot on click
         $("."+reserverowid).css("background", '#558000').fadeIn("slow");
         $('td[class^="active_slots"]').not('.'+reserverowid);
         
         $.ajax({
            url: "studentviewReserveAjax.php",
            type: "post",
            data: {reserveLink:reserveLink} ,
            success: function (res) {
                var stdid          = getParameterByName('id', res); 
                var week           = getParameterByName('week', res);
                var timetableid    = getParameterByName('timetableid', res);
                var selectedperiod = getParameterByName('selectedperiod', res);
                var maxreserves    = getParameterByName('maxreserves', res);
                var autoconfirm    = getParameterByName('autoconfirm', res);
                var notify         = getParameterByName('notify', res);
                
                // passing link to confirm and delete button on clicking respective slot
                if(res !== "") {
                     $("#confirmSchedule").attr("href", res+"&insert=1&delete=0")
                     $("#confirmSchedule").css("cursor", 'pointer')
                     $("#confirmSchedule").attr("title", '');

                     // $("#deleteSchedule").attr("href", res+"&insert=0&delete=1")
                     $("#deleteSchedule").attr("href", projectBaseURL+"/mod/tutorship/studentReserveDelete.php?id="+stdid+"&timetableid="+timetableid+"&week="+week+"&selectedperiod="+selectedperiod+"&maxreserves="+maxreserves+"&autoconfirm="+autoconfirm+"&notify="+notify);
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


    function startTimeFunction() {
        // remove pointer event none from end time on start time change
        $('#selectedEndTime').css('pointer-events', 'auto');
        $('#selectedEndTime').css('background', 'transparent');
        $('#selectedEndTime').css('cursor', 'pointer');
    }
    
    // this function handels the availability of the teachers
    function availableTeachersAjax(courseid) {
        // remove pointer event none from start date, end date on end time change
        $('#selectedStartDate').css('pointer-events', 'auto');
        $('#selectedStartDate').css('background', 'transparent');
        $('#selectedStartDate').css('cursor', 'pointer');

        $('#selectedEndDate').css('pointer-events', 'auto');
        $('#selectedEndDate').css('background', 'transparent');
        $('#selectedEndDate').css('cursor', 'pointer');
        
        // enable end time on selecting start time
        var dataSendAjax = 1;
        var timefilter  = $('#selectedStartTime').val();
        var timefilter2 = $('#selectedEndTime').val();
        // alert(timefilter);
        
        /*$('.teachersavailable').css('display', 'block').fadeIn(3000);*/
        $.ajax({
            url: 'studentviewAjax.php',
            data:{
             dataSendAjax:dataSendAjax,
             courseid:courseid,
             timefilter:timefilter,
             timefilter2:timefilter2
            },
            type:'get',
            success: function(res) {
                //  alert(res);
                if(res !== '') {
                    $('#select_teachers').html(res);
                    $('#applybtndiv').html('<button type="submit" name="submit" class="btn btn-default schdl_button" style="width:100%; margin-left:0; margin-top:8%" id="schdl_button" disabled>Apply to schedule</button>');
                }
                else {
                    $('#schdl_button').attr('disabled', 'disabled');
                    $('#select_teachers').html("<span style='color:#ccc; text-align:center'>No teachers available from this <br> time slot</span>");
                }
                // on first submit enable submit button on radio button click
                $(".teacherRadioInput").each(function(index, element) {
                    $(".teacherRadioInput").click(function() {
                        $("#availableSelectedTeacher").val(this.id);
                        // $("#"+this.id).parent().parent().toggle().css('background','#E7E7E4');
                        // $('div[class^="teachersavailable-"]:not(#first-bar)').parent().parent().toggle().css('background','#fff');
                        $("#schdl_button").prop('disabled', false);
                    })
                });
                

            }
        });
    }

    // Other than first submit enable submit button on radio button click
    $(".teacherRadioInput").each(function(index, element) {
        $(".teacherRadioInput").click(function() {
            $("#availableSelectedTeacher").val(this.id);
            // $("#"+this.id).parent().parent().css('background','#E7E7E4');
            $("#schdl_button").prop('disabled', false);
        })
    });
    // for changing  parent class backgrund i.e. background of unreserve span's parent i.e. its td 
    $( ".span_ru" ).parent().css( "background-color", "#558000" );

    $(document).ready(function () {
        $('.teacherRadioInput').click(function () {
            $('.teacherRadioInput:not(:checked)').parent().parent().removeClass("style1");
            $('.teacherRadioInput:checked').parent().parent().addClass("style1");
        });
        $('.teacherRadioInput:checked').parent().parent().addClass("style1");
    });
    
    // enable apply to schedule button on radio button click
   
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

    

    .stdnt_outer_div { overflow: scroll; width: 100%; min-height: 330px; max-height: 1000px; }
    .stdnt_inner_div { max-width: 10000px; }
    .stdnt_inner_div table, .stdnt_inner_div tr, .stdnt_inner_div td, .stdnt_inner_div th {
        border:1px solid #000;
        padding: 15px 15px!important;
    }
    .stdnt_inner_div th {
        text-align: center;
    }
    .stdnt_inner_div tr, .stdnt_inner_div td {
        width:30px;
    }

    .active_slots{
        background:#347db4;
        color:#fff;
        cursor:pointer;
        /* padding: 17px !important;
        text-align: center !important; */
    }
    .inactive_slots {
        cursor:not-allowed;
        /* color:#fff; */
    }
    .inactive_slots:hover {
        /* background:#FDAEAE!important; */
        background:#FFF!important;
        color:transparent !important;
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
    .span_ru {
        font-size:90%;
    }    


</style>
