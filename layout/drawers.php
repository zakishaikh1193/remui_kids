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
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $COURSE, $USER, $DB, $OUTPUT;

require_once($CFG->dirroot . '/theme/remui/layout/common.php');

// Check if this is a dashboard page and use our custom dashboard
if ($PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    // Get user's cohort information
    $usercohorts = $DB->get_records_sql(
        "SELECT c.name, c.id 
         FROM {cohort} c 
         JOIN {cohort_members} cm ON c.id = cm.cohortid 
         WHERE cm.userid = ?",
        [$USER->id]
    );

    $usercohortname = '';
    $usercohortid = 0;

    if (!empty($usercohorts)) {
        // Get the first cohort (assuming user is in one main cohort)
        $cohort = reset($usercohorts);
        $usercohortname = $cohort->name;
        $usercohortid = $cohort->id;
    }

    // Determine which dashboard layout to show based on cohort
    $dashboardtype = 'default'; // Default dashboard

    if (!empty($usercohortname)) {
        // Check for Grade 1-3 (Elementary)
        if (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
            $dashboardtype = 'elementary';
        }
        // Check for Grade 4-7 (Middle)
        elseif (preg_match('/grade\s*[4-7]/i', $usercohortname)) {
            $dashboardtype = 'middle';
        }
        // Check for Grade 8-12 (High School)
        elseif (preg_match('/grade\s*[8-9]|grade\s*1[0-2]/i', $usercohortname)) {
            $dashboardtype = 'highschool';
        }
    }

    // Add custom dashboard data to template context
    $templatecontext['custom_dashboard'] = true;
    $templatecontext['dashboard_type'] = $dashboardtype;
    $templatecontext['user_cohort_name'] = $usercohortname;
    $templatecontext['user_cohort_id'] = $usercohortid;
    $templatecontext['student_name'] = $USER->firstname;
    $templatecontext['hello_message'] = "Hello " . $USER->firstname . "!";
    $templatecontext['mycoursesurl'] = (new moodle_url('/my/courses.php'))->out();
    
    // Set individual dashboard type flags for Mustache template
    $templatecontext['elementary'] = ($dashboardtype === 'elementary');
    $templatecontext['middle'] = ($dashboardtype === 'middle');
    $templatecontext['highschool'] = ($dashboardtype === 'highschool');
    $templatecontext['default'] = ($dashboardtype === 'default');
    
    // Add Grade 1-3 specific statistics and courses for elementary students
    if ($dashboardtype === 'elementary') {
        $templatecontext['elementary_stats'] = theme_remui_kids_get_elementary_dashboard_stats($USER->id);
        $courses = theme_remui_kids_get_elementary_courses($USER->id);
        $templatecontext['elementary_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_elementary_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        // Add active sections data
        $activesections = theme_remui_kids_get_elementary_active_sections($USER->id);
        $templatecontext['elementary_active_sections'] = $activesections;
        $templatecontext['has_elementary_active_sections'] = !empty($activesections);
        
        // Add active lessons data
        $activelessons = theme_remui_kids_get_elementary_active_lessons($USER->id);
        $templatecontext['elementary_active_lessons'] = $activelessons;
        $templatecontext['has_elementary_active_lessons'] = !empty($activelessons);
        
    }
    
    // Add Grade 4-7 specific statistics and courses for middle school students
    if ($dashboardtype === 'middle') {
        $templatecontext['middle_stats'] = theme_remui_kids_get_elementary_dashboard_stats($USER->id); // Reuse the same stats function
        $courses = theme_remui_kids_get_elementary_courses($USER->id); // Reuse the same courses function
        $templatecontext['middle_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_middle_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        // Add course sections data for modal preview
        $coursesectionsdata = [];
        foreach ($courses as $course) {
            $sectionsdata = theme_remui_kids_get_course_sections_for_modal($course['id']);
            $coursesectionsdata[$course['id']] = $sectionsdata;
            // Debug: Log the data for each course
            error_log("Course {$course['id']} ({$course['fullname']}) sections data: " . print_r($sectionsdata, true));
        }
        $templatecontext['middle_courses_sections'] = json_encode($coursesectionsdata);
        // Debug: Log the final JSON data
        error_log("Final courses sections JSON: " . $templatecontext['middle_courses_sections']);
        
        // Add active sections data (limit to 3 for Current Lessons section)
        $activesections = theme_remui_kids_get_elementary_active_sections($USER->id);
        $templatecontext['middle_active_sections'] = array_slice($activesections, 0, 3); // Show only first 3 sections
        $templatecontext['has_middle_active_sections'] = !empty($activesections);
        
        // Add active lessons data (limit to 3 like elementary dashboard)
        $activelessons = theme_remui_kids_get_elementary_active_lessons($USER->id);
        $templatecontext['middle_active_lessons'] = array_slice($activelessons, 0, 3); // Show only first 3 lessons
        $templatecontext['has_middle_active_lessons'] = !empty($activelessons);
        
        // Add calendar and sidebar data
        $templatecontext['calendar_week'] = theme_remui_kids_get_calendar_week_data($USER->id);
        $templatecontext['upcoming_events'] = theme_remui_kids_get_upcoming_events($USER->id);
        $templatecontext['learning_stats'] = theme_remui_kids_get_learning_progress_stats($USER->id);
        $templatecontext['achievements'] = theme_remui_kids_get_achievements_data($USER->id);
        $templatecontext['calendarurl'] = (new moodle_url('/calendar/view.php'))->out();
    }

    // Add cohort-specific data
    switch ($dashboardtype) {
        case 'elementary':
            $templatecontext['dashboard_title'] = 'Elementary Dashboard (Grades 1-3)';
            $templatecontext['dashboard_color'] = '#FF6B6B'; // Red
            break;
        case 'middle':
            $templatecontext['dashboard_title'] = 'Middle School Dashboard (Grades 4-7)';
            $templatecontext['dashboard_color'] = '#4ECDC4'; // Teal
            break;
        case 'highschool':
            $templatecontext['dashboard_title'] = 'High School Dashboard (Grades 8-12)';
            $templatecontext['dashboard_color'] = '#45B7D1'; // Blue
            break;
        default:
            $templatecontext['dashboard_title'] = 'Default Dashboard';
            $templatecontext['dashboard_color'] = '#95A5A6'; // Gray
            break;
    }

    // Must be called before rendering the template.
    require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
    
    // Render our custom dashboard template
    echo $OUTPUT->render_from_template('theme_remui_kids/dashboard', $templatecontext);
    return; // Exit early to prevent normal rendering
}

// For non-dashboard pages, use the original logic
$coursecontext = context_course::instance($COURSE->id);
if (!is_guest($coursecontext, $USER) &&
    \theme_remui\toolbox::get_setting('enabledashboardcoursestats') &&
    $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    $templatecontext['isdashboardstatsshow'] = true;
    $setupstatus = get_config("theme_remui","setupstatus");
    if(get_config("theme_remui","dashboardpersonalizerinfo") == "show" && ( $setupstatus == "final" || $setupstatus == 'finished' )) {
        $templatecontext['showpersonlizerinfo'] = true;
    }
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
echo $OUTPUT->render_from_template('theme_remui/drawers', $templatecontext);