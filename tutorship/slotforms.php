<?php

/**
 * Slot-related forms of the tutorship module
 * (using Moodle formslib)
 *
 * @package    mod_tutorship
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * 
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Base class for slot-related forms
 */
abstract class tutorship_slotform_base extends moodleform {

    //protected $tutorship;
    protected $cm;
    protected $context;
    protected $usergroups;
    protected $hasduration = false;
    protected $noteoptions;

    public function __construct($action, $context, $cm,  $customdata=null) {
       // $this->tutorship = $tutorship;
        $this->cm = $cm;
        $this->context = $context;
   //     $this->usergroups = $usergroups;
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $this->context, 'subdirs' => false);

        parent::__construct($action, $customdata);
    }

    protected function add_base_fields() {

        global $CFG, $USER;

        $mform = $this->_form;

        // Exclusivity.
        $exclgroup = array();

        $exclgroup[] = $mform->createElement('text', 'exclusivity', '', array('size' => '10'));
        $mform->setType('exclusivity', PARAM_INTEGER);
        $mform->setDefault('exclusivity', 1);
	 }

    protected function add_minutes_field($name, $label, $defaultval, $minuteslabel = 'minutes') {

    }

    protected function add_duration_field($minuteslabel = 'minutes') {
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}

class tutorship_leave_form extends tutorship_slotform_base {

    protected $slotid;

    protected function definition() {

        global $DB, $output;

        $mform = $this->_form;

		$timelist = array();
		for($i = 0 ; $i < 24 ; $i++)
		{
			$hour  = sprintf("%02d", $i);
			$value = $i * 3600 ;
			$fvalue = array($value => $hour.":00");
				$timelist[$value] = $hour.":00";

			$value = $value + 30 * 60;
			$evalue = array();
				$timelist[$value] = $hour.":30";
		}


		$reasonList = tutorship_get_leave_reason_list();

		$startYear  = date("Y");

        $this->slotid = 0;
        if (isset($this->_customdata['slotid'])) {
            $this->slotid = $this->_customdata['slotid'];
        }
        $timeoptions = array( 'startyear' =>$startYear,  'stopyear'  => $startYear);

        // Start date/time of the slot.
        $mform->addElement('date_selector', 'startdate', "Start Date", $timeoptions);
        $mform->setDefault('startdate', time());
        //$mform->addHelpButton('startdate', 'choosingslotstart', 'tutorship');


        $mform->addElement('select', 'starttime', "Start Time" , $timelist );

        // Display slot from this date.
        $mform->addElement('date_selector', 'enddate', "End Date", $timeoptions);
        $mform->setDefault('enddate', time());


		$mform->addElement('select', 'endtime', "End Time" ,$timelist  );
		$mform->addElement('select', 'reason', "Reason" ,$reasonList);

	//$this->add_button();

	$buttonarray=array();
		$buttonarray[] = $mform->createElement('submit', 'submitbutton', "Submit");
	//	$buttonarray[] = $mform->createElement('cancel');
	$mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
//  $mform->addElement('button', 'endtime', "End Time" ,$timelist);

    }

    public function validation($data, $files) {
        global $output;

        $errors = parent::validation($data, $files);
		if($data['startdate'] > $data['enddate'])
		{
			$errors['enddate'] = "The End date must large than more Start Date";
		}

        return $errors;
    }

    public function save_slot($slotid, $data) {

        $context = $this->tutorship->get_context();

        if ($slotid) {
            $slot = tutorship_slot::load_by_id($slotid, $this->tutorship);
        } else {
            $slot = new tutorship_slot($this->tutorship);
        }

        // Set data fields from input form.
        $slot->starttime = $data->starttime;
        $slot->duration = $data->duration;
        $slot->exclusivity = $data->exclusivityenable ? $data->exclusivity : 0;
        $slot->teacherid = $data->teacherid;
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->hideuntil = $data->hideuntil;
        $slot->emaildate = $data->emaildate;
        $slot->timemodified = time();

        if (!$slotid) {
            $slot->save(); // Make sure that a new slot has a slot id before proceeding.
        }

        $editor = $data->notes_editor;
        $slot->notes = file_save_draft_area_files($editor['itemid'], $context->id, 'mod_tutorship', 'slotnote', $slotid,
                $this->noteoptions, $editor['text']);
        $slot->notesformat = $editor['format'];

        $currentapps = $slot->get_appointments();
        $processedstuds = array();
        for ($i = 0; $i < $data->appointment_repeats; $i++) {
            if ($data->studentid[$i] > 0) {
                $app = null;
                foreach ($currentapps as $currentapp) {
                    if ($currentapp->studentid == $data->studentid[$i]) {
                        $app = $currentapp;
                        $processedstuds[] = $currentapp->studentid;
                    }
                }
                if ($app == null) {
                    $app = $slot->create_appointment();
                    $app->studentid = $data->studentid[$i];
                    $app->save();
                }
                $app->attended = isset($data->attended[$i]);

                if (isset($data->grade)) {
                    $selgrade = $data->grade[$i];
                    $app->grade = ($selgrade >= 0) ? $selgrade : null;
                }

                if ($this->tutorship->uses_appointmentnotes()) {
                    $editor = $data->appointmentnote_editor[$i];
                    $app->appointmentnote = file_save_draft_area_files($editor['itemid'], $context->id,
                            'mod_tutorship', 'appointmentnote', $app->id,
                            $this->noteoptions, $editor['text']);
                    $app->appointmentnoteformat = $editor['format'];
                }
                if ($this->tutorship->uses_teachernotes()) {
                    $editor = $data->teachernote_editor[$i];
                    $app->teachernote = file_save_draft_area_files($editor['itemid'], $context->id,
                            'mod_tutorship', 'teachernote', $app->id,
                            $this->noteoptions, $editor['text']);
                    $app->teachernoteformat = $editor['format'];
                }
            }
        }
        foreach ($currentapps as $currentapp) {
            if (!in_array($currentapp->studentid, $processedstuds)) {
                $slot->remove_appointment($currentapp);
            }
        }

        $slot->save();

        $slot = $this->tutorship->get_slot($slot->id);

        return $slot;
    }
}
