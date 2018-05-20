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
 * Library of interface functions and constants for module tutorship.
 *
 * All the core API Moodle functions, needed to allow the module to 
 * work integrated in Moodle are placed here. All the tutorship specific 
 * functions, needed to implement all the module logic, are placed at 
 * locallib.php. This will help to save some memory when Moodle is 
 * performing actions across all modules.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die(); // Direct access to this file is forbidden

// If you for some reason need to use global variables instead of constants, do not forget to make them
// global as this file can be included inside a function scope. However, using the global variables
// at the module level is not a recommended.
// Examples:
// global $NEWMODULE_GLOBAL_VARIABLE;
// $NEWMODULE_QUESTION_OF = array('Life', 'Universe', 'Everything');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object.
 * @param  object $tutorship An object from the form in mod_form.php.
 * @return mixed int/boolean The id of the newly inserted record or failed.
 */
function tutorship_add_instance($tutorship) {
    global $DB, $USER, $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');

    // Only one instance allowed per course
    if ($DB->count_records('tutorship', array('course' => $tutorship->course)) == 0) {

/// Tutorship:
        // Inserts the new instance record
        if (empty($tutorship->name)) {
            $tutorship->name = get_string('tutoringschedule', 'tutorship');
        }
        $tutorship->timemodified = time();
        $tutorship->id = $DB->insert_record('tutorship', $tutorship);

/// Periods:
        // If table periods is empty inserts periods defined at settings.php
        // These values are particulary to a timetable
        if ($DB->count_records('tutorship_periods') == 0) {
            if (! tutorship_insert_periods($tutorship)) {
                print_error('errperiods', 'tutorship');
            }
        } else { // Update
            tutorship_update_periods($tutorship);
        }

/// Timeslots:
        // If table timeslots is empty inserts all possible timeslots within a week
        // This table has always the same values
        if ($DB->count_records('tutorship_timeslots') == 0) {
            $slotlength = get_config('tutorship', 'timeslotlength');
            if (empty($slotlength)) {
                $slotlength = TUTORSHIP_TIMESLOT_MINUTES;
            }
            if (! tutorship_insert_timeslots($slotlength)) {
                print_error('errtimeslots', 'tutorship');
            }
        }

        return $tutorship->id;

    } else {
        print_error('errinstance', 'tutorship');
        return false;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object.
 * @param  object $tutorship An object from the form in mod_form.php.
 * @return mixted int/boolean The id of the updated record or failed.
 */
function tutorship_update_instance($tutorship) {
    global $DB, $USER;
    require_once(dirname(__FILE__) . '/locallib.php');
    
    $tutorship->id              = $tutorship->instance;
    $tutorship->timemodified    = time();

    // Update
    if ($tutorship->id = $DB->update_record('tutorship', $tutorship)) {
        return $tutorship->id;
    } else {
        return false;
    }
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object.
 * @param  int $id Id of the module instance.
 * @return boolean Success/Failure.
 */
function tutorship_delete_instance($id) {
    global $DB;

    if (! $tutorship = $DB->get_record('tutorship', array('id'=>$id))) {
        return false;
    }

    if ($DB->delete_records('tutorship', array('id' => $tutorship->id))) {
        if ($DB->count_records('tutorship', array()) == 0) {
            // If none instances left delete periods and time slots
            // common to all instances and generated by configuration
            // settings.
            $DB->delete_records('tutorship_periods');
            $DB->delete_records('tutorship_timeslots');
        }
        return true;
    } else {
        return false;
    }

}

/**
 * Indicates API features what tutorship supports, returning the 
 * information if the module supports a feature.
 *
 * @see    plugin_supports() in lib/moodlelib.php.
 * @param  string $feature FEATURE_xx constant for requested feature.
 * @return mixed true if the feature is supported, null if unknown.
 */
function tutorship_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;

        default: return null;
    }
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function tutorship_user_outline($course, $user, $mod, $tutorship) {
    $return         = new stdClass;
    $return->time   = 0;
    $return->info   = '';
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function tutorship_user_complete($course, $user, $mod, $tutorship) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in tutorship activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function tutorship_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron.
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function tutorship_cron() {
    global $DB;
    $now      = time();
    $day      = date('N', $now) - 1; // Our days are from 0-Mon to 4-Fri ----- NOW  & DAYS A WEEK
    $week     = date('W', $now);
    //$lastweek = $week - 1;

/// Deletes past reservations
    // Gets current week reserves
    $reserves = $DB->get_records('tutorship_reserves', array('week' => $week));
    foreach ($reserves as $reserve) {
        $timeslotid  = $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $reserve->timetableid));
        $timeslotday = $DB->get_field('tutorship_timeslots', 'day', array('id' => $timeslotid));
        if ($timeslotday < $day) {
            // Deletes previous days reserves
            $DB->delete_records('tutorship_reserves', array('id' => $reserve->id));
        }
    }
if (0) {
    tutorship_get_date($day, $weeknumber, $year);

    // Removes last week reserves
    if ($DB->delete_records('tutorship_reserves', array('week' => $lastweek))) {
        return true;
    } else {
        return false;
    }
}

/// Updates period year
    // Gets periods
    $periods = $DB->get_records('tutorship_periods');
    foreach ($periods as $period) {
        if ($period->enddate < $now) { // Updates increases year
            $currentyear  = date('Y', $now);
            $oneyear      = mktime(0, 0, 0, 12, 31, $currentyear) - mktime(0, 0, 0, 12, 31, $currentyear - 1);           
            $newstartdate = $period->startdate + $oneyear;
            $newenddate   = $period->enddate + $oneyear;
            $DB->set_field('tutorship_periods', 'startdate', $startdate, array('id' => $period->id));
            $DB->set_field('tutorship_periods', 'enddate', $enddate, array('id' => $period->id));
        }
    }

    return true;
    // The day one period ends, updates period year to next year
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of tutorship. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $tutorshipid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function tutorship_get_participants($tutorshipid) {
    return false;
}

/**
 * This function returns if a scale is being used by one tutorship
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $tutorshipid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function tutorship_scale_used($tutorshipid, $scaleid) {
    global $DB;

    $return = false;

    //$rec = $DB->get_record("tutorship", array("id" => "$tutorshipid", "scale" => "-$scaleid"));
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

/**
 * Checks if scale is being used by any instance of tutorship.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any tutorship
 */
function tutorship_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('tutorship', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
//function tutorship_uninstall() {
//    return true;
//}

//Some more functions should be added. Among these I want to list: 

/**
 * This actually does the resetting. It is called indirectly, /course/reset.php calls reset_course_userdata() 
 * in /lib/moodlelib.php, which then calls the functions for each module. The $data parameter is what came 
 * back from the form printed by forum_reset_course. In particular, $data->courseid is the id of the coures 
 * we are cleaning up.
 * The forum implementation is again a good one to study, here is a very simple example: 
 */
//function example_reset_userdata($data) {
//    if (!empty($data->reset_example_frogs)) {
//        if (delete_records('example_frogs', 'couresid', $data->courseid) and $showfeedback) {
//            notify(get_string('frogsdeleted', 'example'), 'notifysuccess');
//        }
//    }
//    if (!empty($data->reset_example_newts)) {
//        if (delete_records('example_newts', 'couresid', $data->courseid) and $showfeedback) {
//            notify(get_string('newtsdeleted', 'example'), 'notifysuccess');
//        }
//    }
//}

 
//These functions are used by moodle core during log gather. 
//They distinguish between "read" and "post" log actions.
//The actions you list inside these two functions, must match the ones you listed in the table 
//"log_display" in install.xml 

//function newmodule_get_view_actions()
//function newmodule_get_post_actions() 

//////////////////////////////////////////////////////////////////////////////////////
/// Any other newmodule functions go here.  Each of them must have a name that
/// starts with newmodule_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all functions below to a new "locallib.php" file.

//Some more functions should be added. Among these I want to list:

//These three functions are responsible for the mewmodule reset process during the more general
//course reset process. Please, refer to mod/data/lib.php or to mod/feedback/lib.php, for instance,
//to understand them better.

/**
 * This is called directly by /course/reset.php. It needs to output some form controls to control
 * different options for resetting your module. You should use Form API for create form.
 * The convention is to call settings relating your your module reset_mymodule_something.
 * The forum implementation is a good model:
 */
//function forum_reset_course_form_definition(&$mform) {
//    $mform->addElement('header', 'forumheader', get_string('modulenameplural', 'forum'));

//    $mform->addElement('checkbox', 'reset_forum_all', get_string('resetforumsall','forum'));

//    $mform->addElement('select', 'reset_forum_types', get_string('resetforums', 'forum'), forum_get_forum_types_all(), array('multiple' => 'multiple'));
//    $mform->setAdvanced('reset_forum_types');
//    $mform->disabledIf('reset_forum_types', 'reset_forum_all', 'checked');

//    $mform->addElement('checkbox', 'reset_forum_subscriptions', get_string('resetsubscriptions','forum'));
//    $mform->setAdvanced('reset_forum_subscriptions');

//    $mform->addElement('checkbox', 'reset_forum_track_prefs', get_string('resettrackprefs','forum'));
//    $mform->setAdvanced('reset_forum_track_prefs');
//    $mform->disabledIf('reset_forum_track_prefs', 'reset_forum_all', 'checked');

//    $mform->addElement('checkbox', 'reset_forum_ratings', get_string('deleteallratings'));
//    $mform->disabledIf('reset_forum_ratings', 'reset_forum_all', 'checked');
//}
/**
 * Used for set default values to form's elements displayed by mymodule_reset_course_form_definition.
 * The forum implementation:
 */
//function forum_reset_course_form_defaults($course) {
//    return array('reset_forum_all'=>1, 'reset_forum_subscriptions'=>0, 'reset_forum_track_prefs'=>0, 'reset_forum_ratings'=>1);
//}
