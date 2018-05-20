<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * 
 */

namespace mod_tutorship\task;

require_once(dirname(dirname(dirname(__FILE__))).'/../../config.php');
require_once(dirname(__FILE__).'/../../locallib.php');

class notify_startclass extends \core\task\scheduled_task {

    public function get_name() {
        return  get_string('notify_startclass', 'tutorship');
    }

    public function execute() 
	{

        global $DB;

        //$date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

		$today = time();
		$currentWeek  = date('W', $today);
    	$notifyTime = (int) get_config('tutorship', "notifybeforestartclass");

         $reservesData = $DB->get_records('tutorship_reserves', array('week' => $currentWeek , 'confirmed' => 1));
		 foreach ($reservesData as $dataItem)
		 {
			 $timeTableID = $dataItem->timetableid;
			 $studentID = $dataItem->studentid;
			 $courseID = $dataItem->courseid;
			 $to = $DB->get_record('user', array('id' => $studentID));

			 $timeslotid = (int) $DB->get_field('tutorship_timetables', 'timeslotid', array('id' => $timeTableID));
			 $teacherid        = $DB->get_field('tutorship_timetables', 'teacherid', array('id' => $timeTableID));
    
			 $timeslot   = $DB->get_record('tutorship_timeslots', array('id' => $timeslotid));
				$year       = date('Y', time());
				$time       = gmdate('H:i', $timeslot->starttime);
				$daynumber  = date('d', tutorship_get_date($timeslot->day, $currentWeek, $year));
				$month      = date('m', tutorship_get_date($timeslot->day, $currentWeek, $year));

				$unixtime = mktime(0, 0, 0, (int) $month, (int) $daynumber, (int) $year);
				$unixtime += $timeslot->starttime;	
				
				$nowUnixTime = strtotime("now");
				$deltaTime = $unixtime - $nowUnixTime ;

				$beforeTime =  $notifyTime * 60;
				$beforeEndTime =  ($notifyTime - 7) * 60;

				if($deltaTime > 0 && $deltaTime > $beforeEndTime &&  $deltaTime < $beforeTime)
				 {
					$subtitle = "Notify Before Start Class";
                    $message            .= get_string('reservationconfirmedtxt', 'tutorship');
                    $message            .= ' <b>'.format_string(fullname($to)).'</b>.<br>';
                    $message            .= get_string('reservationdetails', 'tutorship');
                    $message            .= tutorship_get_reserve_date($timetableid, $studentID);

					$teacherInfo = $DB->get_record('user', array('id' => $teacherid));
					if (! email_to_user ($teacherInfo, $USER, $autoconfirmsubject, null, $message)) {
                     //   print_error('erremail', 'tutorship');
                    }

					if (! email_to_user ($to, $USER, $autoconfirmsubject, null, $message)) {
                      //  print_error('erremail', 'tutorship');
                    }
				 }
		 }
    }

}