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
 * The mod_tutorship course module viewed event.
 *
 * @package    mod_tutorship
 * @copyright  2017 <moodledman@gmail.com>
 * 
 */

namespace mod_tutorship\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_url course module viewed event class.
 *
 * @package    mod_tutorship
 * @since      Moodle 3.2.1
 * @copyright  2017 <moodledman@gmail.com>
 * 
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'tutorship';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_objectid_mapping() {
        return array('db' => 'tutorship', 'restore' => 'tutorship');
    }
}
