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
 * Internal library of functions for module tutorship.
 *
 * All the tutorship specific functions, needed to implement the module
 * logic, are placed here. Never include this file from your lib.php!
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die();

// One important issue is about the connection between lib.php and locallib.php 
// Who has to call the other, how and why? 
// It has to be locallib.php to call lib.php through:


$old_error_handler = set_error_handler("myErrorHandler");

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot.'/mod/tutorship/api/TOmGateway.php');

// error handler function
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	switch ($errno) {
	case E_USER_ERROR:
		echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
		echo "  Fatal error on line $errline in file $errfile";
		echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
		echo "Aborting...<br />\n";
		exit(1);
		break;

	case E_USER_WARNING:
		echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
		break;

	case E_USER_NOTICE:
		echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
		break;

	default:
		//echo "Unknown error type: [$errno] $errstr<br />\n";
		break;
	}

	return true;
}
// As first line. 
// In this way, if moodle core calls function inside mod/tutorship/lib.php no module 
// specific function will be loaded in memory. 
// If it is tutorship to call a function, it has to require_once('locallib.php'); 
// that, in turn, will require_once('lib.php') so that the module will "see" all its 
// available functions. 

/////////////////////////////////////////////////////////////////////////////////////////////
// +------------+                                                                          //
// |            |                                                                          //
// + Constants: +                                                                          //
// |            |                                                                          //
// +------------+                                                                          //
//                                                                                         //
/////////////////////////////////////////////////////////////////////////////////////////////

define('TUTORSHIP_TIMESLOT_MINUTES', 30);           // Default timeslot length: 30 minutes
define('TUTORSHIP_BREAKTIME_MINUTES', 15);           // Default Break length: 15 minutes
define('TUTORSHIP_GUARDTIME_MINUTES', 15);           // Default Guard Time length: 15 minutes
define('TUTORSHIP_NOTIFYTIME_MINUTES', 60);           // Default Guard Time length: 60 minutes

/// --- Setting For OpenMeeting
define('TUTORSHIP_OPENMEETING_IP', 'localhost');    // OpenMeeting Server IP  or HOST
define('TUTORSHIP_OPENMEETING_PORT', 5080);       // OpenMeeting Server Port Num   
define('TUTORSHIP_OPENMEETING_USER', 'admin');       // OpenMeeting Server Admin User Name  
define('TUTORSHIP_OPENMEETING_USERPASS', '');       // OpenMeeting Server Admin User Password  
define('TUTORSHIP_OPENMEETING_MOODLE_KEY', 'moodle');       //Moodle Key For  OpenMeeting Server
define('TUTORSHIP_OPENMEETING_WEBAPP', 'openmeetings');       //OpenMeeting Server Web APP Name
define('TUTORSHIP_OPENMEETING_PROTOCOL', 'http');       //OpenMeeting Server Server Protocol



define('TUTORSHIP_PAYPAL_MENCHAT_EMAIL', '');           // PayPal Menchat Email Address
define('TUTORSHIP_PAYPAL_EMAIL', '');           // PayPal API UserName
define('TUTORSHIP_PAYPAL_PASSWORD', '');           // PayPal API User Password
define('TUTORSHIP_PAYPAL_SIGNATURE', '');           // PayPal API User Signature Key


define('TUTORSHIP_STARTTIME', 8);                   // Default day start time: 8:00h
define('TUTORSHIP_ENDTIME', 21);                    // Default day end time: 21:00h 
define('TUTORSHIP_MONDAY', 0);                      // Monday
define('TUTORSHIP_TUESDAY', 1);                     // Tuesday
define('TUTORSHIP_WEDNESDAY', 2);                   // Wednesday
define('TUTORSHIP_THURSDAY', 3);                    // Thursday
define('TUTORSHIP_FRIDAY', 4);                      // Friday
define('TUTORSHIP_SATURDAY', 5);                      // Saturday
define('TUTORSHIP_SUNDAY', 6);                      // Sunday
define('TUTORSHIP_FIRSTPERIOD_STARTDAY', 20);       // Default first period start day: 20
define('TUTORSHIP_FIRSTPERIOD_STARTMONTH', 9);      // September
define('TUTORSHIP_FIRSTPERIOD_ENDDAY', 14);         // Default frist period end day: 14
define('TUTORSHIP_FIRSTPERIOD_ENDMONTH', 1);        // January
define('TUTORSHIP_FIRSTPERIOD_STARTYEAR', 0);       // Current year
define('TUTORSHIP_FIRSTPERIOD_ENDYEAR', 1);         // Next year
define('TUTORSHIP_SECONDPERIOD_STARTDAY', 1);       // Default second period start day: 1
define('TUTORSHIP_SECONDPERIOD_STARTMONTH', 2);     // February
define('TUTORSHIP_SECONDPERIOD_ENDDAY', 20);        // Default second period end day: 20
define('TUTORSHIP_SECONDPERIOD_ENDMONTH', 5);       // May
define('TUTORSHIP_SECONDPERIOD_STARTYEAR', 1);      // Next year
define('TUTORSHIP_SECONDPERIOD_ENDYEAR', 1);        // Next year
define('TUTORSHIP_THIRDPERIOD_STARTDAY', 15);       // Default third period start day: 15
define('TUTORSHIP_THIRDPERIOD_STARTMONTH', 6);      // June
define('TUTORSHIP_THIRDPERIOD_ENDDAY', 15);         // Default third period end day: 15
define('TUTORSHIP_THIRDPERIOD_ENDMONTH', 7);        // July
define('TUTORSHIP_THIRDPERIOD_STARTYEAR', 1);       // Next year
define('TUTORSHIP_THIRDPERIOD_ENDYEAR', 1);         // Next year

$_CONSTANTS = array();
for($i = 0 ; $i < 100 ; $i++)
{
	$_CONSTANTS[$i] = 10;
}


$_CONSTANTS_CLASS = array();
for($i = 0 ; $i < 100 ; $i++)
{
	$_CONSTANTS_CLASS[$i] = 6;
}



function getTOmUser($gateway) {
	global $USER;
	$pictureUrl = moodle_url::make_pluginfile_url(context_user::instance($USER->id)->id, 'user', 'icon', NULL, '/', 'f2')->out(false);
	return $gateway->getUser($USER->username, $USER->firstname, $USER->lastname, $pictureUrl, $USER->email, $USER->id);
}

function getTOmHash($gateway, $options) {
	return $gateway->getSecureHash(getTOmUser($gateway), $options);
}

function getTOmConfig() {
	global $CFG;
	$ipAddr = get_config('tutorship', 'openmeetings_ip');
	$protocol = get_config('tutorship', 'openmeetings_protocol');
	$port = get_config('tutorship', 'openmeetings_port');
	$appName = get_config('tutorship', 'openmeetings_webapp');
	$user = get_config('tutorship', 'openmeetings_user');
	$password = get_config('tutorship', 'openmeetings_password');
	$moodleKey = get_config('tutorship', 'openmeetings_moodlekey');
	return array(
			"protocol" => $protocol,
			"host" => $ipAddr,
			"port" => $port,
			"context" => $appName,
			"user" => $user,
			"pass" => $password,
			"module" => $moodleKey,
			"debug" => $CFG->debug > 0
	);
}

function setTutorshipRoomName(&$openmeetings) {
	$openmeetings->roomname = 'MOODLE_COURSE_ID_' . $openmeetings->course . '_NAME_' . $openmeetings->name;
}

function getTutorshipRoom(&$openmeetings) {
	setTutorshipRoomName($openmeetings);
	return array(
			'id' => $openmeetings->room_id > 0 ? $openmeetings->room_id : null
			, 'name' => $openmeetings->roomname
			, 'comment' => 'Created by SOAP-Gateway'
			, 'type' => $openmeetings->type
			, 'capacity' => $openmeetings->max_user
			, 'isPublic' => false
			, 'appointment' => false
			, 'moderated' => 1 == $openmeetings->is_moderated_room
			, 'audioOnly' => false
			, 'allowUserQuestions' => true
			, 'allowRecording' => 1 == $openmeetings->allow_recording
			, 'chatHidden' => 1 == $openmeetings->chat_hidden
			, 'externalId' => $openmeetings->instance
	);
}


