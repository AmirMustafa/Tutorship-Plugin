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
 * The mod_tutorship edit_confirmation event.
 *
 * @package    mod_tutorship
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * 
 */

namespace mod_tutorship\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_tutorship edit_noreserves event class.
 * @package    mod_tutorship
 * @since      Moodle 3.2.1
 * @copyright  2017 <moodledman@gmail.com>
 * 
 */
class edit_confirmation extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'tutorship_configs';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventedit_confirmation', 'mod_tutorship');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $name = $this->other['tutorshipname'];
        return "The user with id '$this->userid' updated the confirmation value at '$this->objectid' in the tutorship activity " .
            "with course module id '$this->contextinstanceid'. named : $name";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/tutorship/view.php', array('id' => $this->contextinstanceid));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'tutorship_configs', 'restore' => 'tutorship_configs');
    }
}