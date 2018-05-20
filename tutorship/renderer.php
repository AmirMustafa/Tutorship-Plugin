<?php

/**
 * This file contains a renderer for the Tutorship module
 *
 * @package    mod_tutorship
 * @copyright  2017 HyongHyoMing and others (see README.txt)
 * 
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the tutorship module.
 *
 */
class mod_tutorship_renderer extends plugin_renderer_base {


    /**
     * Format a date in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable date
     */
    public static function userdate($date) {
        if ($date == 0) {
            return '';
        } else {
            return userdate($date, get_string('strftimedaydate'));
        }
    }

    /**
     * Format a time in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable time
     */
    public static function usertime($date) {
        if ($date == 0) {
            return '';
        } else {
            $timeformat = get_user_preferences('calendar_timeformat'); // Get user config.
            if (empty($timeformat)) {
                $timeformat = get_config(null, 'calendar_site_timeformat'); // Get calendar config if above not exist.
            }
            if (empty($timeformat)) {
                $timeformat = get_string('strftimetime'); // Get locale default format if both of the above do not exist.
            }
            return userdate($date, $timeformat);
        }
    }

    /**
     * Format a slot date and time, for use as a parameter in a language string.
     *
     * @param int $slotdate
     *            a timestamp, start time of the slot
     * @param int $duration
     *            length of the slot in minutes
     * @return stdClass date and time formatted for usage in language strings
     */
    public static function slotdatetime($slotdate, $duration) {
        $shortformat = get_string('strftimedatetimeshort');

        $a = new stdClass();
        $a->date = self::userdate($slotdate);
        $a->starttime = self::usertime($slotdate);
        $a->shortdatetime = userdate($slotdate, $shortformat);
        $a->endtime = self::usertime($slotdate + $duration * MINSECS);
        $a->duration = $duration;

        return $a;
    }

    protected $scalecache = array();