/////////////////////////////////////////////////////////////////////////////////////////////
// +-------------------------------+                                                       //
// |                               |                                                       //
// + Inserting Database functions: +                                                       //
// |                               |                                                       //
// +-------------------------------+                                                       //
//                                                                                         //
// tutorship_insert_timetable(): called from view.php when teacher enablesa timetable slot.//
// tutorship_insert_reserve(): called from view.php when student makes a reservation.      //
// tutorship_insert_teacher_config(): called from view.php to initialize teacher's config. //
// tutorship_insert_default_config(): called from settings.php to initialize module config.// 
// tutorship_insert_periods(): called from lib.php when module is added to a course.       //
// tutorship_insert_timeslots(): called from lib.php when module is added to a course.     //
/////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Inserts a timetable record.
 * Given all record fields containing all the necessary data, this function 
 * will create a new instance and return the id number of the new instance.
 *
 * @param  int $teacherid   The id of the teacher, owner of timetable.
 * @param  int $periodid    The id of the timetable's period.
 * @param  int $timeslotid  The id of the timetable's timeslots.
 * @return int $id          The id of the newly inserted timetable record.
 */
function tutorship_insert_timetable($teacherid, $periodid, $timeslotid) {
    global $DB;

    $timetable               = new stdClass();
    $timetable->teacherid    = $teacherid;
    $timetable->periodid     = $periodid;
    $timetable->timeslotid   = $timeslotid;
    $timetable->timemodified = time();

    $id = $DB->insert_record('tutorship_timetables', $timetable);
    unset($timetable);
    return $id;
}

/**
 * Inserts a reserve record.
 * Given all record fields containing all the necessary data, this function
 * will create a new instance and return the id number of the new instance.
 *
 * @param  int $courseid    The id of the course, owner of timetable.
 * @param  int $studentid   The id of the student's reserve.
 * @param  int $timetableid The id of the timetable's timeslots.
 * @param  int $week        The reserve week number.
 * @return int $id          The id of the newly inserted timetable record.
 */
function tutorship_insert_reserve($courseid, $studentid, $timetableid, $week ,$year) {
    global $DB;

    $reserve                = new stdClass();
    $reserve->courseid      = $courseid;
    $reserve->studentid     = $studentid;
    $reserve->timetableid   = $timetableid;
    $reserve->week          = $week;
    $reserve->year          = $year;
    $reserve->confirmed     = 0;
    $reserve->timecreated   = time();

    $id = $DB->insert_record('tutorship_reserves', $reserve);
    unset($reserve);
    return $id;
}

/**
 * Inserts a teacher timetable configuration record into tutorship_configs.
 * Given all record fields containing all the necessary data, this function
 * will create a new instance and return the id number of the new instance.
 *
 * @param  int $teacherid       The id of the teacher, owner of configuration.
 * @param  int $autoconfirm     The automatic confirmation, 0-Manual, 1-Automatic.
 * @param  int $notifications   The email notifications, 0-Disable, 1-Enabled.
 * @param  int $maxreserves     The maximum number of reserves per student.
 * @return int $id              The id of the newly inserted configuration record.
 */
function tutorship_insert_teacher_config($teacherid, $autoconfirm, $notifications, $maxreserves, $noreserves) {
    global $DB;

    $configuration                  = new stdClass();
    $configuration->teacherid       = $teacherid;
    $configuration->autoconfirm     = $autoconfirm;
    $configuration->notifications   = $notifications;
    $configuration->maxreserves     = $maxreserves;
    $configuration->noreserves      = $noreserves;
    $configuration->timemodified    = time();

    $id = $DB->insert_record('tutorship_configs', $configuration);
    unset($configuration);
    return $id;
}

/**
 * Sets initial default configuration.
 * Inserts default configuration in config_plugins table,
 * returning true if success. 
 *
 * Todo: Too many repeated elements, try to apply new feature MDL-24413, 
 * in settings.php when implemented, to reduce number of tutorship 
 * configuration fields in config_plugins table and change this function 
 * as consecuence.
 *
 * @see    tutorship_get_config().
 * @param  null.
 * @return boolean Success/Failure.
 */
