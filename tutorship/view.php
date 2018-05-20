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
 * Prints a particular instance of tutorship.
 *
 * The tutorship instance view that shows the teacher's tutoring
 * timetable configuration with time slots for student to reserve.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Includes all the required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
global $DB, $USER;

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Common parametres from POST or GET
$id                 = optional_param('id', 0, PARAM_INT);               // course_module ID, or
$t                  = optional_param('t', 0, PARAM_INT);                // tutorship instance ID
$selectedteacher    = optional_param('selectedteacher', 0, PARAM_INT);  // selected teacher from studentview
//$action             = optional_param('action', 1, PARAM_INT);           // teacher action (view or edit) from teacherview
$action             = optional_param('action', 2, PARAM_INT);           // teacher action (view or edit) from teacherview
$selectedperiod     = optional_param('selectedperiod', 1, PARAM_INT);   // selected period from teacherview
$maxreserves        = optional_param('maxreserves', 50, PARAM_INT);      // max number of reserves from teacherview
$autoconfirm        = optional_param('autoconfirm', 1, PARAM_INT);      // automatic confirmation from teacherview
$notify             = optional_param('notify', 1, PARAM_INT);           // send notifications from teacherview
$noreserves         = optional_param('noreserves', 0, PARAM_INT);       // disable student reserves from teacherview
$slotid             = optional_param('slotid', 0, PARAM_INT);           // teacher slotid from teacherview
$week               = optional_param('week', 1, PARAM_INT);             // current/next week selection from studentview
$timetableid        = optional_param('timetableid', 0, PARAM_INT);      // timetable id reserved from studentview
$reserveid          = optional_param('reserveid', 0, PARAM_INT);        // reserve id confirmed or cancel from teacherview
$cancell            = optional_param('cancell', 0, PARAM_INT);          // cancell reservation from teacherview
$delete             = optional_param('delete', 0, PARAM_INT);           // delete parameter


$teacherconditions  = array('teacherid' => $USER->id);                  // This will be use a few times

$subpage = optional_param('subpage', 'schedule', PARAM_ALPHA);

// select Year
$currentYear = date("Y");
$selYears            = optional_param('sel_years', $currentYear, PARAM_INT);          // cancell reservation from teacherview
if($selYears == 0 || $selYears == "")
    $selYears = $currentYear;

// select Year
$currentMonth = date("m");
$selMonth            = optional_param('sel_months', $currentMonth, PARAM_INT);          // cancell reservation from teacherview
if($selMonth == 0 || $selMonth == "")
    $selMonth = $currentMonth;

$rate_value = optional_param('rate_value', 7.5 , PARAM_FLOAT); 

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Retrieves necessary information from database
if ($id) {
    if (! $cm = get_coursemodule_from_id('tutorship', $id, 0, false, MUST_EXIST)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
        print_error('coursemisconf');
    }
    if (! $tutorship = $DB->get_record('tutorship', array('id' => $cm->instance), '*', MUST_EXIST)) {
        print_error('invalidcoursemodule');
    }
} else if ($t) {
    if (! $tutorship = $DB->get_record('tutorship', array('id' => $t), '*', MUST_EXIST)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $tutorship->course), '*', MUST_EXIST)) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('tutorship', $tutorship->id, $course->id, false, MUST_EXIST)) {
        print_error('invalidcoursemodule');
    }
} else {
    print_error('missingparameter');
}




///////////////////////////////////////////////////////////////////////////////////////////////////////
// Security login priviledges, context and records user activity
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tutorship:view', $context);


// For Tab Page
$PAGE->set_url('/mod/tutorship/view.php', array('id' => $cm->id));
$output = $PAGE->get_renderer('mod_tutorship');



// Trigger course_module_viewed event.
$params = array(
        'context' => $context,
        'objectid' => $tutorship->id
);

$event = \mod_tutorship\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('tutorship', $tutorship);
$event->trigger();

