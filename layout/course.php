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
 * RemUI Kids - Custom course layout
 *
 * @package    theme_remui_kids
 * @copyright  2025 Kodeit
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $COURSE;

if(!apply_latest_user_pref()){
    user_preference_allow_ajax_update('enable_focus_mode', PARAM_BOOL);
}

// Include parent theme's common layout setup
require_once($CFG->dirroot . '/theme/remui_kids/layout/common.php');

// Set show_course_header flag for common_start template
$templatecontext['show_course_header'] = false;

if (isset($templatecontext['focusdata']['enabled']) && $templatecontext['focusdata']['enabled']) {
    list(
        $templatecontext['focusdata']['sections'],
        $templatecontext['focusdata']['active']
    ) = \theme_remui\utility::get_focus_mode_sections($COURSE);
}

$coursecontext = context_course::instance($COURSE->id);
// Disable old course stats - we're using our custom header instead
$templatecontext['iscoursestatsshow'] = false;

$completion = new \completion_info($COURSE);
$templatecontext['completion'] = $completion->is_enabled();

$roles = get_user_roles(context_course::instance($COURSE->id), $USER->id);
$key = array_search('student', array_column($roles, 'shortname'));
if ($key === false || is_siteadmin()) {
    $templatecontext['notstudent'] = true;
}

$templatecontext['courseid'] = $COURSE->id;

// Check if we're viewing a specific section
$section = optional_param('section', null, PARAM_INT);

// Check if we're actively in edit mode (not just if user can edit)
$isediting = $PAGE->user_is_editing();

// If actively editing, use parent theme's course layout
if ($isediting) {
    // Use parent theme's course layout for editing
    require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
    echo $OUTPUT->render_from_template('theme_remui/course', $templatecontext);
} else if ($section && $section > 0) {
    // If viewing a specific section, show section activities
    $templatecontext['custom_section_view'] = true;
    $templatecontext['current_section'] = $section;
    $templatecontext['section_activities'] = theme_remui_kids_get_section_activities($COURSE, $section);
    $templatecontext['course_url'] = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
    
    // Add course header data for section view
    $templatecontext['course_header_data'] = theme_remui_kids_get_course_header_data($COURSE);
    $templatecontext['show_course_header'] = true;
    
    // Must be called before rendering the template
    require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
    
    echo $OUTPUT->render_from_template('theme_remui_kids/course', $templatecontext);
} else {
    // Use our custom course cards for students (course overview)
    $templatecontext['custom_course_cards'] = true;
    $templatecontext['course_sections'] = theme_remui_kids_get_course_sections_data($COURSE);
    
    // Add course header data for the beautiful header
    $templatecontext['course_header_data'] = theme_remui_kids_get_course_header_data($COURSE);
    $templatecontext['show_course_header'] = true;
    
    // Force disable any old header elements
    $templatecontext['iscoursestatsshow'] = false;
    $templatecontext['notstudent'] = false; // This might be causing issues
    
    // Must be called before rendering the template
    require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
    
    echo $OUTPUT->render_from_template('theme_remui_kids/course', $templatecontext);
}