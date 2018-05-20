<?php

/**
 * Scheduled Notify Start Class tasks in the tutorship module
 *
 * @package    mod_tutorship
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * 
 */

$tasks = array(
            array(
                'classname' => 'mod_tutorship\task\notify_startclass',
                'minute' => '*/5',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
            )
);