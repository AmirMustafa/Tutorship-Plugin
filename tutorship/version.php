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
 * Defines the version of tutorship.
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die();
			                     // If version == 0 then module will not be installed
$plugin->version  = 2014041508;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2010100900;  // Requires this Moodle version
$plugin->component = 'mod_tutorship';        // Full name of the plugin (used for diagnostics)
$plugin->cron     = 3600;        // Period for cron to check this module (secs)
