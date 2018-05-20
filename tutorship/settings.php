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
 * The tutorship module configuration variables.
 *
 * The values defined here are often used as defaults for all module instances.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die();





if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/tutorship/locallib.php');

    // Times array
    $times = array();
    for ($i = 8; $i <= 21; $i++) { // From 8:00h to 21:00h
        $times[$i] = $i;
    }

    // Days array
    $days = array();
    for ($i = 1; $i <= 31; $i++) { // From 1 up to 31 days
        $days[$i] = $i;
    }

    // Months array
    $months = array();
    for ($i = 1; $i <= 12; $i++) { // From 1 up to 12 months
        $months[$i] = $i;
    }

    // Years array
    $years = array();
    for ($i = 0; $i <= 10; $i++) { // From this year up to next year
        $years[$i] = date('Y', time()) + $i;
    }

/// Default config
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Inserts initial general default settings config if empty
    // These values are common to all module instances
    tutorship_insert_default_config();
    
    // Introductory explanation for all settings defaults
    $settings->add(new admin_setting_heading('tutorshipintro', get_string('confsettings', 'tutorship'), 
                   get_string('configintro', 'tutorship')));

/// Time slot limit
    $settings->add(new admin_setting_configtext('tutorship/timeslotlength', get_string('timeslotlength', 'tutorship'),
                   get_string('configtimeslot', 'tutorship'), TUTORSHIP_TIMESLOT_MINUTES, PARAM_INT, 2));

// Break Time Setting
    $settings->add(new admin_setting_configtext('tutorship/breaktimelength', "Break Time length (minutes)",
                   "Break time slot length, the number of minutes per interview session.", TUTORSHIP_BREAKTIME_MINUTES, PARAM_INT, 2));


/// Start time for timetable days
    $settings->add(new admin_setting_configselect('tutorship/starttime', get_string('starttime', 'tutorship'),
                   get_string('configstarttime', 'tutorship'), TUTORSHIP_STARTTIME, $times));

/// End time for timetable days
    $settings->add(new admin_setting_configselect('tutorship/endtime', get_string('endtime', 'tutorship'),
                   get_string('configendtime', 'tutorship'), TUTORSHIP_ENDTIME, $times));


// Guard Time Setting
    $settings->add(new admin_setting_configtext('tutorship/guardtimelength', "Guard Time length (minutes)",
                   "A guard time prevents students from changing their booking shortly before the appointment", TUTORSHIP_GUARDTIME_MINUTES, PARAM_INT, 2));

// Notification Setting time Befor Start Class
    $settings->add(new admin_setting_configtext('tutorship/notifybeforestartclass', "Notification Set Time Before Start Class (minutes)",
                   "A Notification Send Mail Time before Start Class", TUTORSHIP_NOTIFYTIME_MINUTES, PARAM_INT, 2));


    $settings->add(new admin_setting_heading('paypalinfor', "Paypal Information",
                  "Paypal API information For payment"));

// PayPal User Email
    $settings->add(new admin_setting_configtext('tutorship/paypalmenchatemail', "Paypal Email Address",
                   "A PayPal MenChat Emial Address", TUTORSHIP_PAYPAL_MENCHAT_EMAIL, PARAM_TEXT, 30));

// PayPal User Name For API
    $settings->add(new admin_setting_configtext('tutorship/paypaluseremail', "Paypal User Name",
                   "A PayPal API User Name for Paypal API", TUTORSHIP_PAYPAL_EMAIL, PARAM_TEXT, 30));

// PayPal User Password
    $settings->add(new admin_setting_configtext('tutorship/paypaluserpassword', "Paypal Password",
                   "A PayPal Password for Paypal API", TUTORSHIP_PAYPAL_PASSWORD, PARAM_TEXT, 30));

// PayPal User Signature
    $settings->add(new admin_setting_configtext('tutorship/paypalusersignature', "Paypal Signature",
                   "A PayPal Signature for Paypal API", TUTORSHIP_PAYPAL_SIGNATURE, PARAM_TEXT, 30));



// TODO: There are too many repeated select elements in next lines.
//       Change day, month and year period select fields to a new date selector.
//       See MDL-24413 for more info.