    public function get_scale_levels($scaleid) {
        global $DB;

        if (!array_key_exists($scaleid, $this->scalecache)) {
            $this->scalecache[$scaleid] = array();
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $levels = explode(',', $scale->scale);
                foreach ($levels as $levelid => $value) {
                    $this->scalecache[$scaleid][$levelid + 1] = $value;
                }
            }
        }
        return $this->scalecache[$scaleid];
    }


    /**
     * Formats a grade in a specific scheduler for display
     * @param mixed $subject either a scheduler instance or a scale id
     * @param string $grade the grade to be displayed
     * @param boolean $short formats the grade in short form (result empty if grading is
     * not used, or no grade is available; parantheses are put around the grade if it is present)
     * @return string the formatted grade
     */
    public function format_grade($subject, $grade, $short = false) {
        if ($subject instanceof scheduler_instance) {
            $scaleid = $subject->scale;
        } else {
            $scaleid = (int) $subject;
        }

        $result = '';
        if ($scaleid == 0 || is_null($grade) ) {
            // Scheduler doesn't allow grading, or no grade entered.
            if (!$short) {
                $result = get_string('nograde');
            }
        } else {
            $grade = (int) $grade;
            if ($scaleid > 0) {
                // Numeric grade.
                $result .= $grade;
                if (strlen($grade) > 0) {
                    $result .= '/' . $scaleid;
                }
            } else {
                // Grade on scale.
                if ($grade > 0) {
                    $levels = $this->get_scale_levels(-$scaleid);
                    if (array_key_exists($grade, $levels)) {
                        $result .= $levels[$grade];
                    }
                }
            }
            if ($short && (strlen($result) > 0)) {
                $result = '('.$result.')';
            }
        }
        return $result;
    }

    /**
     * A utility function for producing grading lists (for use in formslib)
     *
     * Note that the selection list will contain a "nothing selected" option
     * with key -1 which will be displayed as "No grade".
     *
     * @param reference $tutorship
     * @return array the choices to be displayed in a grade chooser
     */
    public function grading_choices($tutorship) {
        if ($tutorship->scale > 0) {
            $scalegrades = array();
            for ($i = 0; $i <= $tutorship->scale; $i++) {
                $scalegrades[$i] = $i;
            }
        } else {
            $scaleid = - ($tutorship->scale);
            $scalegrades = $this->get_scale_levels($scaleid);
        }
        $scalegrades = array(-1 => get_string('nograde')) + $scalegrades;
        return $scalegrades;
    }

    public function format_grading_strategy($strategy) {
        if ($strategy == SCHEDULER_MAX_GRADE) {
            return get_string('maxgrade', 'scheduler');
        } else {
            return get_string('meangrade', 'scheduler');
        }
    }

    public function format_notes($content, $format, $context, $area, $itemid) {
        $text = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_scheduler', $area, $itemid);
        return format_text($text, $format);
    }

    public function format_appointment_notes(scheduler_instance $tutorship, $data, $idfield = 'id') {
        $note = '';
        $id = $data->{$idfield};
        if (isset($data->appointmentnote) && $tutorship->uses_appointmentnotes()) {
            $note .= $this->format_notes($data->appointmentnote, $data->appointmentnoteformat, $tutorship->get_context(),
                                         'appointmentnote', $id);
        }
        if (isset($data->teachernote) && $tutorship->uses_teachernotes()) {
            $note .= $this->format_notes($data->teachernote, $data->teachernoteformat, $tutorship->get_context(),
                                         'teachernote', $id);
        }
        return $note;
    }

    public function user_profile_link(scheduler_instance $tutorship, stdClass $user) {
        $profileurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $tutorship->course));
        return html_writer::link($profileurl, fullname($user));
    }

    public function appointment_link($tutorship, $user, $appointmentid) {
        $paras = array(
                        'what' => 'viewstudent',
                        'id' => $tutorship->cmid,
                        'appointmentid' => $appointmentid
        );
        $url = new moodle_url('/mod/scheduler/view.php', $paras);
        return html_writer::link($url, fullname($user));
    }

    public function mod_intro($tutorship) {
        $o = $this->heading(format_string($tutorship->name), 2);

        if (trim(strip_tags($tutorship->intro))) {
            $o .= $this->box_start('mod_introbox');
            $o .= format_module_intro('scheduler', $tutorship->get_data(), $tutorship->cmid);
            $o .= $this->box_end();
        }
        return $o;
    }

    private function teacherview_tab(moodle_url $baseurl, $namekey, $what, $subpage = '', $nameargs = null) {
        $taburl = new moodle_url($baseurl, array('what' => $what, 'subpage' => $subpage));
        $tabname =$namekey ;//get_string($namekey, 'scheduler', $nameargs);
        $id = ($subpage != '') ? $subpage : $what;
        $tab = new tabobject($id, $taburl, $tabname);
        return $tab;
    }

    public function teacherview_tabs($tutorship ,moodle_url $baseurl, $selected, $inactive = null) {

        /*$statstab = $this->teacherview_tab($baseurl, 'statistics', 'viewstatistics', 'overall');
        $statstab->subtree = array(
                        $this->teacherview_tab($baseurl, 'overall', 'viewstatistics', 'overall'),
                        $this->teacherview_tab($baseurl, 'studentbreakdown', 'viewstatistics', 'studentbreakdown'),
                        $this->teacherview_tab($baseurl, 'staffbreakdown', 'viewstatistics', 'staffbreakdown',
                                               $tutorship->get_teacher_name()),
                        $this->teacherview_tab($baseurl, 'lengthbreakdown', 'viewstatistics', 'lengthbreakdown'),
                        $this->teacherview_tab($baseurl, 'groupbreakdown', 'viewstatistics', 'groupbreakdown')
        );*/

		$adminPermission = false;
		if (has_capability('mod/tutorship:teachermanage', $tutorship)) {
				$adminPermission = true;
		}

        /*$level1 = array(
              $this->teacherview_tab($baseurl, 'Schedule', 'view', 'schedule')
        );*/
		$level1 = array();

		if($adminPermission != true)
		{
			$level1[] =  $this->teacherview_tab($baseurl, 'Schedule', 'view', 'schedule');
		}

		if (has_capability('mod/tutorship:teachermanage', $tutorship))
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Mass Pay', 'view', 'teachermanagement');
		}
		if (has_capability('mod/tutorship:leavemanage', $tutorship))
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Leave Management', 'view', 'leavemanagement');
		}
		

		if($adminPermission != true)
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Teacher Payment', 'view', 'teacherpayment');
		}

		if($adminPermission != true)
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Upcoming', 'view', 'upcoming');
		}

		if($adminPermission != true)
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Contract', 'view', 'contract');
		}

		if($adminPermission != true)
		{
			$level1[] = $this->teacherview_tab($baseurl, 'FAQ', 'view', 'faq');
		}

		if ($adminPermission == true)
		{
			$level1[] = $this->teacherview_tab($baseurl, 'Teacher Management', 'view', 'tm');
		}
		
        return $this->tabtree($level1, $selected, $inactive);
    }


    public function studentview_tabs($tutorship ,moodle_url $baseurl, $selected, $inactive = null) {

		$level1 = array();
		$level1[] =  $this->teacherview_tab($baseurl, 'Schedule', 'view', 'schedule');

		$level1[] = $this->teacherview_tab($baseurl, 'Upcoming', 'view', 'upcoming');
		$level1[] = $this->teacherview_tab($baseurl, 'History', 'view', 'history');


        return $this->tabtree($level1, $selected, $inactive);
    }

    public function action_message($message, $type = 'success') {
        $classes = 'actionmessage '.$type;
        echo html_writer::div($message, $classes);
    }

    /**
     * Rendering a table of slots
     *
     * @param scheduler_slot_table $slottable the table to rended
     * @return string the HTML output
     */
    public function render_scheduler_slot_table(scheduler_slot_table $slottable) {
        $table = new html_table();

        if ($slottable->showslot) {
            $table->head  = array(get_string('date', 'scheduler'));
            $table->align = array('left');
        }
        if ($slottable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        if ($slottable->showattended) {
            $table->head[] = get_string('seen', 'scheduler');
            $table->align[] = 'center';
        }
        if ($slottable->showslot) {
            $table->head[]  = $slottable->scheduler->get_teacher_name();
            $table->align[] = 'left';
        }
        if ($slottable->showslot && $slottable->showlocation) {
            $table->head[]  = get_string('location', 'scheduler');
            $table->align[] = 'left';
        }

        $table->head[] = get_string('comments', 'scheduler');
        $table->align[] = 'left';

        if ($slottable->showgrades) {
            $table->head[] = get_string('grade', 'scheduler');
            $table->align[] = 'left';
        } else if ($slottable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'scheduler');
            $table->align[] = 'left';
        }
        if ($slottable->showactions) {
            $table->head[] = '';
            $table->align[] = 'left';
        }

        $table->data = array();

        foreach ($slottable->slots as $slot) {
            $rowdata = array();

            $studenturl = new moodle_url($slottable->actionurl, array('appointmentid' => $slot->appointmentid));

            $timedata = $this->userdate($slot->starttime);
            if ($slottable->showeditlink) {
                $timedata = $this->action_link($studenturl, $timedata);
            }
            $timedata = html_writer::div($timedata, 'datelabel');

            $starttime = $this->usertime($slot->starttime);
            $endtime   = $this->usertime($slot->endtime);
            $timedata .= html_writer::div("{$starttime} &ndash; {$endtime}", 'timelabel');

            if ($slottable->showslot) {
                $rowdata[] = $timedata;
            }

            if ($slottable->showstudent) {
                $name = fullname($slot->student);
                if ($slottable->showeditlink) {
                    $name = $this->action_link($studenturl, $name);
                }
                $rowdata[] = $name;
            }

            if ($slottable->showattended) {
                $iconid = $slot->attended ? 'ticked' : 'unticked';
                $iconhelp = $slot->attended ? 'seen' : 'notseen';
                $attendedpix = $this->pix_icon($iconid, get_string($iconhelp, 'scheduler'), 'mod_scheduler');
                $rowdata[] = $attendedpix;
            }

            if ($slottable->showslot) {
                $rowdata[] = $this->user_profile_link($slottable->scheduler, $slot->teacher);
            }

            if ($slottable->showslot && $slottable->showlocation) {
                $rowdata[] = format_string($slot->location);
            }

            $notes = '';
            if ($slottable->showslot && isset($slot->slotnote)) {
                $notes .= $this->format_notes($slot->slotnote, $slot->slotnoteformat,
                                              $slottable->scheduler->get_context(), 'slotnote', $slot->slotid);
            }
            $notes .= $this->format_appointment_notes($slottable->scheduler, $slot, 'appointmentid');
            $rowdata[] = $notes;

            if ($slottable->showgrades || $slottable->hasotherstudents) {
                $gradedata = '';
                if ($slot->otherstudents) {
                    $gradedata = $this->render($slot->otherstudents);
                } else if ($slottable->showgrades) {
                    $gradedata = $this->format_grade($slottable->scheduler, $slot->grade);
                }
                $rowdata[] = $gradedata;
            }
            if ($slottable->showactions) {
                $actions = '';
                if ($slot->cancancel) {
                    $buttonurl = new moodle_url($slottable->actionurl,
                                     array('what' => 'cancelbooking', 'slotid' => $slot->slotid));
                    $button = new single_button($buttonurl, get_string('cancelbooking', 'scheduler'));
                    $actions .= $this->render($button);
                }
                $rowdata[] = $actions;
            }
            $table->data[] = $rowdata;
        }

        return html_writer::table($table);
    }

    /**
     * Rendering a list of student, to be displayed within a larger table
     *
     * @param scheduler_slot_table $slottable the table to rended
     * @return string the HTML output
     */
    public function render_scheduler_student_list(scheduler_student_list $studentlist) {

        $o = '';

        $toggleid = html_writer::random_id('toggle');

        if ($studentlist->expandable && count($studentlist->students) > 0) {
            $this->page->requires->yui_module('moodle-mod_scheduler-studentlist',
                            'M.mod_scheduler.studentlist.init',
                            array($toggleid, (boolean) $studentlist->expanded) );
            $imgclass = 'studentlist-togglebutton';
            $alttext = get_string('showparticipants', 'scheduler');
            $o .= $this->output->pix_icon('t/switch', $alttext, 'moodle',
                            array('id' => $toggleid, 'class' => $imgclass));
        }

        $divprops = array('id' => 'list'.$toggleid);
        $o .= html_writer::start_div('studentlist', $divprops);
        if (count($studentlist->students) > 0) {
            $editable = $studentlist->actionurl && $studentlist->editable;
            if ($editable) {
                $o .= html_writer::start_tag('form', array('action' => $studentlist->actionurl,
                                'method' => 'post', 'class' => 'studentselectform'));
            }

            foreach ($studentlist->students as $student) {
                $class = 'otherstudent';
                $checkbox = '';
                if ($studentlist->checkboxname) {
                    if ($editable) {
                        $checkbox = html_writer::checkbox($studentlist->checkboxname, $student->entryid, $student->checked, '',
                                        array('class' => 'studentselect'));
                    } else {
                        $img = $student->checked ? 'ticked' : 'unticked';
                        $checkbox = $this->render(new pix_icon($img, '', 'scheduler', array('class' => 'statictickbox')));
                    }
                }
                if ($studentlist->linkappointment) {
                    $name = $this->appointment_link($studentlist->scheduler, $student->user, $student->entryid);
                } else {
                    $name = fullname($student->user);
                }
                if ($student->highlight) {
                    $class .= ' highlight';
                }
                $picture = $this->user_picture($student->user, array('courseid' => $studentlist->scheduler->courseid));
                $grade = '';
                if ($studentlist->showgrades && $student->grade) {
                    $grade = $this->format_grade($studentlist->scheduler, $student->grade, true);
                }
                $o .= html_writer::div($checkbox.$picture.' '.$name.' '.$grade, $class);
            }

            if ($editable) {
                $o .= html_writer::empty_tag('input', array(
                                'type' => 'submit',
                                'class' => 'studentselectsubmit',
                                'value' => $studentlist->buttontext
                ));
                $o .= html_writer::end_tag('form');
            }
        }
        $o .= html_writer::end_div();

        return $o;
    }

    public function render_scheduler_slot_booker(scheduler_slot_booker $booker) {

        $table = new html_table();
        $table->head  = array( get_string('date', 'scheduler'), get_string('start', 'scheduler'),
                        get_string('end', 'scheduler'), get_string('location', 'scheduler'),
                        get_string('comments', 'scheduler'), s($booker->scheduler->get_teacher_name()),
                        get_string('groupsession', 'scheduler'), '');
        $table->align = array ('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');
        $table->id = 'slotbookertable';
        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';
        $canappoint = false;

        foreach ($booker->slots as $slot) {

            $rowdata = array();

            $startdate = $this->userdate($slot->starttime);
            $starttime = $this->usertime($slot->starttime);
            $endtime = $this->usertime($slot->endtime);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $rowdata[] = $startdatestr;
            $rowdata[] = $starttimestr;
            $rowdata[] = $endtimestr;

            $rowdata[] = format_string($slot->location);

            $rowdata[] = $this->format_notes($slot->notes, $slot->notesformat, $booker->scheduler->get_context(),
                                             'slotnote', $slot->slotid);

            $rowdata[] = $this->user_profile_link($booker->scheduler, $slot->teacher);

            $groupinfo = $slot->bookedbyme ? get_string('complete', 'scheduler') : $slot->groupinfo;
            if ($slot->otherstudents) {
                $groupinfo .= $this->render($slot->otherstudents);
            }

            $rowdata[] = $groupinfo;

            if ($slot->canbook) {
                $bookurl = new moodle_url($booker->actionurl, array('what' => 'bookslot', 'slotid' => $slot->slotid));
                $button = new single_button($bookurl, get_string('bookslot', 'scheduler'));
                $rowdata[] = $this->render($button);
            } else {
                $rowdata[] = '';
            }

            $table->data[] = $rowdata;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }

        return html_writer::table($table);
    }

    public function render_scheduler_command_bar(scheduler_command_bar $commandbar) {
        $o = '';
        foreach ($commandbar->linkactions as $id => $action) {
            $this->add_action_handler($action, $id);
        }
        $o .= html_writer::start_div('commandbar');
        if ($commandbar->title) {
            $o .= html_writer::span($commandbar->title, 'title');
        }
        foreach ($commandbar->menus as $m) {
            $o .= $this->render($m);
        }
        $o .= html_writer::end_div();
        return $o;
    }

    public function render_scheduler_slot_manager(scheduler_slot_manager $slotman) {

        $this->page->requires->yui_module('moodle-mod_scheduler-saveseen',
                        'M.mod_scheduler.saveseen.init', array($slotman->scheduler->cmid) );

        $o = '';

        $table = new html_table();
        $table->head  = array('', get_string('date', 'scheduler'), get_string('start', 'scheduler'),
                        get_string('end', 'scheduler'), get_string('students', 'scheduler') );
        $table->align = array ('center', 'left', 'left', 'left', 'left');
        if ($slotman->showteacher) {
            $table->head[] = s($slotman->scheduler->get_teacher_name());
            $table->align[] = 'left';
        }
        $table->head[] = get_string('action', 'scheduler');
        $table->align[] = 'center';

        $table->id = 'slotmanager';
        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';

        foreach ($slotman->slots as $slot) {

            $rowdata = array();

            $selectbox = html_writer::checkbox('selectedslot[]', $slot->slotid, false, '', array('class' => 'slotselect'));
            $rowdata[] = $slot->editable ? $selectbox : '';

            $startdate = $this->userdate($slot->starttime);
            $starttime = $this->usertime($slot->starttime);
            $endtime = $this->usertime($slot->endtime);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $rowdata[] = $startdatestr;
            $rowdata[] = $starttimestr;
            $rowdata[] = $endtimestr;

            $rowdata[] = $this->render($slot->students);

            if ($slotman->showteacher) {
                $rowdata[] = $this->user_profile_link($slotman->scheduler, $slot->teacher);
            }

            $actions = '';
            if ($slot->editable) {
                $url = new moodle_url($slotman->actionurl, array('what' => 'deleteslot', 'slotid' => $slot->slotid));
                $confirmdelete = new confirm_action(get_string('confirmdelete-one', 'scheduler'));
                $actions .= $this->action_icon($url, new pix_icon('t/delete', get_string('delete')), $confirmdelete);

                $url = new moodle_url($slotman->actionurl, array('what' => 'updateslot', 'slotid' => $slot->slotid));
                $actions .= $this->action_icon($url, new pix_icon('t/edit', get_string('edit')));
            }

            if ($slot->isattended || $slot->isappointed > 1) {
                $groupicon = 'i/groupevent';
            } else if ($slot->exclusivity == 1) {
                $groupicon = 't/groupn';
            } else {
                $groupicon = 't/groupv';
            }
            $groupalt = ''; $groupact = null;
            if ($slot->isattended) {
                $groupalt = 'attended';
            } else if ($slot->isappointed > 1) {
                $groupalt = 'isnonexclusive';
            } else if ($slot->editable) {
                if ($slot->exclusivity == 1) {
                    $groupact = array('what' => 'allowgroup', 'slotid' => $slot->slotid);
                    $groupalt = 'allowgroup';
                } else {
                    $groupact = array('what' => 'forbidgroup', 'slotid' => $slot->slotid);
                    $groupalt = 'forbidgroup';
                }
            } else {
                if ($slot->exclusivity == 1) {
                    $groupalt = 'allowgroup';
                } else {
                    $groupalt = 'forbidgroup';
                }
            }
            if ($groupact) {
                $url = new moodle_url($slotman->actionurl, $groupact);
                $actions .= $this->action_icon($url, new pix_icon($groupicon, get_string($groupalt, 'scheduler')));
            } else {
                $actions .= $this->pix_icon($groupicon, get_string($groupalt, 'scheduler'));
            }

            if ($slot->editable && $slot->isappointed) {
                $url = new moodle_url($slotman->actionurl, array('what' => 'revokeall', 'slotid' => $slot->slotid));
                $actions .= $this->action_icon($url, new pix_icon('s/no', get_string('revoke', 'scheduler')));
            }

            if ($slot->exclusivity > 1) {
                $actions .= ' ('.$slot->exclusivity.')';
            }
            $rowdata[] = $actions;

            $table->data[] = $rowdata;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }
        $o .= html_writer::table($table);

        return $o;
    }

    public function render_scheduler_scheduling_list(scheduler_scheduling_list $list) {

        $mtable = new html_table();

        $mtable->id = $list->id;
        $mtable->head  = array ('', get_string('name'));
        $mtable->align = array ('center', 'left');
        foreach ($list->extraheaders as $field) {
            $mtable->head[] = $field;
            $mtable->align[] = 'left';
        }
        $mtable->head[] = get_string('action', 'scheduler');
        $mtable->align[] = 'center';

        $mtable->data = array();
        foreach ($list->lines as $line) {
            $data = array($line->pix, $line->name);
            foreach ($line->extrafields as $field) {
                $data[] = $field;
            }
            $actions = '';
            if ($line->actions) {
                $menu = new action_menu($line->actions);
                $menu->actiontext = get_string('schedule', 'scheduler');
                $actions = $this->render($menu);
            }
            $data[] = $actions;
            $mtable->data[] = $data;
        }
        return html_writer::table($mtable);
    }

    public function render_scheduler_totalgrade_info(scheduler_totalgrade_info $gradeinfo) {
        $items = array();

        if ($gradeinfo->showtotalgrade) {
            $items[] = array('gradingstrategy', $this->format_grading_strategy($gradeinfo->scheduler->gradingstrategy));
            $items[] = array('totalgrade', $this->format_grade($gradeinfo->scheduler, $gradeinfo->totalgrade));
        }

        if (!is_null($gradeinfo->gbgrade)) {
            $gbgradeinfo = $this->format_grade($gradeinfo->scheduler, $gradeinfo->gbgrade->grade);
            $attributes = array();
            if ($gradeinfo->gbgrade->hidden) {
                $attributes[] = get_string('hidden', 'grades');
            }
            if ($gradeinfo->gbgrade->locked) {
                $attributes[] = get_string('locked', 'grades');
            }
            if ($gradeinfo->gbgrade->overridden) {
                $attributes[] = get_string('overridden', 'grades');
            }
            if (count($attributes) > 0) {
                $gbgradeinfo .= ' ('.implode(', ', $attributes) .')';
            }
            $items[] = array('gradeingradebook', $gbgradeinfo);
        }

        $o = html_writer::start_div('totalgrade');
        $o .= html_writer::start_tag('dl', array('class' => 'totalgrade'));
        foreach ($items as $item) {
            $o .= html_writer::tag('dt', get_string($item[0], 'scheduler'));
            $o .= html_writer::tag('dd', $item[1]);
        }
        $o .= html_writer::end_tag('dl');
        $o .= html_writer::end_div('totalgrade');
        return $o;
    }

    public function render_scheduler_conflict_list(scheduler_conflict_list $cl) {

        $o = html_writer::start_tag('ul');

        foreach ($cl->conflicts as $conflict) {
            $a = new stdClass();
            $a->datetime = userdate($conflict->starttime);
            $a->duration = $conflict->duration;
            if ($conflict->isself) {
                $entry = get_string('conflictlocal', 'scheduler', $a);
            } else {
                $a->courseshortname = $conflict->courseshortname;
                $a->coursefullname = $conflict->coursefullname;
                $a->schedulername = format_string($conflict->schedulername);
                $entry = get_string('conflictremote', 'scheduler', $a);
            }
            $o .= html_writer::tag('li', $entry);
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }





	public function header() {
		// designed to be empty
	}
	
	public function footer() {
		// designed to be empty
	}
	
	private function _header(tutorshiproom $openmeetings) {
		global $cm, $course, $CFG, $USER, $PAGE, $OUTPUT;
		$title = $course->shortname . ": " . $openmeetings->om['name'];
		$PAGE->set_title($title);
		$PAGE->set_cacheable(false);
		$PAGE->set_focuscontrol("");
		$PAGE->set_url('/mod/tutorship/view.php', array(
				'id' => $cm->id
		));
		if ($openmeetings->om['whole_window'] > 0) {

			$out .= "<html" . $this->output->htmlattributes() . ">";
			$out .= html_writer::start_tag("head");
			$out .= html_writer::empty_tag("meta", array(
					"http-equiv" => "pragma",
					"content" => "no-cache")
				);
			$out .= html_writer::empty_tag("meta", array(
					"http-equiv" => "expires",
					"content" => "-1")
				);
			$out .= html_writer::empty_tag("meta", array(
					"http-equiv" => "cache-control",
					"content" => "no-cache")
				);
			$out .= html_writer::tag("title", $title);
			$out .= $this->output->standard_head_html();
			$out .= '<style text/css>body.noMargin {	margin: 0;	padding: 0;	border: 0;}
			iframe.tutorshiproom {	border: 0;	width: 100% !important;	height: 640px;}
			iframe.wholeWindow {	height: 100%;}
			</style>';
			$out .= html_writer::end_tag("head");
			$out .= html_writer::start_tag("body", array("class" => "noMargin"));
		}
		else {

			// / Print the page header
			if ($course->category) {
				$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
			} else {
				$navigation = '';
			}
			
			$stropenmeetingss = get_string('modulenameplural', 'tutorship');//"OpenMeetings";//get_string("modulenameplural", "openmeetings");

			$PAGE->set_heading($course->fullname); // Required
											

			$PAGE->navbar->add($stropenmeetingss, null, null, navigation_node::TYPE_CUSTOM, new moodle_url($CFG->wwwroot . '/user/index.php?id=' . $courseid));
					

			$PAGE->navbar->add("OpenMeeting");
			$out .= $this->output->header();		
			//print_r("Here");die();
		}
			
		return $out;
	}

	private function _footer(tutorshiproom $openmeetings) {
		if ($openmeetings->om['whole_window'] > 0) {
			$out .= html_writer::end_tag("body");
			$out .= html_writer::end_tag("html");
		} else {
			$out .= $this->output->footer();
		}
		return $out;
	}
	
	public function render_tutorshiproom(tutorshiproom $openmeetings) {
		global $cm, $course, $CFG, $USER, $PAGE;
		
		$out .= $this->_header($openmeetings);
		$context = context_module::instance($cm->id);
		//print_R("EE");die();
		$becomemoderator = false;
		if ($openmeetings->om['teacher'] != 0) 
		{
			// Teacher Permission
			$becomemoderator = true;
		}
		$gateway = new TOmGateway(getTOmConfig());
		if ($gateway->login()) {
			$allowRecording = $openmeetings->om['allow_recording'] != 2;
			if ($openmeetings->om['is_moderated_room'] == 3) {
				$becomemoderator = true;
			}
			// Simulate the User automatically
			if ($openmeetings->om['type'] != 'recording') {
				$hash = getTOmHash($gateway, array("roomId" => $openmeetings->om['room_id'], "moderator" => $becomemoderator, "allowRecording" => $allowRecording));
			} else {
				$hash = getTOmHash($gateway, array("recordingId" => $openmeetings->om['room_recording_id']));
			}
			
			if ($hash != "") {
				$url = $gateway->getUrl() . "/hash?&secure=" . $hash . "&language=" . $openmeetings->om['language'];
				$height = $openmeetings->om['whole_window'] > 0 ? "100%" : "640px";
				$out .= html_writer::empty_tag("iframe", array(
						"src" => $url,
						"class" => "tutorshiproom" . ($openmeetings->om['whole_window'] > 0 ? " wholeWindow" : "")
				));
			}
		} else {
			echo "Could not login User to OpenMeetings, check your OpenMeetings Module Configuration";
			exit();
		}
		
		$out .= $this->_footer($openmeetings);
		return $out;
	}

}




class tutorshiproom implements renderable {
	var $om;
	
	public function __construct(stdclass $openmeetings) {
		$this->om = $openmeetings;
	}
}
/*
class mod_tutorshiproom_renderer extends plugin_renderer_base {
	
}
*/