function tutorship_insert_default_config() {
    global $DB;

    if ($DB->count_records('config_plugins', array('plugin' => 'tutorship')) > 0) {
        return false;
    } else {
        // Time slots configs
		set_config('starttime', TUTORSHIP_STARTTIME, 'tutorship');
        set_config('endtime', TUTORSHIP_ENDTIME, 'tutorship');
        set_config('timeslotlength', TUTORSHIP_TIMESLOT_MINUTES, 'tutorship');
		set_config('breaktimelength', TUTORSHIP_BREAKTIME_MINUTES, 'tutorship');
		set_config('guardtimelength', TUTORSHIP_GUARDTIME_MINUTES, 'tutorship');
		set_config('notifybeforestartclass', TUTORSHIP_NOTIFYTIME_MINUTES, 'tutorship');
		
		set_config('paypalmenchatemail', TUTORSHIP_PAYPAL_MENCHAT_EMAIL, 'tutorship');
		set_config('paypaluseremail', TUTORSHIP_PAYPAL_EMAIL, 'tutorship');
		set_config('paypaluserpassword', TUTORSHIP_PAYPAL_PASSWORD, 'tutorship');
		set_config('paypalusersignature', TUTORSHIP_PAYPAL_SIGNATURE, 'tutorship');

        // First period configs
        //---set_config('firstperioddesc', get_string('firstperiod', 'tutorship'), 'tutorship');
        set_config('firstperiodstartday', TUTORSHIP_FIRSTPERIOD_STARTDAY, 'tutorship');
        set_config('firstperiodstartmonth', TUTORSHIP_FIRSTPERIOD_STARTMONTH, 'tutorship');
        set_config('firstperiodstartyear', TUTORSHIP_FIRSTPERIOD_STARTYEAR, 'tutorship');
        set_config('firstperiodendday', TUTORSHIP_FIRSTPERIOD_ENDDAY, 'tutorship');
        set_config('firstperiodendmonth', TUTORSHIP_FIRSTPERIOD_ENDMONTH, 'tutorship');
        set_config('firstperiodendyear', TUTORSHIP_FIRSTPERIOD_ENDYEAR, 'tutorship');


        set_config('openmeetings_ip', TUTORSHIP_OPENMEETING_IP, 'tutorship');
        set_config('openmeetings_port', TUTORSHIP_OPENMEETING_PORT, 'tutorship');
        set_config('openmeetings_user', TUTORSHIP_OPENMEETING_USER, 'tutorship');
        set_config('openmeetings_password', TUTORSHIP_OPENMEETING_USERPASS, 'tutorship');
        set_config('openmeetings_moodlekey', TUTORSHIP_OPENMEETING_MOODLE_KEY, 'tutorship');
        set_config('openmeetings_webapp', TUTORSHIP_OPENMEETING_WEBAPP, 'tutorship');
        set_config('openmeetings_protocol', TUTORSHIP_OPENMEETING_PROTOCOL, 'tutorship');


        /*
		// Second period configs
        set_config('secondperioddesc', get_string('secondperiod', 'tutorship'), 'tutorship');
        set_config('secondperiodstartday', TUTORSHIP_SECONDPERIOD_STARTDAY, 'tutorship');
        set_config('secondperiodstartmonth', TUTORSHIP_SECONDPERIOD_STARTMONTH, 'tutorship');
        set_config('secondperiodstartyear', TUTORSHIP_SECONDPERIOD_STARTYEAR, 'tutorship');
        set_config('secondperiodendday', TUTORSHIP_SECONDPERIOD_ENDDAY, 'tutorship');
        set_config('secondperiodendmonth', TUTORSHIP_SECONDPERIOD_ENDMONTH, 'tutorship');
        set_config('secondperiodendyear', TUTORSHIP_SECONDPERIOD_ENDYEAR, 'tutorship');

        // Third period configs
        set_config('thirdperioddesc', get_string('thirdperiod', 'tutorship'), 'tutorship');
        set_config('thirdperiodstartday', TUTORSHIP_THIRDPERIOD_STARTDAY, 'tutorship');
        set_config('thirdperiodstartmonth', TUTORSHIP_THIRDPERIOD_STARTMONTH, 'tutorship');
        set_config('thirdperiodstartyear', TUTORSHIP_THIRDPERIOD_STARTYEAR, 'tutorship');
        set_config('thirdperiodendday', TUTORSHIP_THIRDPERIOD_ENDDAY, 'tutorship');
        set_config('thirdperiodendmonth', TUTORSHIP_THIRDPERIOD_ENDMONTH, 'tutorship');
        set_config('thirdperiodendyear', TUTORSHIP_THIRDPERIOD_ENDYEAR, 'tutorship');

		*/


		// Set max Reservation Size For Course		
		$courses = $DB->get_records('course');
		if(isset($courses) && count($courses) > 0)
		{
			$len = count($courses);
			foreach ($courses as $item)
			{
				if(isset($item) && $item->format == "topics")
				{
					$courseID = $item->id;
					$regKey = "coursemax".$courseID;
					set_config(regKey ,$_CONSTANTS[$courseID], 'tutorship');
				}
			}
		}



	

		// Set max Class Size For Course		
		if(isset($courses) && count($courses) > 0)
		{
			$len = count($courses);
			foreach ($courses as $item)
			{
				if(isset($item) && $item->format == "topics")
				{
					$courseID = $item->id;
					$regKey = "courseclasssize".$courseID;
					set_config(regKey ,$_CONSTANTS_CLASS[$courseID], 'tutorship');
				}
			}
		}



        // Checks all records
        if ($DB->count_records('config_plugins', array('plugin' => 'tutorship')) == 24) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Inserts the three tutorship periods.
 * Given an object containing all the necessary data,this function
 * will create three new instance and return if the records were
 * inserted or not.
 *
 * @see    tutorship_get_config().
 * @global object.
 * @param  object  $tutorship The object containing all the necessary data.
 * @return boolean Success/Failure.
 */ 
function tutorship_insert_periods($tutorship) {
    global $DB;
   
/// First period 
    // Sets first period start date
    $day        = (int) get_config('tutorship', 'firstperiodstartday');
    $month      = (int) get_config('tutorship', 'firstperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'firstperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $firstperiodstart = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time

    // Sets first period end date
    $day        = (int) get_config('tutorship', 'firstperiodendday');
    $month      = (int) get_config('tutorship', 'firstperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'firstperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $firstperiodend = mktime(0, 0, 0, $month, $day, $year);

    // Sets first period description
    //--$firstperioddesc = get_config('tutorship', 'firstperioddesc');
    
	/*
/// Second period
    // Sets second period start date    
    $day        = (int) get_config('tutorship', 'secondperiodstartday');
    $month      = (int) get_config('tutorship', 'secondperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'secondperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $secondperiodstart = mktime(0, 0, 0, $month, $day, $year);
    
    // Sets second perios end date    
    $day        = (int) get_config('tutorship', 'secondperiodendday');
    $month      = (int) get_config('tutorship', 'secondperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'secondperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $secondperiodend = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time
    
    // Sets second period description
    $secondperioddesc = get_config('tutorship', 'secondperioddesc');

/// Third period
    // Sets third period start date
    $day        = (int) get_config('tutorship', 'thirdperiodstartday');
    $month      = (int) get_config('tutorship', 'thirdperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'thirdperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $thirdperiodstart = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time
        
    // Sets third period end date
    $day        = (int) get_config('tutorship', 'thirdperiodendday');
    $month      = (int) get_config('tutorship', 'thirdperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'thirdperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $thirdperiodend = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time
        
    // Sets third period description
    $thirdperioddesc = get_config('tutorship', 'thirdperioddesc');
*/
    // Creates and sets the period objects
    $firstobject                = new stdClass();
  //  $secondobject               = new stdClass();
  //  $thirdobject                = new stdClass();
    $firstobject->startdate     = $firstperiodstart;
    $firstobject->enddate       = $firstperiodend;
    $firstobject->description   = "Period";//$firstperioddesc;
   
	/*$secondobject->startdate    = $secondperiodstart;
    $secondobject->enddate      = $secondperiodend;
    $secondobject->description  = $secondperioddesc;
    $thirdobject->startdate     = $thirdperiodstart;
    $thirdobject->enddate       = $thirdperiodend;
    $thirdobject->description   = $thirdperioddesc;
*/
    // Inserts the objects checking if dates are ok
    if (tutorship_validate_period_date($firstobject)) {
        $firstid  = $DB->insert_record('tutorship_periods', $firstobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    }
  /*  if (tutorship_validate_period_date($secondobject)) {
        $secondid = $DB->insert_record('tutorship_periods', $secondobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    }
    if (tutorship_validate_period_date($thirdobject)) {
        $thirdid  = $DB->insert_record('tutorship_periods', $thirdobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    } */

    // Don't need the objects any more
    unset($firstobject);
	if ($firstid) {
        return true;
    } else {
        return false;
    }

   /* unset($secondobject);
    unset($thirdobject);
        if ($firstid and $secondid and $thirdid) {
        return true;
    } else {
        return false;
    }*/

}



//// ---- Relation Leave Information
function tutorship_get_leave_reason_list()
{
	$staticList = array("0"=> "Tech" , "1"=> "Sick" , "2" => "Moving" ,"3"=> "Personal");
	return $staticList;
}
function tutorship_get_affectedclass_count($teacherID , $startdate , $enddate,$starttime ,$endtime)
{
	 global $DB;
	$classCnt = 0;

	if($teacherID == "" || $startdate == "" || $enddate == "")
		return $classCnt;
	$selYears = date("Y" , $startdate);
	$startWeek = date("W" , $startdate);
	$endWeek = date("W" , $enddate);

	$s_day = date("w" , $startdate) - 1;
	$e_day = date("w" , $enddate) - 1;

	if($s_day < 0)$s_day = 6;
	if($e_day < 0)$e_day = 6;
	
	$m_getReserveSQL = "select a.* ,b.teacherid ,b.timeslotid from tutorship_reserves a left join tutorship_timetables b on b.id= a.timetableid where a.week >= $startWeek AND a.week <= $endWeek AND a.confirmed = 1 AND b.teacherid=$teacherID  AND  a.year = $selYears GROUP BY a.timetableid";

	$allReserves         = $DB->get_records_sql($m_getReserveSQL, array());
	foreach($allReserves as $item)
	{
		$itemWeek = $item->week;
		if($itemWeek  > $startWeek && $itemWeek < $endWeek )
		{
			$classCnt  ++;
		}
		else
		{
			// If reserve week same startWeek or endWeek
			$timesolt = $DB->get_record('tutorship_timeslots', array('id' => $item->timeslotid));
			if($startWeek == $endWeek && $itemWeek == $startWeek)
			{
				if($timesolt->day > $s_day && $timesolt->day < $e_day)
				{
					$classCnt  ++;
				}
				else if($timesolt->day == $s_day)
				{
					if($starttime >= $timesolt->starttime)
						$classCnt  ++;
				}
				else if($timesolt->day == $e_day)
				{
					if($timesolt->starttime <= $endtime)
						$classCnt  ++;
				}
			}	
			else if($itemWeek == $startWeek)
			{
				if($timesolt->day > $s_day)
				{
					$classCnt  ++;
				}
				else if($timesolt->day ==  $s_day)
				{
					//print_r("EE");die();
					if($starttime >= $timesolt->starttime)
						$classCnt  ++;
				}
			}
			else if($itemWeek == $endWeek)
			{
				
				if($timesolt->day < $e_day)
				{
					$classCnt  ++;
				}
				else if($timesolt->day ==  $e_day)
				{
					
					if($timesolt->starttime <= $endtime)
						$classCnt  ++;
				}
			}
		}
	}
	//$classCnt = count($allReserves);
	return $classCnt;
}

function tutorship_check_leave($teacherID ,$formdata)
{
	 global $DB;
	 $status = false;

	//$teacherid, $startdate, $endate ,$startitme ,$endtime , $reason
	$mCheckData = $DB->get_record('tutorship_leaveinfo', array('teacherid' =>$teacherID , 'startdate'=>$formdata->startdate ,'enddate'=> $formdata->enddate , 'starttime'=>$formdata->starttime , 'endtime' =>$formdata->endtime ));

	if(isset($mCheckData) && isset($mCheckData->id) && $mCheckData->id != "")
	{
		$status = true;
	}
	return $status;
}

function tutorship_insert_leave($teacherID ,$formdata) {
   
	 global $DB;

    $leavetable               = new stdClass();
    $leavetable->teacherid    = $teacherID;
    $leavetable->startdate     = $formdata->startdate;
    $leavetable->enddate   = $formdata->enddate;
    $leavetable->starttime   = $formdata->starttime;
    $leavetable->endtime   = $formdata->endtime;
    $leavetable->reason   = $formdata->reason;
    $leavetable->confirmed   = 0;
    $leavetable->timecreated = time();
//print_R($formdata);die();
//print_r( $leavetable);die();
    $id = $DB->insert_record('tutorship_leaveinfo', $leavetable);
    unset($leavetable);
    return $id;
}


function tutorship_update_periods($tutorship) {
    global $DB;

    /// First period
    // Sets first period start date
    $day        = (int) get_config('tutorship', 'firstperiodstartday');
    $month      = (int) get_config('tutorship', 'firstperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'firstperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $firstperiodstart = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time

    // Sets first period end date
    $day        = (int) get_config('tutorship', 'firstperiodendday');
    $month      = (int) get_config('tutorship', 'firstperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'firstperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $firstperiodend = mktime(0, 0, 0, $month, $day, $year);

    // Sets first period description
    $firstperioddesc = "Period";//get_config('tutorship', 'firstperioddesc');



/*
    /// Second period
    // Sets second period start date
    $day        = (int) get_config('tutorship', 'secondperiodstartday');
    $month      = (int) get_config('tutorship', 'secondperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'secondperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $secondperiodstart = mktime(0, 0, 0, $month, $day, $year);

    // Sets second perios end date
    $day        = (int) get_config('tutorship', 'secondperiodendday');
    $month      = (int) get_config('tutorship', 'secondperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'secondperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $secondperiodend = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time

    // Sets second period description
    $secondperioddesc = get_config('tutorship', 'secondperioddesc');

    /// Third period
    // Sets third period start date
    $day        = (int) get_config('tutorship', 'thirdperiodstartday');
    $month      = (int) get_config('tutorship', 'thirdperiodstartmonth');
    $yearselect = (int) get_config('tutorship', 'thirdperiodstartyear');
    $year       = tutorship_to_year($yearselect);
    $thirdperiodstart = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time

    // Sets third period end date
    $day        = (int) get_config('tutorship', 'thirdperiodendday');
    $month      = (int) get_config('tutorship', 'thirdperiodendmonth');
    $yearselect = (int) get_config('tutorship', 'thirdperiodendyear');
    $year       = tutorship_to_year($yearselect);
    $thirdperiodend = mktime(0, 0, 0, $month, $day, $year);//, 1); // Summer time

    // Sets third period description
    $thirdperioddesc = get_config('tutorship', 'thirdperioddesc');
*/
    // Creates and sets the period objects
    $firstobject                = $DB->get_record('tutorship_periods', array('id' => 1));
//--    $secondobject               = $DB->get_record('tutorship_periods', array('id' => 2));
//--    $thirdobject                = $DB->get_record('tutorship_periods', array('id' => 3));
    $firstobject->startdate     = $firstperiodstart;
    $firstobject->enddate       = $firstperiodend;
    $firstobject->description   = $firstperioddesc;
/*    $secondobject->startdate    = $secondperiodstart;
    $secondobject->enddate      = $secondperiodend;
    $secondobject->description  = $secondperioddesc;
    $thirdobject->startdate     = $thirdperiodstart;
    $thirdobject->enddate       = $thirdperiodend;
    $thirdobject->description   = $thirdperioddesc;
*/
    // Inserts the objects checking if dates are ok
    if (tutorship_validate_period_date($firstobject)) {
        $firstid  = $DB->update_record('tutorship_periods', $firstobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    }
	/*
    if (tutorship_validate_period_date($secondobject)) {
        $secondid = $DB->update_record('tutorship_periods', $secondobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    }
    if (tutorship_validate_period_date($thirdobject)) {
        $thirdid  = $DB->update_record('tutorship_periods', $thirdobject);
    } else {
        print_error('errperiodvalidation', 'tutorship');
    }*/

    // Don't need the objects any more
    unset($firstobject);
//    unset($secondobject);
//    unset($thirdobject);

	if ($firstid) {
        return true;
    } else {
        return false;
    }
   /* if ($firstid and $secondid and $thirdid) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Inserts all possible slots within a week.
 * Given a timeslot length in minutes, this function will create
 * all the possible slots within a week and return if the records 
 * were inserted or not.
 *
 * @param  string $length Time slot length in minutes.
 * @return boolean        Success/Failure.
 */
function tutorship_insert_timeslots($length) {
    global $DB;
    $slotseconds = (int) $length * 60;
    $starttime   = TUTORSHIP_STARTTIME * 60 * 60;
    $endtime     = TUTORSHIP_ENDTIME * 60 * 60;
	$breaktime =  (int)TUTORSHIP_BREAKTIME_MINUTES * 60;

	$stepTime =  $slotseconds + $breaktime;

    if ($slotseconds > 0) {
        // Inserts slots
		$time_cnt = 0;
        for ($i = $starttime; $i <= $endtime; $i += $stepTime) {
          //  for ($day = TUTORSHIP_MONDAY; $day <= TUTORSHIP_FRIDAY; $day++) {
              for ($day = TUTORSHIP_MONDAY; $day <= TUTORSHIP_SUNDAY; $day++) {
				
				$slot            = new stdClass();
                $slot->day       = $day;
                $slot->starttime = $i;
                $DB->insert_record('tutorship_timeslots', $slot);
                unset($slot);
            }
        }
    }

    // Checks if there are slots
    if ($DB->count_records_select('tutorship_timeslots', array()) > 0) {
        return true;
    } else {
        return false;
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////
// +--------------------------------+                                                      //
// |                                |                                                      //
// + Retrieving Database functions: |                                                      //
// |                                |                                                      //
// +--------------------------------+                                                      //
//                                                                                         //
// tutorship_get_teachers(): called from studentview.php to get course enrolled teachers.  //
// tutorship_get_slot_length(): called from studentview.php and teacherview.php.           //
/////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Gets teachers enrolled within a course.
 *
 * @param  int $courseid Course id.
 * @return object        Teachers enrolled in $courseid.
 */
function tutorship_get_teachers($courseid) {
    global $DB;
    $role     = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $roleid   = (int) $role->id;
    $context = context_course::instance($courseid);

    return get_role_users($roleid, $context);
}

/**
 * Returns the time slot length field from the config_plugin table.
 *
 * @param  null.
 * @return int Time slot length in seconds.
 */
function tutorship_get_slot_length() {
    global $DB;
    $timeslotlength = (int) get_config('tutorship', 'timeslotlength');

    if ($timeslotlength) {
        $timeslotlength *= 60;
    } else {
        $timeslotlength = TUTORSHIP_TIMESLOT_MINUTES * 60;
    }

    return $timeslotlength;
}

/////////////////////////////////////////////////////////////////////////////////////////////
// +------------------------------------------------------------+                          //
// |                                                            |                          //
// + Other functions: for converting, checking, creating links. +                          //
// |                                                            |                          //
// +------------------------------------------------------------+                          //
//                                                                                         //
// tutorship_get_slot_link(): called from teacherview.php to print Enable/Disable slots.   //
// tutorship_get_reserve_link(): called from studentview.php to print Reserve/Unreserve.   //
// tutorship_get_week_link(): called from studentview.php and teacherview.php to view week.//
// tutorship_get_reservation_info_link(): called from teacherview.php to view who reserved.//
// tutorship_get_email_link(): called from view.php for teacher's confirmation mails.      //
// tutorship_get_date(): called from locallib.php to get reservation date.                 //
// tutorship_get_reserve_date(): called from view.php to email reservation date string.    //
// tutorship_to_year(): called from locallib.php to get current or next year.              //
// tutorship_has_timetable(): called from studentview.php and teacherview.php to check.    //
// tutorship_get_current_period(): called from studentview and teacherview.php.            //
// tutorship_can_reserve(): called from view.php to check if a student can reserve.        //
// tutorship_validate_period_date(): called from locallib.php to validate the period dates.//
/////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Returns html link with url params and string checking if a slot
 * has already been enable. Enabling a slot means a new record in
 * tutorship_timetables table.
 *
 * @param  int   $timeslotid The id of slot referenced.
 * @param  int   $teacherid  The teacher id making changes.
 * @param  int   $periodid   The id of timetable period.
 * @param  array $urlparams  The url parameters included in the link.
 * @return link  $link       The url link with the slot id as parameter.
 */
function tutorship_get_slot_link($timeslotid, $teacherid, $periodid, $urlparams) {
    global $DB;
    $urlstr                   = '/mod/tutorship/view.php';
    $params                   = $urlparams;
    $params['slotid']         = $timeslotid;
    $params['selectedperiod'] = $periodid;
    $params['action']         = 2; // For enable/disable slot links we want to stay in edit view

    // Gets and checks if timeslotid is enabled

    $timetableconditions = array('teacherid' => $teacherid, 'periodid' => $periodid, 'timeslotid' => $timeslotid);
    $enabled             = $DB->record_exists('tutorship_timetables', $timetableconditions);

    if ($enabled) {
        return html_writer::link(new moodle_url($urlstr, $params), get_string('disable', 'tutorship'));
    } else {
        return html_writer::link(new moodle_url($urlstr, $params), get_string('enable', 'tutorship'));
    }
}

/**
 * Returns html link with url params and string checking if a timetable has
 * already been reserved. Allows reserve and unreserve link and reserved string.
 * Making reservations means a new record in tutorship_reserves table.
 *
 * @param  int   $timetableid   The timetable reserved.
 * @param  int   $studentid     The student id making reservations.
 * @param  int   $courseid      The course id where the student is making the reservations.
 * @param  int   $today         Today's timestamp in function of the selected week.
 * @param  array $urlparams     The url parameters included in the link.   
 * @return mixed $link          The url link with the timetable id as parameter or reserved string.
 */
function tutorship_get_reserve_link($timetableid, $studentid, $courseid, $today, $urlparams) {
    global $DB;
    $urlstr                = '/mod/tutorship/view.php';
    $params                = $urlparams;
    $weeknumber            = date('W', $today);
    $params['timetableid'] = $timetableid;
    //$params['sesskey']     = sesskey();

    // First checks if it is possible to reserve
    // Then checks if timetable week has been reserved on course 

    $reserveconditions = array('timetableid' => $timetableid, 'week' => $weeknumber , 'studentid' => $studentid);
    
    $reserved          = $DB->record_exists('tutorship_reserves', $reserveconditions);
    $teacherid         = $DB->get_field('tutorship_timetables', 'teacherid', array('id' => $timetableid));
    $cannotreserve     = $DB->get_field('tutorship_configs', 'noreserves', array('teacherid' => $teacherid));


    if ($cannotreserve) {
        return get_string('singletutorship', 'tutorship');
    } else {
        if ($reserved) {
            // Then checks if reservation was made by studentid in a specific week 

            $reserveconditions['studentid'] = $studentid;
            $reserveconditions['week']      = $weeknumber;
            $unreserve = $DB->record_exists('tutorship_reserves', $reserveconditions);

            if ($unreserve) {
                $confirmed    = $DB->get_field('tutorship_reserves', 'confirmed', $reserveconditions);
                $unreservestr = get_string('unreserve', 'tutorship');

                if ($confirmed) 
				{
                    $confirmedstr = get_string('confirmed', 'tutorship');


					// Get Reserves Date
					$timeSlotID         = $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $timetableid));
					$timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeSlotID));
					$year       = date('Y', time());
					$time       = gmdate('H:i', $timeslot->starttime);
					$daynumber  = date('d', tutorship_get_date($timeslot->day, $weeknumber, $year));
					$month      = date('m', tutorship_get_date($timeslot->day, $weeknumber, $year));

					$unixtime = mktime(0, 0, 0, (int) $month, (int) $daynumber, (int) $year);
					$unixtime += $timeslot->starttime;
					$nowUnixTime = strtotime("now");
					$deltaUnixTime = $unixtime - $nowUnixTime;
					//$limitTime = gmdate('d H:i:s', $deltaUnixTime); 
					//$limitTime = gmdate('d H:i:s', $deltaUnixTime);
					$guardTime = (int) get_config('tutorship', "guardtimelength");
					$guardUnixTime =  $guardTime * 60;
					
					// For Guard Time
					if ($guardUnixTime >= $deltaUnixTime)
					{
	                    return 'Guard Time<br>'.$confirmedstr;
					}
					else
					{
						return html_writer::link(new moodle_url($urlstr, $params), $unreservestr).'<br>'.$confirmedstr;
					}

                } else {
                    $notconfirmedstr = get_string('notconfirmed', 'tutorship');
                    return html_writer::link(new moodle_url($urlstr, $params), $unreservestr).'<br>'.$notconfirmedstr;
                }
            } else {
                return get_string('reserved', 'tutorship'); 
            }

        } else {

			// Create Link For reservation..
			// Check Max Class Size
				$keyName = "courseclasssize".$courseid;
				$maxClassCount = (int) get_config('tutorship', $keyName);
				//Current Class Size
				$searchCond = array('timetableid' => $timetableid, 'courseid'=>$courseid);
				$reservedList   = $DB->get_records('tutorship_reserves', $searchCond);
                
				$classMaxStatus = false;
				$cnt = 0;
				if(isset($reservedList) && count($reservedList) > 0)
				{
					$cnt = count($reservedList);
					if($cnt >=  $maxClassCount)
					{
						$classMaxStatus = true; 
					}
				}
				
		     //       return html_writer::link(new moodle_url($urlstr, $params), get_string('reserve', 'tutorship'));
			//return $timetableid;
				if ($classMaxStatus == true)
				{
					return "FULL"; 
				}
				else
				{
		            return html_writer::link(new moodle_url($urlstr, $params), get_string('reserve', 'tutorship'));
				}
        }
    }
}

/**
 * Returns html link with url params to view current or next week.
 *
 * @param  int   $week      Current-1 or next-2 week.
 * @param  array $urlparams The url parameters included in the link.
 * @return link  $link      The url link with the week as parameter.
 */
function tutorship_get_week_link($week, $urlparams) {
    global $DB;
    $urlstr                = '/mod/tutorship/view.php';
    $params                = $urlparams;
    $params['timetableid'] = 0; // We don't want next week same day to be reserved
    $params['action']      = 1; // For current/next week links we want to stay in view


	$str = "";
	$params['week'] = 1;
	$str = "Week View ";
	$str .= html_writer::link(new moodle_url($urlstr, $params), '1');

	$params['week'] = 2;
	$str .= "  ";
	$str .= html_writer::link(new moodle_url($urlstr, $params), '2');

	$params['week'] = 3;
	$str .= "  ";
	$str .= html_writer::link(new moodle_url($urlstr, $params), '3');

	$params['week'] = 4;
	$str .= "  ";
	$str .= html_writer::link(new moodle_url($urlstr, $params), '4');
	return $str;

    if ($week == 1) {           // Current week, so print next week link

		$params['week'] = 1;
		$str .= html_writer::link(new moodle_url($urlstr, $params), '  1  ');

		$params['week'] = 2;
		$str .= html_writer::link(new moodle_url($urlstr, $params), '  2  ');

		$params['week'] = 3;
		$str .= html_writer::link(new moodle_url($urlstr, $params), '  3  ');

		$params['week'] = 4;
		$str .= html_writer::link(new moodle_url($urlstr, $params), '  4  ');
		return $str;
        //return html_writer::link(new moodle_url($urlstr, $params), get_string('nextweek', 'tutorship'));
    }
	else if ($week == 2) {                    // Next week, so print current week link
        $params['week'] = 3;
        return html_writer::link(new moodle_url($urlstr, $params), get_string('nextweek', 'tutorship'));
    }
	else if ($week == 3) {                    // Next week, so print current week link
        $params['week'] = 4;
        return html_writer::link(new moodle_url($urlstr, $params), get_string('nextweek', 'tutorship'));
    }else {                    // Next week, so print current week link
        $params['week'] = 1;
        return html_writer::link(new moodle_url($urlstr, $params), get_string('currentweek', 'tutorship'));
    }
}

/**
 * Returns html link with url params to view who made the reservation,
 * also returns the cancell or confirm reservation link.
 *
 * @param  int   $courseid    Id of course where a reserve has been requested.
 * @param  int   $timetableid Id of timetable requested.
 * @param  int   $week        Week number.
 * @param  array $urlparams   The url parameters included in the link.
 * @return link  $links       The url link with the reserve id as parameter.
 */
function tutorship_get_reservation_info_link($courseid, $timetableid, $week, $urlparams) {
    global $DB;
    $urlstr               = '/mod/tutorship/view.php';
    $reservationcondition = array('courseid' => $courseid, 'timetableid' => $timetableid, 'week' => $week);

    if ($DB->record_exists('tutorship_reserves', $reservationcondition)) {
        $studentid           = $DB->get_field('tutorship_reserves', 'studentid', $reservationcondition);
        $student             = $DB->get_record('user', array('id' => $studentid));
        $reserveid           = $DB->get_field('tutorship_reserves', 'id', $reservationcondition);

		//Link For Calendar Event
		$timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $timetableid));
		$timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeslotid));
		$year       = date('Y', time());
		$time       = gmdate('H:i', $timeslot->starttime);
		$daynumber  = date('d', tutorship_get_date($timeslot->day, $week, $year));
		$month      = date('m', tutorship_get_date($timeslot->day, $week, $year));
		$unixtime = mktime(0, 0, 0, (int) $month, (int) $daynumber, (int) $year);
		$unixtime += $timeslot->starttime;
					
		//print_r($unixtime);die();

		$courseInfo             = $DB->get_record('course', array('id' => $courseid));
		$courseFullName = $courseInfo->fullname;

        $linkconditions      = array('id' => $studentid, 'course' => $courseid);
		$studentFullName = $student->firstname." " . $student->lastname;
        $linkstr             = $studentFullName .'<br>'.$student->username;//$student->email;
        $params              = $urlparams;
        $params['action']    = 1;
        $params['cancell']   = 1;
        $params['reserveid'] = $reserveid;

        $conditions = array('courseid' => $courseid, 'timetableid' => $timetableid, 'week' => $week, 'confirmed' => '1');
        $confirmed  = $DB->get_field('tutorship_reserves', 'confirmed', $conditions);

        $output  = html_writer::link(new moodle_url('/user/view.php', $linkconditions), $linkstr);
        $output .= '<br>';
	
		

        if (! $confirmed) {

            $confirmparams              = $urlparams;
            $confirmparams['action']    = 1;
            $confirmparams['cancell']   = 0;
            $confirmparams['reserveid'] = $reserveid;
            $output .= html_writer::link(new moodle_url($urlstr, $confirmparams), get_string('confirm', 'tutorship'));
            $output .= '<br>';

        }
		else
		{
			$calendarCondition =  array('view' => 'day', 'course' => $courseid, 'time' => $unixtime);
			$output .= html_writer::link(new moodle_url('/calendar/view.php', $calendarCondition), $courseFullName);
			$output .= '<br>';
		}

 // Marked For cancel in teacher       $output .= html_writer::link(new moodle_url($urlstr, $params), get_string('cancel', 'tutorship'));
        
    } else {
        $output = get_string('empty', 'tutorship');
    }

    return $output;
}

/**
 * Returns html link with url params to teacher's timetable view.
 *
 * @param  int  $coursemoduleid The course module id, where action is taking place.
 * @param  int  $week           The requested week number.
 * @return link $link           The html with url params.
 */
function tutorship_get_email_link($coursemoduleid, $week) {
    global $CFG;
    $params['id']     = $coursemoduleid;
    $params['week']   = $week;
    $params['action'] = 1; // We want to show view when clicking on the link  
    $link             = $CFG->wwwroot.'/mod/tutorship/view.php';
    return html_writer::link(new moodle_url($link, $params), get_string('gotoreservation', 'tutorship'));
}

/**
 * Returns date timestamp from day, week number and year.
 *
 * @see    tutorship_get_reserve_date().
 *
 * @param  int $day         The week day, 0-Monday, 4-Friday.
 * @param  int $weeknumber  The week number, from 1 to 52.
 * @param  int $year        The year.
 * @return int timestamp    The date in Unix timestamp format.
 */
function tutorship_get_date($day, $weeknumber, $year) {
    // Count from '0104' because January 4th is always in week 1
    // (according to ISO 8601).
    $time = strtotime($year.'0104 +'.($weeknumber - 1).' weeks');

    // Get the time of the first day of the week
    $mondaytime = strtotime('-'.(date('w', $time) - 1).' days', $time);

    // Return timestamp
    return strtotime('+'.$day.' days', $mondaytime);
}

/**
 * Returns reservation date string for mails.
 *
 * @param  int    $timetableid
 * @param  int    $studentid
 * @return string $datestr
 */
function tutorship_get_reserve_date($timetableid, $studentid) {
    global $DB;

    $reserveconditions = array('timetableid' => $timetableid, 'studentid' => $studentid);
    $timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $timetableid));
    $timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeslotid));
    $week       = $DB->get_field('tutorship_reserves', 'week', $reserveconditions);

    $year       = date('Y', time());
    $time       = gmdate('H:i', $timeslot->starttime);
    $daynumber  = date('d', tutorship_get_date($timeslot->day, $week, $year));
    $month      = date('m', tutorship_get_date($timeslot->day, $week, $year));

    switch ($timeslot->day) {
        case 0:
            $day = get_string('monday', 'tutorship');
            break;
        case 1:
            $day = get_string('tuesday', 'tutorship');
            break;
        case 2:
            $day = get_string('wednesday', 'tutorship');
            break;
        case 3:
            $day = get_string('thursday', 'tutorship');
            break;
        case 4:
            $day = get_string('friday', 'tutorship');
            break;
		case 5:
            $day = get_string('saturday', 'tutorship');
            break;
		case 6:
            $day = get_string('sunday', 'tutorship');
            break;
    }

    if ($week == date('W', time())) {
        $weekstr = get_string('current', 'tutorship');
    } else {
        $weekstr = get_string('next', 'tutorship');
    }
    
    $datestr  = $weekstr.': <b>'.$day.'</b>, '.get_string('at', 'tutorship').' <b>'.$time.'</b>';
    $datestr .= get_string('hours', 'tutorship').' (';
    $datestr .= get_string('day', 'tutorship').': <b>'.$daynumber.'</b>, '.get_string('month', 'tutorship').': <b>';
    $datestr .= $month.'</b>, '.get_string('year', 'tutorship').': <b>'.$year.'</b>)';

    return $datestr;
}

