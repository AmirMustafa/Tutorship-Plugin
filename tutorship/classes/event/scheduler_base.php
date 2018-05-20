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
 * Base class for tutorship events.
 *
 * @package    mod_tutorship
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * 
 */

namespace mod_tutorship\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_tutorship abstract base event class.
 *
 * @package    mod_tutorship
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * 
 */
abstract class tutorship_base extends \core\event\base {

    protected $tutorship;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    protected static function base_data(\tutorship_instance $tutorship) {
        return array(
            'context' => $tutorship->get_context(),
            'objectid' => $tutorship->id
        );
    }

    protected function set_tutorship(\tutorship_instance $tutorship) {
        $this->add_record_snapshot('tutorship', $tutorship->data);
        $this->tutorship = $tutorship;
        $this->data['objecttable'] = 'tutorship';
    }

    /**
     * Get tutorship instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \tutorship_instance
     */
    public function get_tutorship() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_tutorship() is intended for event observers only');
        }
        if (!isset($this->tutorship)) {
            debugging('tutorship property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/tutorship/locallib.php');
            $this->tutorship = \tutorship_instance::load_by_coursemodule_id($this->contextinstanceid);
        }
        return $this->tutorship;
    }


    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/tutorship/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'tutorship';
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
