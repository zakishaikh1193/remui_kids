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

require_once($CFG->dirroot . '/theme/remui_kids/layout/common.php');

// Check if this is a dashboard page and use our custom dashboard
if ($PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    // Check if user is admin first
    $isadmin = is_siteadmin($USER) || has_capability('moodle/site:config', context_system::instance(), $USER);
    
    if ($isadmin) {
        // Show admin dashboard
        $templatecontext['custom_dashboard'] = true;
        $templatecontext['dashboard_type'] = 'admin';
        $templatecontext['admin_dashboard'] = true;
        $templatecontext['wwwroot'] = $CFG->wwwroot;
        $templatecontext['admin_stats'] = theme_remui_kids_get_admin_dashboard_stats();
        $templatecontext['admin_user_stats'] = theme_remui_kids_get_admin_user_stats();
        $templatecontext['admin_course_stats'] = theme_remui_kids_get_admin_course_stats();
        $templatecontext['admin_recent_activity'] = theme_remui_kids_get_admin_recent_activity();
        
        // Must be called before rendering the template.
        require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
        
        // Render our custom admin dashboard template
        echo $OUTPUT->render_from_template('theme_remui_kids/admin_dashboard', $templatecontext);
        return; // Exit early to prevent normal rendering
    }
    
    // Get user's cohort information for non-admin users
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
        // Check for Grade 8-12 (High School) - Check this first to avoid conflicts
        if (preg_match('/grade\s*(?:1[0-2]|[8-9])/i', $usercohortname)) {
            $dashboardtype = 'highschool';
        }
        // Check for Grade 4-7 (Middle)
        elseif (preg_match('/grade\s*[4-7]/i', $usercohortname)) {
            $dashboardtype = 'middle';
        }
        // Check for Grade 1-3 (Elementary) - Check this last
        elseif (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
            $dashboardtype = 'elementary';
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
    $templatecontext['dashboardurl'] = (new moodle_url('/my/'))->out();
    $templatecontext['gradesurl'] = (new moodle_url('/grade/report/overview/index.php'))->out();
    $templatecontext['assignmentsurl'] = (new moodle_url('/mod/assign/index.php'))->out();
    $templatecontext['messagesurl'] = (new moodle_url('/message/index.php'))->out();
    $templatecontext['codeeditorurl'] = (new moodle_url('/mod/lti/view.php', ['id' => 1]))->out(); // Adjust ID as needed
    $templatecontext['scratchurl'] = (new moodle_url('/mod/lti/view.php', ['id' => 2]))->out(); // Adjust ID as needed
    $templatecontext['logouturl'] = (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out();
    $templatecontext['profileurl'] = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out();
    
    // Add custom body class for dashboard styling
    $templatecontext['bodyattributes'] = 'class="custom-dashboard-page has-student-sidebar"';
    
    // Ensure parent theme navigation context is properly set up
    $templatecontext['navlayout'] = \theme_remui\toolbox::get_setting('header-primary-layout-desktop');
    $templatecontext['applylatestuserpref'] = apply_latest_user_pref();
    
    // Set up drawer preferences for parent theme navigation
    user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
    user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
    user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);
    
    $navdraweropen = (get_user_preferences('drawer-open-nav', true) == true);
    $templatecontext['navdraweropen'] = $navdraweropen;
    
    // Add parent theme navigation context
    $templatecontext['applylatestdrawerjs'] = (get_moodle_release_version_branch() > '402');
    
    // Ensure parent theme navigation JavaScript is loaded
    $PAGE->requires->data_for_js('applylatestuserpref', $templatecontext['applylatestuserpref']);
    
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
    
    // Add Grade 8-12 specific statistics and courses for high school students
    if ($dashboardtype === 'highschool') {
        $templatecontext['highschool_stats'] = theme_remui_kids_get_highschool_dashboard_stats($USER->id);
        $templatecontext['highschool_metrics'] = theme_remui_kids_get_highschool_dashboard_metrics($USER->id);
        $courses = theme_remui_kids_get_highschool_courses($USER->id);
        $templatecontext['highschool_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_highschool_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        // Add course sections data for modal preview
        $coursesectionsdata = [];
        foreach ($courses as $course) {
            $sectionsdata = theme_remui_kids_get_course_sections_for_modal($course['id']);
            $coursesectionsdata[$course['id']] = $sectionsdata;
            // Debug: Log the data for each course
            error_log("High school course {$course['id']} ({$course['fullname']}) sections data: " . print_r($sectionsdata, true));
        }
        $templatecontext['highschool_courses_sections'] = json_encode($coursesectionsdata);
        // Debug: Log the final JSON data
        error_log("Final high school courses sections JSON: " . $templatecontext['highschool_courses_sections']);
        
        // Add active sections data (limit to 3 for Current Lessons section)
        $activesections = theme_remui_kids_get_highschool_active_sections($USER->id);
        $templatecontext['highschool_active_sections'] = array_slice($activesections, 0, 3);
        $templatecontext['has_highschool_active_sections'] = !empty($activesections);
        
        // Add active lessons data (limit to 3)
        $activelessons = theme_remui_kids_get_highschool_active_lessons($USER->id);
        $templatecontext['highschool_active_lessons'] = array_slice($activelessons, 0, 3);
        $templatecontext['has_highschool_active_lessons'] = !empty($activelessons);
        
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