/// First period
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/*
		Removed By require on 2018-01-15
	$settings->add(new admin_setting_heading('firstperiod', get_string('firstperiod', 'tutorship'),
                   get_string('configfirstperiod', 'tutorship')));

    // First period description
    $settings->add(new admin_setting_configtext('tutorship/firstperioddesc', get_string('firstperioddesc', 'tutorship'),
                   get_string('configdesc', 'tutorship'), get_string('firstperiod', 'tutorship'), PARAM_TEXT, 30));
*/
/// First period start date
    $settings->add(new admin_setting_heading('firstperiodstart', get_string('startdate', 'tutorship'), ''));

    // First period start day
    $settings->add(new admin_setting_configselect('tutorship/firstperiodstartday', get_string('startday', 'tutorship'),
                   get_string('configstartday', 'tutorship'), TUTORSHIP_FIRSTPERIOD_STARTDAY, $days));

    // First period start month
    $settings->add(new admin_setting_configselect('tutorship/firstperiodstartmonth', get_string('startmonth', 'tutorship'),
                   get_string('configstartmonth', 'tutorship'), TUTORSHIP_FIRSTPERIOD_STARTMONTH, $months));

    // First period start year
    $settings->add(new admin_setting_configselect('tutorship/firstperiodstartyear', get_string('startyear', 'tutorship'),
                   get_string('configstartyear', 'tutorship'), TUTORSHIP_FIRSTPERIOD_STARTYEAR, $years));

/// First period end date
    $settings->add(new admin_setting_heading('firstperiodend', get_string('enddate', 'tutorship'), ''));
    
    // First period end day
    $settings->add(new admin_setting_configselect('tutorship/firstperiodendday', get_string('endday', 'tutorship'),
                   get_string('configendday', 'tutorship'), TUTORSHIP_FIRSTPERIOD_ENDDAY, $days));

    // First period end month
    $settings->add(new admin_setting_configselect('tutorship/firstperiodendmonth', get_string('endmonth', 'tutorship'),
                   get_string('configendmonth', 'tutorship'), TUTORSHIP_FIRSTPERIOD_ENDMONTH, $months)); 
    
    // First period end year
    $settings->add(new admin_setting_configselect('tutorship/firstperiodendyear', get_string('endyear', 'tutorship'),
                   get_string('configendyear', 'tutorship'), TUTORSHIP_FIRSTPERIOD_ENDYEAR, $years));



/* -- Set Max Reservation Part-----*/

$settings->add(new admin_setting_heading('setmaxreservation', "Set Max Reservation For Course", ''));

$courses = $DB->get_records('course');

if(isset($courses) && count($courses) > 0)
{
	foreach ($courses as $item)
	{
		if(isset($item) && $item->format == "topics")
		{
			$courseItemName = $item->fullname;
			$courseID = $item->id;
			$settingKey = "tutorship/coursemax".$courseID;
			$settings->add(new admin_setting_configtext($settingKey, $courseItemName,
                   "", $_CONSTANTS[$courseID], PARAM_INT, 2));
		}
	}	
}


/* -- Set Max Class Size -----*/

$settings->add(new admin_setting_heading('setmaxclasssize', "Set Max Class Size For Course", ''));
if(isset($courses) && count($courses) > 0)
{
	foreach ($courses as $item)
	{
		if(isset($item) && $item->format == "topics")
		{
			$courseItemName = $item->fullname;
			$courseID = $item->id;
			$settingKey = "tutorship/courseclasssize".$courseID;
			$settings->add(new admin_setting_configtext($settingKey, $courseItemName,
                   "", $_CONSTANTS_CLASS[$courseID], PARAM_INT, 2));
		}
	}	
}



	// For Open Meeting Setting
    $settings->add(new admin_setting_heading('tutorshipopenmeeting', "OpenMeeting Setting", ""));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_ip', "Server Host or IP",
                   "OpenMeetings Server Host or IP", TUTORSHIP_OPENMEETING_IP, PARAM_TEXT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_port', "Server Port",
                   "OpenMeetings Server Port", TUTORSHIP_OPENMEETING_PORT, PARAM_INT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_user', "Admin User",
                   "OpenMeetings Admin User", TUTORSHIP_OPENMEETING_USER, PARAM_TEXT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_password', "Admin Password",
                   "OpenMeetings Admin User Password", TUTORSHIP_OPENMEETING_USERPASS, PARAM_TEXT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_moodlekey', "Moodle Key",
                   "Advanced setting: OpenMeetings Module key (vary for multiple instances using same OpenMeetings Server)", TUTORSHIP_OPENMEETING_MOODLE_KEY, PARAM_TEXT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_webapp', "Webapp Name",
                   "Advanced setting: If you have renamed the OpenMeetings webapp you can enter your alternative name here.", TUTORSHIP_OPENMEETING_WEBAPP, PARAM_TEXT, 30));

	$settings->add(new admin_setting_configtext('tutorship/openmeetings_protocol', "Protocol",
                   "Protocol to be used while constructing Openmeetings URLs (default: http)", TUTORSHIP_OPENMEETING_PROTOCOL, PARAM_TEXT, 30));


