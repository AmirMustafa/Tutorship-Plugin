<?php 
if(isset($_POST['availableSelectedTeacher'])) {
            session_start();
            $selectedStartTime             = gmdate('H:i', $_POST['selectedStartTime']).":00";  // for dropdown start time
            sscanf($selectedStartTime, "%d:%d:%d", $hours, $minutes, $seconds);
            $selectedStartTimestamp        = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
            $_SESSION["selectedStartTime"] = $_POST['selectedStartTime'];

            
            // end time
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
            $times =[];
            foreach ($teachertimeslots as $value) {
                $times[$value->starttime]['time'][$value->day] = (string) $value->id;
            }
            // sort array as per key i.e. time base sort
            ksort($times);
            foreach($times as $key=>$time){
                echo "<tr>";
                echo "<td class='timeDynamic'>". gmdate('H:i', $key - $offsetTimeZoneTeacher)."</td>";
                
                echo "<td class='timeDynamic'>". gmdate('H:i', $key - $offsetTimeZone)."</td>";
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
                        echo "<td class='inactive_slots' onclick='selected_slot()' style='color:#fff' title='This time slot is not available.'>Reserve Unreserve</td>";
                    }
                }
                echo "</tr>";
            }
            
            echo "</tr>";
            echo "</table>"; /* End table */
            echo "</div>";
            
}