/**
 * Gets 0 and returns actual year in YYYY format, gets 1
 * and returns next year in YYYY format.
 *
 * @see    tutorship_insert_periods().
 *
 * @param  int $select Year number, 0 is this year and 1 next year.
 * @return boolean     Success/Failure.
 */
function tutorship_to_year($select) {
    $today = time();

    if ($select == 0) {
        return date('Y', $today);
    } else {
        return date('Y', $today) +  $select;//1;
    }
}

/**
 * Checks if a teacher has a timetable.
 *
 * @param  int $teacherid Id of the teacher.
 * @param  int $periodid  Id of the timetable period.
 * @return boolean        Success/Failure.
 */
function tutorship_has_timetable($teacherid, $periodid) {
    global $DB;

    if ($DB->count_records('tutorship_timetables', array('teacherid' => $teacherid, 'periodid' => $periodid)) == 0) {
        return false;
    } else {
        return true;
    }
}

/**
 * Returns the current period id by comparing the periods dates with today.
 *
 * @param  int $today   Today's timestamp.
 * @return int $perioid Current period id.
 */
function tutorship_get_current_period($today) {
    global $DB;
    $periodid = 0;
    $periods  = $DB->get_records('tutorship_periods');

    foreach ($periods as $period) {
        if (($period->startdate < $today) and ($period->enddate > $today)) {
            $periodid = $period->id;
        }
    }
    return $periodid;
}