/*
/// Second period
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $settings->add(new admin_setting_heading('secondperiod', get_string('secondperiod', 'tutorship'),
                   get_string('configsecondperiod', 'tutorship')));

    // Second period description
    $settings->add(new admin_setting_configtext('tutorship/secondperioddesc', get_string('secondperioddesc', 'tutorship'),
                   get_string('configdesc', 'tutorship'), get_string('secondperiod', 'tutorship'), PARAM_TEXT, 30));

/// Second period start date
    $settings->add(new admin_setting_heading('secondperiodstart', get_string('startdate', 'tutorship'), ''));

    // Second period start day
    $settings->add(new admin_setting_configselect('tutorship/secondperiodstartday', get_string('startday', 'tutorship'),
                   get_string('configstartday', 'tutorship'), TUTORSHIP_SECONDPERIOD_STARTDAY, $days));

    // Second period start month
    $settings->add(new admin_setting_configselect('tutorship/secondperiodstartmonth', get_string('startmonth', 'tutorship'),
                   get_string('configstartmonth', 'tutorship'), TUTORSHIP_SECONDPERIOD_STARTMONTH, $months));

    // Second period start year
    $settings->add(new admin_setting_configselect('tutorship/secondperiodstartyear', get_string('startyear', 'tutorship'),
                   get_string('configstartyear', 'tutorship'), TUTORSHIP_SECONDPERIOD_STARTYEAR, $years));

/// Second period end date
    $settings->add(new admin_setting_heading('secondperiodend', get_string('enddate', 'tutorship'), ''));
    
    // Second period end day
    $settings->add(new admin_setting_configselect('tutorship/secondperiodendday', get_string('endday', 'tutorship'),
                   get_string('configendday', 'tutorship'), TUTORSHIP_SECONDPERIOD_ENDDAY, $days));

    // Second period end month
    $settings->add(new admin_setting_configselect('tutorship/secondperiodendmonth', get_string('endmonth', 'tutorship'),
                   get_string('configendmonth', 'tutorship'), TUTORSHIP_SECONDPERIOD_ENDMONTH, $months));

    // Second period end year
    $settings->add(new admin_setting_configselect('tutorship/secondperiodendyear', get_string('endyear', 'tutorship'),
                   get_string('configendyear', 'tutorship'), TUTORSHIP_SECONDPERIOD_ENDYEAR, $years));

/// Third period
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $settings->add(new admin_setting_heading('thirdperiod', get_string('thirdperiod', 'tutorship'),
                   get_string('configthirdperiod', 'tutorship')));

    // Third period description
    $settings->add(new admin_setting_configtext('tutorship/thirdperioddesc', get_string('thirdperioddesc', 'tutorship'),
                   get_string('configdesc', 'tutorship'), get_string('thirdperiod', 'tutorship'), PARAM_TEXT, 30));

/// Third period start date
    $settings->add(new admin_setting_heading('thirdperiodstart', get_string('startdate', 'tutorship'), ''));

    // Third period start day
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodstartday', get_string('startday', 'tutorship'),
                   get_string('configstartday', 'tutorship'), TUTORSHIP_THIRDPERIOD_STARTDAY, $days));

    // Third period start month
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodstartmonth', get_string('startmonth', 'tutorship'),
                   get_string('configstartmonth', 'tutorship'), TUTORSHIP_THIRDPERIOD_STARTMONTH, $months));

    // Third period start year
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodstartyear', get_string('startyear', 'tutorship'),
                   get_string('configstartyear', 'tutorship'), TUTORSHIP_THIRDPERIOD_STARTYEAR, $years));

/// Third period end date
    $settings->add(new admin_setting_heading('thirdperiodend', get_string('enddate', 'tutorship'), ''));
    
    // Third period end day
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodendday', get_string('endday', 'tutorship'),
                   get_string('configendday', 'tutorship'), TUTORSHIP_THIRDPERIOD_ENDDAY, $days));
    
    // Third period end month
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodendmonth', get_string('endmonth', 'tutorship'),
                   get_string('configendmonth', 'tutorship'), TUTORSHIP_THIRDPERIOD_ENDMONTH, $months));

    // Third period end year
    $settings->add(new admin_setting_configselect('tutorship/thirdperiodendyear', get_string('endyear', 'tutorship'),
                   get_string('configendyear', 'tutorship'), TUTORSHIP_THIRDPERIOD_ENDYEAR, $years));

*/
}
