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
$week               = optional_param('week', 1, PARAM_INT);             // current/next week selection from studentview
$timetableid        = optional_param('timetableid', 0, PARAM_INT);      // timetable id reserved from studentview
$t                  = optional_param('t', 0, PARAM_INT);                // tutorship instance ID
$selectedteacher    = optional_param('selectedteacher', 0, PARAM_INT);  // selected teacher from studentview
$action             = optional_param('action', 2, PARAM_INT);           // teacher action (view or edit) from teacherview
$selectedperiod     = optional_param('selectedperiod', 1, PARAM_INT);   // selected period from teacherview
$maxreserves        = optional_param('maxreserves', 50, PARAM_INT);      // max number of reserves from teacherview
$autoconfirm        = optional_param('autoconfirm', 1, PARAM_INT);      // automatic confirmation from teacherview
$notify             = optional_param('notify', 1, PARAM_INT);           // send notifications from teacherview
$noreserves         = optional_param('noreserves', 0, PARAM_INT);       // disable student reserves from teacherview
$slotid             = optional_param('slotid', 0, PARAM_INT);           // teacher slotid from teacherview
$reserveid          = optional_param('reserveid', 0, PARAM_INT);        // reserve id confirmed or cancel from teacherview
$cancell            = optional_param('cancell', 0, PARAM_INT);          // cancell reservation from teacherview

if($DB->delete_records("tutorship_reserves", array("timetableid" => $timetableid, "week" => $week))) {
    // header("location:$CFG->wwwroot/mod/tutorship/view.php?id='".$id."'");
    header("location:$CFG->wwwroot/mod/tutorship/view.php?id=$id");
}