/**
 * If student has made three reserves he can not do any more 
 * reserves for the same timetable.
 *
 * @param  int $timetableid Id of teacher's timetable.
 * @param  int $courseid    Id of course where reserves are taking place.
 * @param  int $studentid   Id of student making reserves.
 * @return boolean          Success/Failure.
 */
function tutorship_can_reserve($timetableid, $courseid, $studentid) {
    global $DB;
    $teacherid   = $DB->get_field('tutorship_timetables', 'teacherid', array('id' => $timetableid)); 
    $maxreserves = $DB->get_field('tutorship_configs', 'maxreserves', array('teacherid' => $teacherid));
    $numreserves = $DB->count_records('tutorship_reserves', array('courseid' => $courseid, 'studentid' => $studentid));

    if ($numreserves < $maxreserves) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if period start date is before end date.
 *
 * @param  object  $periodobject
 * @return boolean Success/Failure.
 */
function tutorship_validate_period_date($periodobject) {
    if ($periodobject->startdate < $periodobject->enddate) {
        return true;
    } else {
        return false;
    }
}



function tutorship_get_reserveMaxStatus($studentid, $courseid) {
    global $DB;
	$keyName = "coursemax".$courseid;
	$maxCount = (int) get_config('tutorship', $keyName);
	//$maxCount = $_CONSTANTS[$courseid];
    $reserveconditions = array('courseid' => $courseid, 'studentid' => $studentid);
    $reserveList          = $DB->get_records('tutorship_reserves', $reserveconditions);
	$status  = false;
	if(isset($reserveList) && count($reserveList) > 0)
	{
		$cnt = count($reserveList);
		if($cnt >=  $maxCount)
		{
			$status = true; 
		}
	}
	return $status;
   
}




function tutorship_get_reserve_Maxlink($timetableid, $studentid, $courseid, $today, $urlparams) {
    global $DB;
    $urlstr                = '/mod/tutorship/view.php';
    $params                = $urlparams;
    $weeknumber            = date('W', $today);
    $params['timetableid'] = $timetableid;
    //$params['sesskey']     = sesskey();

    // First checks if it is possible to reserve
    // Then checks if timetable week has been reserved on course 

    $reserveconditions = array('timetableid' => $timetableid, 'week' => $weeknumber , 'courseid'=>  $courseid);
    $reserved          = $DB->record_exists('tutorship_reserves', $reserveconditions);
    $teacherid         = $DB->get_field('tutorship_timetables', 'teacherid', array('id' => $timetableid));
    $cannotreserve     = $DB->get_field('tutorship_configs', 'noreserves', array('teacherid' => $teacherid));

    if ($cannotreserve) {
        return get_string('singletutorship', 'tutorship');
    } else {
        if ($reserved) {

            // Then checks if reservation was made by studentid in a specific week 

            $reserveconditions['studentid'] = $studentid;
            $reserveconditions['week']      = $weeknumber;
            $reserveconditions['courseid']      = $courseid;
			
            $unreserve = $DB->record_exists('tutorship_reserves', $reserveconditions);

            if ($unreserve) {
                $confirmed    = $DB->get_field('tutorship_reserves', 'confirmed', $reserveconditions);
                $unreservestr = get_string('unreserve', 'tutorship');

                if ($confirmed) {
                    $confirmedstr = get_string('confirmed', 'tutorship');
                    return html_writer::link(new moodle_url($urlstr, $params), $unreservestr).'<br>'.$confirmedstr;
                } else {
                    $notconfirmedstr = get_string('notconfirmed', 'tutorship');
                    return html_writer::link(new moodle_url($urlstr, $params), $unreservestr).'<br>'.$notconfirmedstr;
                }
            } else {
                return get_string('reserved', 'tutorship'); 
            }

        } else {
            return get_string('reserved', 'tutorship'); 
			//return html_writer::link(new moodle_url($urlstr, $params), get_string('reserve', 'tutorship'));
        }
    }
}

function tutorship_get_timezone_offset($remote_tz, $origin_tz = null) {
    if($origin_tz === null) {
        if(!is_string($origin_tz = date_default_timezone_get())) {
            return false; // A UTC timestamp was returned -- bail out!
        }
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime("now", $origin_dtz);
    $remote_dt = new DateTime("now", $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
    return $offset;
}


function tutorship_get_dateFromWeek($dayData ,$dayNumber) {
   
	if($dayData == "")
	{
		return "";
	}

	$currentWNumber             = date('W', $dayData);
	$year       = date('Y', $dayData);//time());
	$daynumber  = date('d', tutorship_get_date($dayNumber, $currentWNumber, $year));
	$month      = date('m', tutorship_get_date($dayNumber, $currentWNumber, $year)); 
    $dayFormat = $year ."-".$month."-".$daynumber;
	$dayFormat = date('M d', strtotime($dayFormat));

    return $dayFormat;
}


function tutorship_get_YearFromWeek($dayData ,$dayNumber) {
   
	if($dayData == "")
	{
		return "";
	}
	$calcDay =  $dayData + 24 * 60 * 60 * $dayNumber;
	$yearFormat       = date('Y', $calcDay);//time());
	
    return $yearFormat;
}


function tutorship_payment($payerEmail,$totalMoney ,$classCnt)
{

		$paypal_username = get_config('tutorship', 'paypaluseremail');
		$paypal_password = get_config('tutorship', 'paypaluserpassword');
		$paypal_signature = get_config('tutorship', 'paypalusersignature');

		$headers = array(
				'X-PAYPAL-SECURITY-USERID: ' . $paypal_username, 
				'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_password, 
				'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_signature, 
				'X-PAYPAL-SECURITY-SUBJECT: ' . '', 
				'X-PAYPAL-REQUEST-DATA-FORMAT: XML',
				'X-PAYPAL-RESPONSE-DATA-FORMAT: XML', 
				'X-PAYPAL-APPLICATION-ID: ' .'APP-80W284485P519543T',
				);
		$requestData = array();
		$requestData['requestEnvelope'] = array('detailLevel' => 'ReturnAll' , "errorLanguage"=>"en_US");
			$invoiceItem = array();
			$invoiceItem['merchantEmail'] = $paypal_username;
			$invoiceItem['payerEmail'] = $payerEmail;
			//$invoiceItem['number'] = 'INV 01-01';
			$invoiceItem['currencyCode'] = 'USD';
			$invoiceItem['invoiceDate'] = date('Y-m-dTH:i:s', time());
			$invoiceItem['dueDate'] = date('Y-m-dTH:i:s', time()+ 3 * 24 * 3600);
				$item = array();
				$item['item'] = array('name' => 'For Course Class Count: '.$classCnt,'quantity'=>1 ,'unitPrice'=>$totalMoney );
			$invoiceItem['itemList'] = $item;
		$requestData['invoice'] = $invoiceItem;

		$request = json_encode($requestData);

		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CreateAndSendInvoiceRequest xmlns="http://svcs.paypal.com/types/ap">';
			$XMLRequest .= '<requestEnvelope xmlns="">';
			$XMLRequest .= '<detailLevel>ReturnAll</detailLevel>';
			$XMLRequest .= '<errorLanguage>en_US</errorLanguage>';
			$XMLRequest .= '</requestEnvelope>';
		$XMLRequest .= '<invoice xmlns="">';
		$XMLRequest .= '<merchantEmail xmlns="">Starqueen106@hotmail.com</merchantEmail>';
		$XMLRequest .= '<payerEmail xmlns="">' . $payerEmail . '</payerEmail>';
		//$XMLRequest .= $Number != '' ? '<number xmlns="">' . $Number . '</number>' : '';
		
		
		$XMLRequest .= '<itemList xmlns="">';
				$XMLRequest .= '<item xmlns="">';
				$XMLRequest .='<name xmlns="">' . 'For Course Class Count: '.$classCnt . '</name>';
				$XMLRequest .= '<description xmlns=""></description>';
				$XMLRequest .= '<quantity xmlns="">1</quantity>';
				$XMLRequest .= '<unitPrice xmlns="">' . $totalMoney . '</unitPrice>' ;
				$XMLRequest .= '</item>';	
			$XMLRequest .= '</itemList>';


		
		$XMLRequest .=  '<currencyCode xmlns="">USD</currencyCode>';
		$XMLRequest .=  '<invoiceDate xmlns="">' . date('Y-m-d\TH:i:s', time()) . '</invoiceDate>';
		$XMLRequest .=  '<dueDate xmlns="">' . date('Y-m-d\TH:i:s', time()+ 3 * 24 * 3600) . '</dueDate>';
		//$XMLRequest .= $PaymentTerms != '' ? '<paymentTerms xmlns="">' . $PaymentTerms . '</paymentTerms>' : '';
		//$XMLRequest .= $DiscountPercent != '' ? '<discountPercent xmlns="">' . $DiscountPercent . '</discountPercent>' : '';
		//$XMLRequest .= $DiscountAmount != '' ? '<discountAmount xmlns="">' . $DiscountAmount . '</discountAmount>' : '';
		//$XMLRequest .= $Terms != '' ? '<terms xmlns="">' . $Terms . '</terms>' : '';
		//$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		//$XMLRequest .= $MerchantMemo != '' ? '<merchantMemo xmlns="">' . $MerchantMemo . '</merchantMemo>' : '';
		
		
		//$XMLRequest .= $ShippingAmount != '' ? '<shippingAmount xmlns="">' . $ShippingAmount . '</shippingAmount>' : '';
		//$XMLRequest .= $ShippingTaxName != '' ? '<shippingTaxName xmlns="">' . $ShippingTaxName . '</shippingTaxName>' : '';
		//$XMLRequest .= $ShippingTaxRate != '' ? '<shippingTaxRate xmlns="">' . $ShippingTaxRate . '</shippingTaxRate>' : '';
		//$XMLRequest .= $LogoURL != '' ? '<logoUrl xmlns="">' . $LogoURL . '</logoUrl>' : '';
		//$XMLRequest .= '<referrerCode xmlns="">'.$this->APIButtonSource.'</referrerCode>';
		$XMLRequest .= '</invoice>';
		$XMLRequest .= '</CreateAndSendInvoiceRequest>';

		$requestURL = 'https://svcs.sandbox.paypal.com/Invoice/CreateAndSendInvoice';

		//$curl = curl_init('https://api-3t.paypal.com/nvp');

		$curl = curl_init();
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_URL, $requestURL);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLRequest);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	/*	$curl = curl_init($requestURL);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLRequest);*/
		
		$response = curl_exec($curl);
		
		curl_close($curl);
		print_r($response);die();
		if (strcmp ($response, "Success") == 0){
			//print_r($response);die();
		}

		return $response;

		/*
		//$payment_type = 'Sale';
			
		$request  = 'METHOD=DoCapture';//DoDirectPayment';
		$request = '&VERSION=51.0';
		$request .= '&USER='.$paypal_username; // your paypal pro username
		$request .= '&PWD='.$paypal_password ; //your paypal pro password  
		$request .= '&SIGNATURE='.$paypal_signature ;  ////your paypal signature password  
		$request .= '&CUSTREF=' . (int)rand(100000,999999);
		$request .= '&PAYMENTACTION=' . $payment_type;
		$request .= '&AMT='.$mount;//$_POST['courses'];
		$request .= '&CREDITCARDTYPE=' . $paypal_cctype;
		$request .= '&ACCT=' . urlencode(str_replace(' ', '', $_POST['cc_number']));
		$request .= '&EXPDATE=' . urlencode($_POST['cc_expire_date_month'] . $_POST['cc_expire_date_year']);
		$request .= '&CVV2=' . urlencode($_POST['cc_cvv2']);
		
		if ($_POST['cc_type'] == 'SWITCH' || $_POST['cc_type'] == 'SOLO') { 
			$request .= '&CARDISSUE=' . urlencode($_POST['cc_issue']);
		}
		
		$request .= '&FIRSTNAME=' . urlencode($form_firstname);
		$request .= '&LASTNAME=' . urlencode($form_lastname);
		$request .= '&EMAIL=' . urlencode($form_email);
		$request .= '&PHONENUM=' . urlencode($form_phone);
		$request .= '&IPADDRESS=' . urlencode($_SERVER['REMOTE_ADDR']);
		$request .= '&STREET=' . urlencode($form_address);
		$request .= '&CITY=' . urlencode($form_city);
		$request .= '&STATE=' . urlencode($form_state);
		$request .= '&ZIP=' . urlencode($form_zip);
		$request .= '&COUNTRYCODE=' . urlencode($form_country);
		$request .= '&CURRENCYCODE=' . urlencode('USD');

		// $curl = curl_init('https://api-3t.paypal.com/nvp'); // This is for live account
		//$curl = curl_init('https://api-3t.sandbox.paypal.com/nvp'); // This is for sandbox account
		
		 
		$request .= '&merchantEmail='.$paypal_username;//$_POST['courses'];
		$request .= '&payerEmail=test11@gmail.com';
		$request .= '&number=123';
		$request .= '&merchantInfo=123';

		 //https://api-3t.paypal.com/nvp/v1/Invoice/CreateAndSendInvoice
		 $requestURL = https://svcs.paypal.com/Invoice/CreateAndSendInvoice

		//$curl = curl_init('https://api-3t.paypal.com/nvp');
		$curl = curl_init($requestURL);
		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		
		$response = curl_exec($curl);
		
		curl_close($curl);
		return $response;*/
} 

function tutorship_salepayment($payerList)
{

		$paypal_username = get_config('tutorship', 'paypaluseremail');
		$paypal_password = get_config('tutorship', 'paypaluserpassword');
		$paypal_signature = get_config('tutorship', 'paypalusersignature');
		$paypal_email = get_config('tutorship', 'paypalmenchatemail');
			
			$ReturnURL = "http://sandbox.domain.com/paypal/class/1.2/Pay_Cancel.php";
			$CancelURL = "http://sandbox.domain.com/paypal/class/1.2/Pay_Return.php";

		$headers = array(
				'X-PAYPAL-SECURITY-USERID: ' . $paypal_username, 
				'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_password, 
				'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_signature, 
				'X-PAYPAL-SECURITY-SUBJECT: ' . '', 
				'X-PAYPAL-REQUEST-DATA-FORMAT: XML',
				'X-PAYPAL-RESPONSE-DATA-FORMAT: XML', 
				'X-PAYPAL-APPLICATION-ID: ' .'APP-80W284485P519543T',
				);

		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PayRequest xmlns="http://svcs.paypal.com/types/ap">';
			$XMLRequest .= '<requestEnvelope xmlns="">';
				$XMLRequest .= '<detailLevel>ReturnAll</detailLevel>';
				$XMLRequest .= '<errorLanguage>en_US</errorLanguage>';
			$XMLRequest .= '</requestEnvelope>';
		$XMLRequest .= '<actionType xmlns="">PAY</actionType>' ;
		$XMLRequest .= '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>';

		$XMLRequest .= '<receiverList xmlns="">';
		foreach($payerList as $payItem){
			$XMLRequest .= '<receiver xmlns="">';
			$XMLRequest .= '<amount xmlns="">' . $payItem['total']. '</amount>';
			$XMLRequest .= '<email xmlns="">' . $payItem['email']. '</email>';			
			$XMLRequest .= '</receiver>';
		}
		$XMLRequest .= '</receiverList>';
	
	$XMLRequest .= '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>';
	$XMLRequest .=  '<currencyCode xmlns="">USD</currencyCode>';
	$XMLRequest .= '<senderEmail xmlns="">'.$paypal_email.'</senderEmail>';
$XMLRequest .= '</PayRequest>';
		

		$requestURL = 'https://svcs.sandbox.paypal.com/AdaptivePayments/Pay';
		$curl = curl_init();
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_URL, $requestURL);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLRequest);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


		$response = curl_exec($curl);
		
		curl_close($curl);

		$DOM = new DOMDocument();
		$DOM -> loadXML($response);
				// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = "";
		$Ack = $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue;
		$Build = $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue ;
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue;
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue;
		
		$PayKey = $DOM -> getElementsByTagName('payKey') -> item(0) -> nodeValue;
		$PaymentExecStatus = $DOM -> getElementsByTagName('paymentExecStatus') -> item(0) -> nodeValue;
		
		$RedirectURL = 'https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay?paykey='.$PayKey;