//add_to_log($course->id, 'tutorship', 'view', 'view.php?id='.$cm->id, $tutorship->name, $cm->id);
if (has_capability('mod/tutorship:update', $context)) { // Only teachers can do this
// Todo - implement sesskey in forms, may be using function is_post_with_sesskey().
// echo "Hiii again..";die();
///////////////////////////////////////////////////////////////////////////////////////////////////////
// Enables/Disables time slot within a timetable
if ($slotid) {
    if ($selectedperiod) {
        $slotstableconditions = array('teacherid' => $USER->id, 'periodid' => $selectedperiod, 'timeslotid' => $slotid);
    } else {
        $slotstableconditions = array('teacherid' => $USER->id, 'timeslotid' => $slotid);
    }

    // Records user activity
    //add_to_log($course->id, 'tutorship', 'editslot', 'teacherview.php?id='.$cm->id, $tutorship->name, $cm->id);

    // Trigger an event for updating this field.
    $event = \mod_tutorship\event\edit_slot::create(array(
            'objectid' => $slotid,
            'userid' => $USER->id,
            'context' => $context,
            'other' => array(
                    'tutorshipname' => $tutorship->name
            )
    ));
    $event->trigger();

    // If there is a time slot in timetable then delete, otherwise create it.
    if ($DB->record_exists('tutorship_timetables', $slotstableconditions)) {

        // Sends out mail to inform students who reserved those time slots and removes reserves
        $teachertimetableid = $DB->get_field('tutorship_timetables', 'id', $slotstableconditions);
        $reserves = $DB->get_records('tutorship_reserves', array('timetableid' => $teachertimetableid));
        if ($reserves) {
            $site     = get_site();
            $subject  = format_string($site->shortname).': '.format_string($course->shortname).': ';
            $subject .= get_string('reservationcancelled', 'tutorship');
            $message  = '<p>'.format_string($site->fullname).': '.format_string($course->fullname).': ';
            $message .= get_string('modulename', 'tutorship').'.</p>';
            $message .= get_string('reservationcancelledtxt', 'tutorship');
            $message .= '<b>'.format_string(fullname($USER)).'</b>.<br>';
            $message .= get_string('reservationdetails', 'tutorship');
            foreach ($reserves as $reserve) {
                //print_object('^^^^^');
                $message .= tutorship_get_reserve_date($DB->get_field('tutorship_reserves', 'timetableid',
                                                       array('id' => $reserve->id)), $reserve->studentid);
                $to = $DB->get_record('user', array('id' => $reserve->studentid));    
                // Deletes timetable reserves
                if ($DB->delete_records('tutorship_reserves', array('id' => $reserve->id))) {
                    //exit('1');
                    // Email to student
                    $tutocalendars = $DB->get_records('tutorship_calendar', array('reserveid' => $reserve->id));
                    if ($tutocalendars) {
                        foreach ($tutocalendars as $tutorcalendar) {
                            $subscription = $DB->get_record('event_subscriptions', array('id' => $tutorcalendar->subid));
                            if ($subscription) {
                                calendar_delete_subscription($subscription);
                            }
                        }
                    }

                    if (! email_to_user($to, $USER, $subject, null, $message)) {
                        print_error('erremail', 'tutorship');
                    }   
                } else {
                    print_error('errcancelconfirm', 'tutorship');
                }
            }
        }

        // Deletes time slot from timetable
        if (! $DB->delete_records('tutorship_timetables', $slotstableconditions)) {
            print_error('errslotdelete', 'tutorship');
        }

    } else {
        if (! tutorship_insert_timetable($USER->id, $selectedperiod, $slotid)) {
            print_error('errtimetable', 'tutorship');
        }
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Updates maximum number of reserves per student
if ($maxreserves) {
    $configuration = $DB->get_record('tutorship_configs', $teacherconditions);

    // In case the teacher changes the maximum number of reserves
    if (! $configuration) { // If there was not any configuration added yet, adds
        if (! tutorship_insert_teacher_config($USER->id, '0', '1', $maxreserves, '0')) {
            print_error('errconfig', 'tutorship');
        }
    } else { // updates
        if ($configuration->maxreserves != $maxreserves) {
            if ($DB->set_field('tutorship_configs', 'maxreserves', $maxreserves, $teacherconditions)) {
                // Records user activity
                //add_to_log($course->id, 'tutorship', 'edit maxreserves', 'teacherview.php?id='.$cm->id,
                //           $tutorship->name, $cm->id);
                $event = \mod_tutorship\event\edit_maxreserves::create(array(
                        'objectid' => $maxreserves,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();
            } else {
                print_error('errmaxreserves', 'tutorship');
            }
        }
    }
} else if ($DB->record_exists('tutorship_configs', $teacherconditions)) {
    $maxreserves = (int) $DB->get_field('tutorship_configs', 'maxreserves', $teacherconditions);
} else {
    $maxreserves = 3;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Enables/Disables automatic confirmation
if (isset($autoconfirm)) {
    $slotid = 0;
    $configuration = $DB->get_record('tutorship_configs', $teacherconditions);

    // In case the teacher changes the automatic confirmation
    if (! $configuration) { // If there was not any configuration added yet, adds
        if (! tutorship_insert_teacher_config($USER->id, $autoconfirm, '1', '3', '0')) {
            print_error('errconfig', 'tutorship');
        }
    } else { // updates
        if ($configuration->autoconfirm != $autoconfirm) {
            if ($DB->set_field('tutorship_configs', 'autoconfirm', $autoconfirm, $teacherconditions)) {
                // Records user activity
                //add_to_log($course->id, 'tutorship', 'edit confirmation', 'teacherview.php?id='.$cm->id,
                //           $tutorship->name, $cm->id);
                $event = \mod_tutorship\event\edit_confirmation::create(array(
                        'objectid' => $autoconfirm,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();
            } else {
                print_error('errconfirm', 'tutorship');
            }
        }
    }
} else if ($DB->record_exists('tutorship_configs', $teacherconditions)) {
    $autoconfirm = (int) $DB->get_field('tutorship_configs', 'autoconfirm', $teacherconditions);
} else {
    $autoconfirm = 0;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Enables/Disables email notifications
if (isset($notify)) {   
    $configuration = $DB->get_record('tutorship_configs', $teacherconditions);

    // In case the teacher changes the notifications
    if (! $configuration) { // If there was not any configuration added yet, adds
        if (! tutorship_insert_teacher_config($USER->id, '0', $notify, '3', '0')) {
            print_error('errconfig', 'tutorship');
        }
    } else { // updates
        if ($configuration->notifications != $notify) {
            if ($DB->set_field('tutorship_configs', 'notifications', $notify, $teacherconditions)) {
                // Records user activity
                //add_to_log($course->id, 'tutorship', 'edit notifications', 'teacherview.php?id='.$cm->id,
                //           $tutorship->name, $cm->id);
                $event = \mod_tutorship\event\edit_notifications::create(array(
                        'objectid' => $notify,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();
            } else {
                print_error('errnotify', 'tutorship');
            }
        }
    }
} else if ($DB->record_exists('tutorship_configs', $teacherconditions)) {
    $notifications = (int) $DB->get_field('tutorship_configs', 'notifications', $teacherconditions);
} else {
    $notifications = 1;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Enables/Disables reservations
if (isset($noreserves)) {
    $configuration = $DB->get_record('tutorship_configs', $teacherconditions);

    // In case the teacher changes the noreserves
    if (! $configuration) { // If there was not any configuration added yet, adds
        if (! tutorship_insert_teacher_config($USER->id, '0', $notify, '3', $noreserves)) {
            print_error('errconfig', 'tutorship');
        }
    } else { // updates
        if ($configuration->noreserves != $noreserves) {
            if ($DB->set_field('tutorship_configs', 'noreserves', $noreserves, $teacherconditions)) {
                // Records user activity
                //add_to_log($course->id, 'tutorship', 'edit noreserves', 'teacherview.php?id='.$cm->id,
                //           $tutorship->name, $cm->id);
                $event = \mod_tutorship\event\edit_noreserves::create(array(
                        'objectid' => $noreserves,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();
            } else {
                print_error('errnoreserves', 'tutorship');
            }
        }
    }
} else if ($DB->record_exists('tutorship_configs', $teacherconditions)) {
    $noreserves = (int) $DB->get_field('tutorship_configs', 'noreserves', $teacherconditions);
} else {
    $noreserves = 0;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Confirm or cancel reservations
if ($reserveid) {
    $site        = get_site();
    $studentid   = $DB->get_field('tutorship_reserves', 'studentid', array('id' => $reserveid));
    $to          = $DB->get_record('user', array('id' => $studentid));
    $subject     = format_string($site->shortname).': '.format_string($course->shortname).': ';
    $message     = '<p>'.format_string($site->fullname).': '.format_string($course->fullname).': ';
    $message    .= get_string('modulename', 'tutorship').'.</p>';

    if ($cancell) {
        // Cancell and clear reservation
        if ($DB->record_exists('tutorship_reserves', array('id' => $reserveid))) {

            // Records user activity
            //add_to_log($course->id, 'tutorship', 'reserve cancell', 'studentview.php?id='.$cm->id, $tutorship->name,
            //           $cm->id);
            $event = \mod_tutorship\event\cancel_slot::create(array(
                    'objectid' => $reserveid,
                    'userid' => $USER->id,
                    'context' => $context,
                    'other' => array(
                            'tutorshipname' => $tutorship->name
                    )
            ));
            $event->trigger();
            // Email settings
            $subject .= get_string('reservationcancelled', 'tutorship');
            $message .= get_string('reservationcancelledtxt', 'tutorship');
            $message .= ' <b>'.format_string(fullname($USER)).'</b>.<br>';
            $message .= get_string('reservationdetails', 'tutorship');
            $message .= tutorship_get_reserve_date($DB->get_field('tutorship_reserves', 'timetableid',
                                                                  array('id' => $reserveid)), $studentid);
            //print_object('+++++');
            // Teacher has cancelled a reservation request

            $existingevents = $DB->get_record('event', array( 'uuid' => $reserveid));
                    if($existingevents)
                    {

//                      $deleteEvent = calendar_event::load($existingevents->id);
//                       $deleteEvent->delete(false);
                        $DB->delete_records('event', array('uuid' => $reserveid));
                        //$DB->delete_records('event_subscriptions', array('uuid' => $reserveid));
                    }

            if ($DB->delete_records('tutorship_reserves', array('id' => $reserveid))) {
                //exit('2');
                //calendar_delete_subscription($subscriptionid);
                $tutocalendars = $DB->get_records('tutorship_calendar', array('reserveid' => $reserveid));
                if ($tutocalendars) {
                    foreach ($tutocalendars as $tutorcalendar) {
                        $subscription = $DB->get_record('event_subscriptions', array('id' => $tutorcalendar->subid));
                        if ($subscription) {
                            calendar_delete_subscription($subscription);
                        }
                    }
                }
                // Email to student
                if (! email_to_user($to, $USER, $subject, null, $message)) {
                    print_error('erremail', 'tutorship');
                }

            } else {
                print_error('errcancelconfirm', 'tutorship');
            }

        }
    } else {
        // Confirm reservation
        if ($DB->record_exists('tutorship_reserves', array('id' => $reserveid, 'confirmed' => '0'))) {

            // Get how many reserves user already have in that course.
            $userreservers = $DB->get_records('tutorship_reserves', array('courseid' => $cm->course, 'studentid' => $studentid));
        
            if ($userreservers) {
                $reservations  = count($userreservers);
            }
    
            $sectiontitle = '';
            $sectioname = $DB->get_record('course_sections', array('section' => $reservations, 'course' => $cm->course));

            /*if ($sectioname->name) {
                $sectiontitle .= $sectioname->name;
            } else {
                $sectiontitle .= 'Topic '.$reservations;
            }*/

            // Records user activity
            //add_to_log($course->id, 'tutorship', 'reserve confirm', 'studentview.php?id='.$cm->id, $tutorship->name,
            //           $cm->id);
            // Teacher has confirmed a reservation request

            if ($DB->set_field('tutorship_reserves', 'confirmed', 1, array('id' => $reserveid))) {
                $event = \mod_tutorship\event\confirm_slot::create(array(
                        'objectid' => $reserveid,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();

            $topicList = $DB->get_records('course_sections', array('course' => $cm->course));
            //foreach ($topicList as $topicData) 
            //{
                //if($topicData->section != "0")
            //  {
                    /*if ($topicData->name) {
                        $sectiontitle .= $sectioname->name;
                    } else {
                        $sectiontitle = 'Topic '.$topicData->section;
                    }*/
                     // Add to calendar.
                    // Get the time.
                    $reserveconditions = array('timetableid' => $DB->get_field('tutorship_reserves', 'timetableid',
                            array('id' => $reserveid)), 'studentid' => $studentid);
                    $timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $DB->get_field('tutorship_reserves', 'timetableid',
                            array('id' => $reserveid))));

                    
                    /*$selReservedID = $reserveconditions['timetableid'];
                    $sectioname = $DB->get_record('course_sections', array('section' => $selReservedID, 'course' => $cm->course));

                    if ($sectioname->name) {
                        $sectiontitle = $sectioname->name;
                    } else {
                        $sectiontitle = 'Topic '. $selReservedID;
                    }*/

                    $timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeslotid));
                    

                    // Get Topic Number from StartTime
                    $starttime   = TUTORSHIP_STARTTIME * 60 * 60;
                    $breaktime =  (int)TUTORSHIP_BREAKTIME_MINUTES * 60;
                    
                    $reTime = $timeslot->starttime;
                    

                    $deltaTime = $reTime - $starttime;

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
                    
                    $sectioname = $DB->get_record('course_sections', array('section' => $sectionPos, 'course' => $cm->course));

                    if ($sectioname->name) {
                        $sectiontitle = $sectioname->name;
                        
                    } else {
                        $sectiontitle = 'Topic '. $sectionPos;
                    }


                

                    // Get Topic Number from StartTime End

                    $week       = $DB->get_field('tutorship_reserves', 'week', $reserveconditions);

                    $year       = date('Y', time());
                    $time       = gmdate('H:i', $timeslot->starttime);
                    $daynumber  = date('d', tutorship_get_date($timeslot->day, $week, $year));
                    $month      = date('m', tutorship_get_date($timeslot->day, $week, $year));

                    $unixtime = mktime(0, 0, 0, (int) $month, (int) $daynumber, (int) $year);
                    $unixtime += $timeslot->starttime;
                    // End getting time

                    // Get Student inforamtion
                    $studentInfo = $DB->get_record('user', array('id' => $studentid));
                    $studentTimeZone = $studentInfo->timezone;
                    if($studentTimeZone == '99')
                    {
                        $studentTimeZone = "Asia/Vientiane";
                    }
                    $offsetTimeZone = tutorship_get_timezone_offset($studentTimeZone , 'Asia/Vientiane');

                    $student_ID =  $studentInfo->username;
                    $student_Name = $studentInfo->firstname . " " .$studentInfo->lastname;
                    $nowUnixTime = strtotime("now");
                    $deltaUnixTime = $unixtime - $nowUnixTime;

                    $studenttime    = gmdate('H:i', $timeslot->starttime - $offsetTimeZone);

                    $sDate = $year ."-" . $month."-".$daynumber . " ".$studenttime;
                    //$eventDesc = $sectiontitle . " | " . $sDate . " | " . $student_Name . " | " . $student_ID;
                    $eventDesc = "<table  cellpadding='8'><tr><td>Topic</td><td>Student Time</td><td>Student Name</td><td>Student ID</td><td>Session ID</td><td rowspan=2><a href='view.php?id='" .$cm->course. " style='color:red'> Enter course</a></td></tr>";
                    $eventDesc .= "<tr><td>".$sectiontitle . "</td>";
                    $eventDesc .= "<td>".$sDate . "</td>";
                    $eventDesc .= "<td>".$student_Name . "</td>";
                    $eventDesc .= "<td>".$studentid . "</td>";
                    $eventDesc .= "<td>".$reserveid . "</td></tr></table>";
                    //$eventDesc .= "Topic    ,Date               ,Student Name     ,Student ID \/n";
                    //$eventDesc .= $sectiontitle . " | " . $sDate . " | " . $student_Name . " | " . $studentid;


                    //Teacher event
                    $sub = new stdClass();
                    $sub->url = '';
                    $sub->courseid = 0;
                    $sub->groupid = 0;
                    $sub->userid = $USER->id;
                    $sub->pollinterval = 0;
                    $subid = $DB->insert_record('event_subscriptions', $sub, true);

                    $event = new stdClass();
                    //2017-11-10 $event->name = $sectiontitle.':'.$tutorship->name;
                    $event->name =$tutorship->name;

                    $event->timestart = $unixtime;
                    $event->timeduration = 1800;
                    $event->description = $eventDesc;//$sectiontitle;
                    $event->uuid = $reserveid;//'uuid';
                    $event->repeatid = $studentid;
                    //$event->subscriptionid = $subid;
                    $event->userid = $USER->id;
                    $event->groupid = 0;
                    $event->courseid = 0;
                    $event->eventtype = 'user';
                    $eventobj = calendar_event::create($event, false);
                    //End teacher event
                    $teacherobject = new stdClass();
                    $teacherobject->reserveid = $reserveid;
                    $teacherobject->subid = $subid;
                    $teacherobject->timemodified = time();
                    $DB->insert_record('tutorship_calendar', $teacherobject);
                    //Student event
                    $sub = new stdClass();
                    $sub->url = '';
                    $sub->courseid = 0;
                    $sub->groupid = 0;//$reserveid;;
                    $sub->userid = $studentid;
                    $sub->pollinterval = 0;
                    $subid = $DB->insert_record('event_subscriptions', $sub, true);

                    $event = new stdClass();
                    //2017-11-10 $event->name = $sectiontitle.':'.$tutorship->name;
                    $event->name = $tutorship->name;
                    $event->description = $eventDesc;//$sectiontitle;
                    $event->timestart = $unixtime;
                    $event->timeduration = 1800;
                    //$event->description = 'custom description:'.$tutorship->name;
                    $event->uuid = $reserveid;//'uuid';
                    $event->repeatid = $studentid;
                    
                    //$event->subscriptionid = $subid;
                    $event->userid = $studentid;
                    $event->groupid = 0;
                    $event->courseid = 0;
                    $event->eventtype = 'user';
                    $eventobj = calendar_event::create($event, false);
                    //End student event
                    $studentobject = new stdClass();
                    $studentobject->reserveid = $reserveid;
                    $studentobject->subid = $subid;
                    $studentobject->timemodified = time();
                    $DB->insert_record('tutorship_calendar', $studentobject);

            //  }
            //}
                
 
                // Email to student
                $subject .= get_string('reservationconfirmed', 'tutorship');
                $message .= get_string('reservationconfirmedtxt', 'tutorship');
                $message .= ' <b>'.format_string(fullname($USER)).'</b>.<br>';
                $message .= get_string('reservationdetails', 'tutorship'); 
                $message .= tutorship_get_reserve_date($DB->get_field('tutorship_reserves', 'timetableid',
                                                                      array('id' => $reserveid)), $studentid);
                if (! email_to_user ($to, $USER, $subject, null, $message)) {
                    print_error('erremail', 'tutorship');
                }
    
            } else {
                print_error('errconfirmation', 'tutorship');
            }

        // Cancel and clear reservation
        } else if ($DB->record_exists('tutorship_reserves', array('id' => $reserveid, 'confirmed' => '1'))) {
            // Records user activity
            //add_to_log($course->id, 'tutorship', 'reserve cancell', 'studentview.php?id='.$cm->id, $tutorship->name,
            //           $cm->id);
            if ($cancell) {
                $event = \mod_tutorship\event\cancel_slot::create(array(
                        'objectid' => $reserveid,
                        'userid' => $USER->id,
                        'context' => $context,
                        'other' => array(
                                'tutorshipname' => $tutorship->name
                        )
                ));
                $event->trigger();

                // Email settings
                $subject .= get_string('reservationcancelled', 'tutorship');
                $message .= get_string('reservationcancelledtxt', 'tutorship');
                $message .= ' <b>' . format_string(fullname($USER)) . '</b>.<br>';
                $message .= get_string('reservationdetails', 'tutorship');
                $message .= tutorship_get_reserve_date($DB->get_field('tutorship_reserves', 'timetableid',
                        array('id' => $reserveid)), $studentid);

                // Teacher has cancelled a reservation request

                    $existingevents = $DB->get_record('event', array( 'uuid' => $reserveid));
                    if($existingevents)
                    {

                        $deleteEvent = calendar_event::load($existingevents->id);
                         $deleteEvent->delete(false);
                        //$DB->delete_records('event', array('uuid' => $reserveid));
                        //$DB->delete_records('event_subscriptions', array('uuid' => $reserveid));
                    }


                if ($DB->delete_records('tutorship_reserves', array('id' => $reserveid))) {
                    $tutocalendars = $DB->get_records('tutorship_calendar', array('reserveid' => $reserveid));
                    if ($tutocalendars) {
                        foreach ($tutocalendars as $tutorcalendar) {
                            // For Delete Event 
                            $subscription = $DB->get_record('event_subscriptions', array('id' => $tutorcalendar->subid));
                            if ($subscription) {
                                calendar_delete_subscription($subscription);
                            }
                        }
                    }
                    // Email to student
                    if (!email_to_user($to, $USER, $subject, null, $message)) {
                        print_error('erremail', 'tutorship');
                    }

                } else {
                    print_error('errcancelconfirm', 'tutorship');
                }
            }

        }
    }
}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Establishes current week or next week time
if ($week == 1) {        // Current week
    $today = time();
} else if ($week == 2) { // Next week
    $today = time() + (7 * 24 * 60 * 60);
}
else if ($week == 3) { // Next week
    $today = time() + (14 * 24 * 60 * 60);
}
else if ($week == 4) { // Next week
    $today = time() + (21 * 24 * 60 * 60);
}
if (has_capability('mod/tutorship:reserve', $context)) { // Only students can do this
// Todo - implement sesskey in forms, may be using function is_post_with_sesskey().

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Reserves/Unreserves
if ($timetableid) { 
    $reachedmaxreserves     = false;
    $weeknumber             = date('W', $today);
    $teacherid              = $DB->get_field('tutorship_timetables', 'teacherid', array('id' => $timetableid));
    $reservationconditions  = array('courseid' => $course->id, 'studentid' => $USER->id, 'timetableid' => $timetableid,
                                    'week' => $weeknumber);
    $site                   = get_site();
    $to                     = $DB->get_record('user', array('id' => $teacherid));
    $subject                = format_string($site->shortname).': '.format_string($course->shortname).': ';
    $autoconfirmsubject     = $subject;
    $message                = '<p>'.format_string($site->fullname).': '.format_string($course->fullname).': ';
    $message               .= get_string('modulename', 'tutorship').'.</p>';

    // If reservation was made by student, delete it, otherwise create it
    // Cancell reservation
    if ($DB->record_exists('tutorship_reserves', $reservationconditions)) {
        $delobj = $DB->get_record('tutorship_reserves', $reservationconditions);
        //print_object('%%%%%');
        if ($DB->delete_records('tutorship_reserves', $reservationconditions)) {
            //exit('4');
            // Records user activity

            $existingevents = $DB->get_record('event', array( 'uuid' => $delobj->id));
                if($existingevents)
                {
                    $DB->delete_records('event', array('uuid' => $delobj->id));
                }

            $tutocalendars = $DB->get_records('tutorship_calendar', array('reserveid' => $delobj->id));
            if ($tutocalendars) {
                foreach ($tutocalendars as $tutorcalendar) {
                    $subscription = $DB->get_record('event_subscriptions', array('id' => $tutorcalendar->subid));
                    if ($subscription) {
                        calendar_delete_subscription($subscription);
                    }
                }
            }
            //add_to_log($course->id, 'tutorship', 'unreserve', 'studentview.php?id='.$cm->id, $tutorship->name, $cm->id);
            $event = \mod_tutorship\event\unreserve_slot::create(array(
                    'objectid' => $delobj->id,
                    'userid' => $USER->id,
                    'context' => $context,
                    'other' => array(
                            'tutorshipname' => $tutorship->name
                    )
            ));
            $event->trigger();

            // If teacher has notifications enabled, email to teacher
            if ($DB->get_field('tutorship_configs', 'notifications', array('teacherid' => $teacherid))) {

                // Student has cancelled a reservation, email to teacher
                $subject .= get_string('reservationcancelled', 'tutorship');
                $message .= get_string('reservationcancelledtxt', 'tutorship');
                $message .= ' <b>'.format_string(fullname($USER)).'</b>.<br>';
                $message .= get_string('reservationdetails', 'tutorship');
                $message .= tutorship_get_reserve_date($timetableid, $USER->id);
                
                $teacherInfo = $DB->get_record('user', array('id' => $teacherid));
                if (! email_to_user ($teacherInfo, $USER, $subject, null, $message)) {
                    print_error('erremail', 'tutorship');
                }

                if (! email_to_user($to, $USER, $subject, null, $message)) {
                    print_error('erremail', 'tutorship');
                }

            }

        } else {
            print_error('errunreserve', 'tutorship');
        }

    // Make reservation
    } else if (tutorship_can_reserve($timetableid, $course->id, $USER->id)) { // Has made all possible reservations?
       
       $calc_timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $timetableid));
       $calc_timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $calc_timeslotid));
       $yearData = tutorship_get_YearFromWeek($today ,  $calc_timeslot->day);
        if ($newslotid = tutorship_insert_reserve($course->id, $USER->id, $timetableid, $week , $yearData)) {
            // Records user activity
            //add_to_log($course->id, 'tutorship', 'reserve', 'studentview.php?id='.$cm->id, $tutorship->name, $cm->id);
            $event = \mod_tutorship\event\reserve_slot::create(array(
                    'objectid' => $newslotid,
                    'userid' => $USER->id,
                    'context' => $context,
                    'other' => array(
                            'tutorshipname' => $tutorship->name
                    )
            ));
            $event->trigger();

            // If has notifications configuration enabled, email to teacher
            if ($DB->get_field('tutorship_configs', 'notifications', array('teacherid' => $teacherid))) {
                $messagehtml = '';
                // Student has made a reservation, email to teacher
                $subject     .= get_string('reservationrequest', 'tutorship');
                $messagehtml .= get_string('reservationrequesttxt', 'tutorship');
                $messagehtml .= ' <b>'.format_string(fullname($USER)).'</b>.<br>';
                $messagehtml .= get_string('reservationdetails', 'tutorship');
                $messagehtml .= tutorship_get_reserve_date($timetableid, $USER->id).'.<br>';
                $messagehtml .= tutorship_get_email_link($cm->id, $week);

                $teacherInfo = $DB->get_record('user', array('id' => $teacherid));
                if (! email_to_user ($teacherInfo, $USER, $subject, null, $messagehtml)) {
                    print_error('erremail', 'tutorship');
                }
                if (! email_to_user ($to, $USER, $subject, null, $messagehtml)) {
                    print_error('erremail', 'tutorship');
                }

            }

            // If teacher has automatic confirmation enabled, confirm and email to student
            if ($DB->get_field('tutorship_configs', 'autoconfirm', array('teacherid' => $teacherid))) {
                if ($DB->set_field('tutorship_reserves', 'confirmed', 1, $reservationconditions)) {

                $sectiontitle = '';
                $studentid   = $USER->id;;//$DB->get_field('tutorship_reserves', 'studentid', $reservationconditions);
                $reserveid   = $DB->get_field('tutorship_reserves', 'id', $reservationconditions);

                             // Add to calendar.
                            // Get the time.
                            $reserveconditions = array('timetableid' => $DB->get_field('tutorship_reserves', 'timetableid',
                                    array('id' => $reserveid)), 'studentid' => $studentid);
                            $timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $DB->get_field('tutorship_reserves', 'timetableid',
                                    array('id' => $reserveid))));


                            $timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeslotid));
                            

                            // Get Topic Number from StartTime
                            $starttime   = TUTORSHIP_STARTTIME * 60 * 60;
                            $breaktime =  (int)TUTORSHIP_BREAKTIME_MINUTES * 60;
                            
                            $reTime = $timeslot->starttime;

                            $deltaTime = $reTime - $starttime;

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
                            
                            $sectioname = $DB->get_record('course_sections', array('section' => $sectionPos, 'course' => $cm->course));

                            if ($sectioname->name) {
                                $sectiontitle = $sectioname->name;
                                
                            } else {
                                $sectiontitle = 'Topic '. $sectionPos;
                            }


                            // Get Topic Number from StartTime End

                            $week       = $DB->get_field('tutorship_reserves', 'week', $reserveconditions);

                            $year       = date('Y', time());
                            $time       = gmdate('H:i', $timeslot->starttime);
                            $daynumber  = date('d', tutorship_get_date($timeslot->day, $week, $year));
                            $month      = date('m', tutorship_get_date($timeslot->day, $week, $year));

                            $unixtime = mktime(0, 0, 0, (int) $month, (int) $daynumber, (int) $year);
                            $unixtime += $timeslot->starttime;
                            // End getting time

                            // Get Student inforamtion
                            $studentInfo = $DB->get_record('user', array('id' => $studentid));
                            $studentTimeZone = $studentInfo->timezone;
                            if($studentTimeZone == '99')
                            {
                                $studentTimeZone = "Asia/Vientiane";
                            }
                            $offsetTimeZone = tutorship_get_timezone_offset($studentTimeZone , 'Asia/Vientiane');

                            $student_ID =  $studentInfo->username;
                            $student_Name = $studentInfo->firstname . " " .$studentInfo->lastname;
                            $nowUnixTime = strtotime("now");
                            $deltaUnixTime = $unixtime - $nowUnixTime;

                            $studenttime    = gmdate('H:i', $timeslot->starttime - $offsetTimeZone);

                            $sDate = $year ."-" . $month."-".$daynumber . " ".$studenttime;
                            //$eventDesc = $sectiontitle . " | " . $sDate . " | " . $student_Name . " | " . $student_ID;
                            $eventDesc = "<table  cellpadding='8'><tr><td>Topic</td><td>Student Time</td><td>Student Name</td><td>Student ID</td><td>Session ID</td><td rowspan=2><a href='../course/view.php?id=" .$course->id. "' style='color:red'> Enter course</a></td></tr>";
                            $eventDesc .= "<tr><td>".$sectiontitle . "</td>";
                            $eventDesc .= "<td>".$sDate . "</td>";
                            $eventDesc .= "<td>".$student_Name . "</td>";
                            $eventDesc .= "<td>".$studentid . "</td>";
                            $eventDesc .= "<td>".$reserveid . "</td></tr></table>";
                            //$eventDesc .= "Topic    ,Date               ,Student Name     ,Student ID \/n";
                            //$eventDesc .= $sectiontitle . " | " . $sDate . " | " . $student_Name . " | " . $studentid;


                            //Teacher event
                            $sub = new stdClass();
                            $sub->url = '';
                            $sub->courseid = 0;
                            $sub->groupid = 0;
                            $sub->userid = $teacherid;//$USER->id;
                            $sub->pollinterval = 0;
                            $subid = $DB->insert_record('event_subscriptions', $sub, true);

                            $event = new stdClass();
                            //2017-11-10 $event->name = $sectiontitle.':'.$tutorship->name;
                            $event->name =$tutorship->name;

                            $event->timestart = $unixtime;
                            $event->timeduration = 1800;
                            $event->description = $eventDesc;//$sectiontitle;
                            $event->uuid = $reserveid;//'uuid';
                            $event->repeatid = $studentid;
                    
                            //$event->subscriptionid = $subid;
                            $event->userid =  $teacherid;;
                            $event->groupid = 0;
                            $event->courseid = 0;
                            $event->eventtype = 'user';
                            $eventobj = calendar_event::create($event, false);
                            //End teacher event
                            $teacherobject = new stdClass();
                            $teacherobject->reserveid = $reserveid;
                            $teacherobject->subid = $subid;
                            $teacherobject->timemodified = time();
                            $DB->insert_record('tutorship_calendar', $teacherobject);
                            //Student event
                            $sub = new stdClass();
                            $sub->url = '';
                            $sub->courseid = 0;
                            $sub->groupid = 0;//$reserveid;;
                            $sub->userid = $USER->id;//$studentid;
                            $sub->pollinterval = 0;
                            $subid = $DB->insert_record('event_subscriptions', $sub, true);

                            $event = new stdClass();
                            //2017-11-10 $event->name = $sectiontitle.':'.$tutorship->name;
                            $event->name = $tutorship->name;
                            $event->description = $eventDesc;//$sectiontitle;
                            $event->timestart = $unixtime;
                            $event->timeduration = 1800;
                            //$event->description = 'custom description:'.$tutorship->name;
                            $event->uuid = $reserveid;//'uuid';
                            //$event->subscriptionid = $subid;
                            $event->userid = $USER->id;//$studentid;
                            $event->groupid = 0;
                            $event->courseid = 0;
                            $event->eventtype = 'user';
                            $event->repeatid = $teacherid;
                            $eventobj = calendar_event::create($event, false);
                            //End student event
                            $studentobject = new stdClass();
                            $studentobject->reserveid = $reserveid;
                            $studentobject->subid = $subid;
                            $studentobject->timemodified = time();
                            $DB->insert_record('tutorship_calendar', $studentobject);



                    $message = '';
                    // Email to student
                    $autoconfirmsubject .= get_string('reservationconfirmed', 'tutorship');
                    $message            .= get_string('reservationconfirmedtxt', 'tutorship');
                    $message            .= ' <b>'.format_string(fullname($to)).'</b>.<br>';
                    $message            .= get_string('reservationdetails', 'tutorship');
                    $message            .= tutorship_get_reserve_date($timetableid, $USER->id);

                    $teacherInfo = $DB->get_record('user', array('id' => $teacherid));
                    if (! email_to_user ($teacherInfo, $USER, $autoconfirmsubject, null, $message)) {
                        print_error('erremail', 'tutorship');
                    }

                    if (! email_to_user ($USER, $to, $autoconfirmsubject, null, $message)) {
                        print_error('erremail', 'tutorship');
                    }

                } else {
                    print_error('errconfirmation', 'tutorship');
                }
            }

        } else {
            print_error('errreserve', 'tutorship');
        }

    } else {
        $reachedmaxreserves = true;
    }
}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Sets page properties
$urlparams = array();
$urlparams['id'] = $cm->id;
if ($t) {
    $urlparams['t'] = $course->id; 
}
if ($selectedteacher) {
    $urlparams['selectedteacher'] = $selectedteacher;
}
if ($selectedperiod) {
    $urlparams['selectedperiod'] = $selectedperiod;
}
if ($slotid) {
    $urlparams['slotid'] = $slotid;
}
if ($maxreserves) {
    $urlparams['maxreserves'] = $maxreserves;
}
if ($autoconfirm) {
    $urlparams['autoconfirm'] = $autoconfirm;
}
if ($notify) {
    $urlparams['notify'] = $notify;
}
if ($week) {
    $urlparams['week'] = $week;
}
if ($timetableid) {
    $urlparams['timetableid'] = $timetableid;
}
if ($reserveid) {
    $urlparams['reserveid'] = $reserveid;
}
if ($cancell) {
    $urlparams['cancell'] = $cancell;
}
if ($noreserves) {
    $urlparams['noreserves'] = $noreserves;
}
$PAGE->set_url(new moodle_url('/mod/tutorship/view.php', $urlparams));
$PAGE->set_context($context);
$PAGE->set_cacheable(false);
$PAGE->set_title($tutorship->name);
$PAGE->set_heading($course->shortname);

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Checks to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);
groups_print_activity_menu($cm, $CFG->wwwroot . "/mod/tutorship/view.php?id=$cm->id");
if ($currentgroup) {
    $groupselect = " AND groupid = '$currentgroup'";
    $groupparam  = "&amp;groupid=$currentgroup";
} else {
    $groupselect = "";
    $groupparam  = "";
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Output starts here
if($action != 11)
{
    if($subpage != 'openmeeting')
        echo $OUTPUT->header();
}



if($subpage == 'schedule')
{
    if (has_capability('mod/tutorship:teachermanage', $context)) {
            $subpage = "teachermanagement";
    }
}

/*
echo '<style>body.noMargin {    margin: 0;  padding: 0; border: 0;}
iframe.tutorshiproom {  border: 0;  width: 100% !important; height: 640px;}
iframe.wholeWindow {    height: 100%  !important;}
</style>'; */
///////////////////////////////////////////////////////////////////////////////////////////////////////
// Includes proper file depending on capability
if (has_capability('mod/tutorship:update', $context)) {

    if($subpage == 'openmeeting')
    {

        $rID            = optional_param('rid', 0, PARAM_INT);          // cancell reservation from teacherview
        if($rID != 0)
        {
            //Get Reserve Information
            $reserveInfo = $DB->get_record('tutorship_reserves', array('id' =>$rID));
            if(isset($reserveInfo))
            {

                $timeTableID =  $reserveInfo->timetableid;
                $weekN =  $reserveInfo->week;
                $yearN =  $reserveInfo->year;
                $tID = $USER->id;
                
                $roomInfo = $DB->get_record('tutorship_openmeeting', array('classid' =>$timeTableID , 'teacherid'=>$tID  , 'weekid'=>$weekN , 'yearid'=>$yearN));

                $meetingRoomID = 0;
                $meetingUniqID = 0;
                if(isset($roomInfo) && isset($roomInfo->roomid))
                {
                    $meetingRoomID = $roomInfo->roomid;
                    $meetingUniqID = $roomInfo->id;
                }
                else
                {
                    // Create Room in OpenMeeting
                    $roomTemplateInfo = array();
                    $roomTemplateInfo['name'] = 'MOODLE_COURSE_ID_3_NAME_'.$timeTableID."_".$weekN."_".$yearN;
                    $roomTemplateInfo['comment'] = 'Created by SOAP-Gateway';
                    $roomTemplateInfo['type'] = 'conference';
                    $roomTemplateInfo['capacity'] = '200';
                    $roomTemplateInfo['isPublic'] = '';
                    $roomTemplateInfo['appointment'] = '';
                    $roomTemplateInfo['moderated'] = '';
                    $roomTemplateInfo['audioOnly'] = '';
                    $roomTemplateInfo['allowUserQuestions'] = '1';
                    $roomTemplateInfo['allowRecording'] = '1';
                    $roomTemplateInfo['chatHidden'] = '';
                    $roomTemplateInfo['externalId'] = '';


                    $gateway = new TOmGateway(getTOmConfig());
                    $om_login = $gateway->login();
                    if($om_login)
                    {
                        $roomID = $gateway->updateRoom($roomTemplateInfo);
                        if($roomID != -1)
                        {
                            $roomInfo = tutorship_insert_room($roomID,$tID,$timeTableID,$weekN,$yearN );
                            $meetingRoomID = $roomID;
                            $meetingUniqID = $roomInfo;
                        }
                    }
                }


                

                $roomInfo = array();

                $roomInfo["id"] = $meetingUniqID;
                $roomInfo["course"] = $cm->course;
                $roomInfo["teacher"] = "1";
                $roomInfo["type"] =  "conference";
                $roomInfo["is_moderated_room"] = 1;
                $roomInfo["max_user"] = 40;
                $roomInfo["language"] = 1;
                $roomInfo["name"] = "TTEst Metting";
                $roomInfo["intro"] = "";
                $roomInfo["timecreated"] = 0;
                $roomInfo["timemodified"] = 0;
                $roomInfo["room_id"] = $meetingRoomID;
                $roomInfo["room_recording_id"] = 0;
                $roomInfo["allow_recording"] = 1;
                $roomInfo["whole_window"] = 1;//0;
                $roomInfo["chat_hidden"] = 0;



                $output = $PAGE->get_renderer('mod_tutorship');
                //  $output = $PAGE->get_renderer('mod_tutorship');
                $openmeetingswidget = new tutorshiproom($roomInfo);
            //  print_R($openmeetingswidget);die();

                echo $output->header();
                echo $output->render($openmeetingswidget);
                echo $output->footer();
            
            }
        }
    }
    else
    {
        include ($CFG->dirroot.'/mod/tutorship/teacherview.php');
    }
} else if (has_capability('mod/tutorship:reserve', $context)) {
    if($subpage == 'openmeeting')
    {

        $rID            = optional_param('rid', 0, PARAM_INT);          // cancell reservation from teacherview
        if($rID != 0)
        {
            //Get Reserve Information
            $reserveInfo = $DB->get_record('tutorship_reserves', array('id' =>$rID));
            if(isset($reserveInfo))
            {

                $timeTableID =  $reserveInfo->timetableid;
                $weekN =  $reserveInfo->week;
                $yearN =  $reserveInfo->year;
                //Get Teacher Information
                $timetableInfo = $DB->get_record('tutorship_timetables', array('id' =>$timeTableID));
                $tID = 0;
                if(isset($timetableInfo) && isset($timetableInfo->teacherid))
                {
                    $tID = $timetableInfo->teacherid;
                }
                

                $roomInfo = $DB->get_record('tutorship_openmeeting', array('classid' =>$timeTableID , 'teacherid'=>$tID  , 'weekid'=>$weekN , 'yearid'=>$yearN));

                $meetingRoomID = 0;
                $meetingUniqID = 0;
                if(isset($roomInfo) && isset($roomInfo->roomid))
                {
                    $meetingRoomID = $roomInfo->roomid;
                    $meetingUniqID = $roomInfo->id;
                }
                else
                {
                    // Create Room in OpenMeeting
                    $roomTemplateInfo = array();
                    $roomTemplateInfo['name'] = 'MOODLE_COURSE_ID_3_NAME_'.$timeTableID."_".$weekN."_".$yearN;
                    $roomTemplateInfo['comment'] = 'Created by SOAP-Gateway';
                    $roomTemplateInfo['type'] = 'conference';
                    $roomTemplateInfo['capacity'] = '200';
                    $roomTemplateInfo['isPublic'] = '';
                    $roomTemplateInfo['appointment'] = '';
                    $roomTemplateInfo['moderated'] = '';
                    $roomTemplateInfo['audioOnly'] = '';
                    $roomTemplateInfo['allowUserQuestions'] = '1';
                    $roomTemplateInfo['allowRecording'] = '1';
                    $roomTemplateInfo['chatHidden'] = '';
                    $roomTemplateInfo['externalId'] = '';


                    $gateway = new TOmGateway(getTOmConfig());
                    $om_login = $gateway->login();
                    if($om_login)
                    {
                        $roomID = $gateway->updateRoom($roomTemplateInfo);
                        if($roomID != -1)
                        {
                            $roomInfo = tutorship_insert_room($roomID,$tID,$timeTableID,$weekN,$yearN );
                            $meetingRoomID = $roomID;
                            $meetingUniqID = $roomInfo;
                        }
                    }
                }


                

                $roomInfo = array();

                $roomInfo["id"] = $meetingUniqID;
                $roomInfo["course"] = $cm->course;
                $roomInfo["teacher"] = "0";
                $roomInfo["type"] =  "conference";
                $roomInfo["is_moderated_room"] = "1";
                $roomInfo["max_user"] = 40;
                $roomInfo["language"] = 1;
                $roomInfo["name"] = "TTest Metting";
                $roomInfo["intro"] = "";
                $roomInfo["timecreated"] = 0;
                $roomInfo["timemodified"] = 0;
                $roomInfo["room_id"] = $meetingRoomID;
                $roomInfo["room_recording_id"] = 0;
                $roomInfo["allow_recording"] = 0;
                $roomInfo["whole_window"] = 1;//0;
                $roomInfo["chat_hidden"] = 0;



                $output = $PAGE->get_renderer('mod_tutorship');
                //  $output = $PAGE->get_renderer('mod_tutorship');
                $openmeetingswidget = new tutorshiproom($roomInfo);
            //  print_R($openmeetingswidget);die();

                echo $output->header();
                echo $output->render($openmeetingswidget);
                echo $output->footer();
            
            }
        }
    }
    else
    {
        include ($CFG->dirroot.'/mod/tutorship/studentview.php');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// Finish the page
if($subpage != 'openmeeting')
    echo $OUTPUT->footer();


