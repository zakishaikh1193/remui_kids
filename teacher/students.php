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
 * Teacher's view of enrolled students
 *
 * @package   theme_remui_kids
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Require login and proper access.
require_login();
$context = context_system::instance();

// Check if user has teacher capabilities.
if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'access teacher students page');
}

// Set up the page.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/students.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('teacher_students', 'theme_remui_kids'));
$PAGE->set_heading(get_string('teacher_students', 'theme_remui_kids'));

// Add breadcrumb.
$PAGE->navbar->add(get_string('teacher_students', 'theme_remui_kids'));

// Get all courses where the current user is a teacher.
$teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');

// Start output.
echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('enrolled_students', 'theme_remui_kids'));

if (empty($teachercourses)) {
    echo $OUTPUT->notification(get_string('noteachingcourses', 'theme_remui_kids'));
    echo $OUTPUT->footer();
    exit;
}

// Create a tab for each course.
$tabs = array();
$active = true;

foreach ($teachercourses as $course) {
    $tabs[] = new tabobject(
        'course_' . $course->id,
        new moodle_url('/theme/remui_kids/teacher/students.php', array('courseid' => $course->id)),
        $course->shortname,
        '',
        true
    );
}

echo $OUTPUT->tabtree($tabs);

// Get the selected course.
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid) {
    $course = get_course($courseid);
    $context = context_course::instance($course->id);
    
    // Get all enrolled users with student role.
    $enrolledusers = get_enrolled_users($context, 'moodle/course:isincompletionreports');
    
    if (empty($enrolledusers)) {
        echo $OUTPUT->notification(get_string('nostudentsenrolled', 'theme_remui_kids'));
    } else {
        // Create table for displaying students.
        $table = new flexible_table('teacher-students-table');
        $table->define_columns(array('fullname', 'email', 'lastaccess'));
        $table->define_headers(
            array(
                get_string('fullname'),
                get_string('email'),
                get_string('lastaccess')
            )
        );
        $table->define_baseurl($PAGE->url);
        $table->setup();
        
        foreach ($enrolledusers as $user) {
            $userlastaccess = $user->lastaccess ? userdate($user->lastaccess) : get_string('never');
            $table->add_data(array(
                fullname($user),
                $user->email,
                $userlastaccess
            ));
        }
        
        $table->print_html();
    }
}

echo $OUTPUT->footer();