//print_R($response);die();
			$ResponseDataArray = array(
			   'Errors' => $Errors, 
			   'Ack' => $Ack, 
			   'Build' => $Build, 
			   'CorrelationID' => $CorrelationID, 
			   'Timestamp' => $Timestamp, 
			   'PayKey' => $PayKey, 
			   'PaymentExecStatus' => $PaymentExecStatus, 
			   'RedirectURL' => $PayKey != '' ? $RedirectURL : '', 
			   'XMLRequest' => $XMLRequest, 
			   'XMLResponse' => $XMLResponse
			   );
		return $ResponseDataArray;
} 

function tutorship_masspayment($payerEmail,$totalMoney ,$classCnt)
{

		$paypal_username = 'starqueen106_api1.hotmail.com';//get_config('tutorship', 'paypaluseremail');
		$paypal_password = '7GE6VNU459P26LYG';//get_config('tutorship', 'paypaluserpassword');
		$paypal_signature = 'A9A0gZTWcGyirG.qnLuzNtP0Yj-IAc2C3snlkSIs6jm-6VkTX6lMETGL';//get_config('tutorship', 'paypalusersignature');


		$headers = array(
				'X-PAYPAL-SECURITY-USERID: ' . $paypal_username, 
				'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_password, 
				'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_signature, 
				'X-PAYPAL-SECURITY-SUBJECT: ' . '', 
				'X-PAYPAL-REQUEST-DATA-FORMAT: XML',
				'X-PAYPAL-RESPONSE-DATA-FORMAT: XML', 
				'X-PAYPAL-APPLICATION-ID: ' .'APP-80W284485P519543T',
				);


		$request = 'USER='.$paypal_username; // your paypal pro username
	//	$request .= '&PWD='.$paypal_password; //your paypal pro password  
		$request .= '&SIGNATURE='.$paypal_signature;  ////your paypal signature password  
		$request .= '&BUTTONSOURCE=AngellEYE_PHPClass';  ////your paypal signature password  
		$request  = '&METHOD=MassPay';//DoDirectPayment';
		$request .= '&VERSION=51.0';
		$request .= '&EMAILSUBJECT='.urlencode('Test massPay');
		$request .= '&CURRENCYCODE='.urlencode('USD');
		$request .= '&RECEIVERTYPE='.urlencode('EmailAddress');//('EmailAddress');
		$request .= '&L_EMAIL0='.urlencode($payerEmail);//($paypal_username);
		//$request .= '&L_RECEIVERID0='.urlencode($payerEmail);
		$request .= '&L_AMT0='.$totalMoney;
		$request .= '&L_UNIQUEID0='."AAAAAA12311";
		$request .= '&L_NOTE0='."AAAAAA";

		// $curl = curl_init('https://api-3t.paypal.com/nvp'); // This is for live account
		//$curl = curl_init('https://api-3t.sandbox.paypal.com/nvp'); // This is for sandbox account
		//print_r($request);die();
		 $requestURL =  'https://api-3t.paypal.com/nvp';
		 //'https://api-3t.paypal.com/nvp';//'https://api-3t.paypal.com/nvp';

		//$curl = curl_init('https://api-3t.paypal.com/nvp');
		
		$curl = curl_init();
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_URL, $requestURL);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


		$response = curl_exec($curl);
		
		curl_close($curl);
		
		print_R($response);die();
		return $response;
} 



/// For OpenMeeting
function tutorship_insert_room($roomID ,$teacherid, $timetableid, $week ,$year) {
    global $DB;

    $reserve                = new stdClass();
    $reserve->roomid      = $roomID;
    $reserve->classid     = $timetableid;
    $reserve->teacherid   = $teacherid;
    $reserve->weekid          = $week;
    $reserve->yearid          = $year;
    $reserve->createdate   = time();

    $id = $DB->insert_record('tutorship_openmeeting', $reserve);
    unset($reserve);
    return $id;
}


function tutorship_insert_contract($courseid, $teacherid) {
    global $DB;

    $contract                = new stdClass();
    $contract->course      = $courseid;
    $contract->teacherid     = $teacherid;
    $contract->createdate   = time();

    $id = $DB->insert_record('tutorship_contract', $contract);
    unset($contract);
    return $id;
}