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
 * CUL Activity Stream event handlers
 *
 * @package    local
 * @subpackage culactivity_stream
 * @copyright  2013 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */


/**
 * Function for handling mod_created events
 * 
 * @param stdClass $eventdata
 * @return bool true
 */
function new_resource_mod_created($eventdata) {
    return new_resource_course_update($eventdata, 'mod_created');
}

/**
 * Function for handling mod_updated events
 * 
 * @param stdClass $eventdata
 * @return bool true
 */
/*function culactivity_stream_mod_updated($eventdata) {
    return culactivity_stream_course_update($eventdata, 'mod_updated');
}*/

/**
 * Function to send messages to users following events that update courses
 * 
 * @param stdClass $eventdata
    * Structure of $eventdata:
    * $eventdata->modulename
    * $eventdata->name       
    * $eventdata->cmid       
    * $eventdata->courseid   
    * $eventdata->userid 
 * @param string $type the name of the event    
 * @return boolean true
 */
function new_resource_course_update($eventdata, $type) {
    global $CFG, $DB;

    $course = $DB->get_record('course', array('id'=>$eventdata->courseid));
    $messagetext = get_string($type, 'resource_notification', $eventdata->name);    
    $coursename = $course->idnumber? $course->idnumber : $course->fullname;    
    $messagetext .= get_string('incourse', 'resource_notification', $coursename);
    
    // required
    $message = new stdClass();
    $message->component = 'resource_notification';
    $message->modulename = $eventdata->modulename;
    $message->name = 'course_updates';
    $message->userfrom = $DB->get_record('user', array('id'=>$eventdata->userid));
    $message->subject          = $messagetext;
    $message->fullmessage      = $messagetext;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml  = $messagetext;
    $message->smallmessage     = $messagetext;
    
    // optional
    $message->notification = 1;
    $message->course = $course;
    $message->contexturl = "$CFG->wwwroot/mod/$eventdata->modulename/view.php?id=$eventdata->cmid";
    $message->contexturlname  = $eventdata->name;    
        
    // foreach user that can see this
    $context = $context = context_course::instance($eventdata->courseid);
    $users = get_enrolled_users($context);
    
    foreach ($users as $user) {
        $message->userto = $user;
        message_send($message);        
    }    
	
    
    return true;
}
