<?php 
if(isset($_GET['dataSendAjax'])) {
    require_once(__DIR__ . '/../../config.php');
    require_once($CFG->dirroot. '/course/lib.php');
    global $DB;
    $timefilter = $_GET['timefilter'];
    $timefilter2 = $_GET['timefilter2'];
    $courseid = $_GET['courseid'];
    /*echo $q = "SELECT u.*, ts.starttime  FROM {tutorship_timetables} AS tt
        LEFT JOIN {tutorship_timeslots} AS ts ON tt.timeslotid = ts.id
        LEFT JOIN {user} AS u ON tt.teacherid = u.id
        WHERE ts.starttime >= $timefilter AND ts.starttime <= $timefilter2 ORDER BY u.id";die();*/
    $teacherdetails = $DB->get_records_sql("
        SELECT u.*, ts.starttime  FROM {tutorship_timetables} AS tt
        LEFT JOIN {tutorship_timeslots} AS ts ON tt.timeslotid = ts.id
        LEFT JOIN {user} AS u ON tt.teacherid = u.id
        WHERE ts.starttime >= $timefilter AND ts.starttime <= $timefilter2 ORDER BY u.id
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
    // echo '<button type="submit" name="submit" class="btn btn-default schdl_button" id="schdl_button" disabled>Apply to schedule</button>';
    

}