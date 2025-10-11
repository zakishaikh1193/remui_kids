<?php
/**
 * Sidebar Helper Functions for remui_kids theme
 *
 * This file provides helper functions to set up sidebar context
 * for consistent navigation across all elementary pages.
 *
 * @package    theme_remui_kids
 * @copyright  2024 KodeIt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get elementary sidebar context for template
 *
 * @param string $current_page The current page identifier (dashboard, mycourses, lessons, activities, etc.)
 * @param object $USER The current user object
 * @return array Template context array for sidebar
 */
function theme_remui_kids_get_elementary_sidebar_context($current_page = 'dashboard', $USER = null) {
    global $CFG, $DB;
    
    if ($USER === null) {
        global $USER;
    }
    
    // Determine which page is active
    $active_flags = [
        'is_dashboard_page' => ($current_page === 'dashboard'),
        'is_mycourses_page' => ($current_page === 'mycourses'),
        'is_lessons_page' => ($current_page === 'lessons'),
        'is_activities_page' => ($current_page === 'activities'),
        'is_achievements_page' => ($current_page === 'achievements'),
        'is_competencies_page' => ($current_page === 'competencies'),
        'is_schedule_page' => ($current_page === 'schedule'),
        'is_settings_page' => ($current_page === 'settings'),
        'is_profile_page' => ($current_page === 'profile'),
        'is_scratch_emulator_page' => ($current_page === 'scratch_emulator'),
        'is_code_editor_page' => ($current_page === 'code_editor'),
    ];
    
    // Get user's cohort information with error handling
    $usercohorts = [];
    $usercohortname = '';
    $usercohortid = 0;
    $dashboardtype = 'default';
    
    try {
        if ($DB && isset($USER->id)) {
            $usercohorts = $DB->get_records_sql(
                "SELECT c.name, c.id 
                 FROM {cohort} c 
                 JOIN {cohort_members} cm ON c.id = cm.cohortid 
                 WHERE cm.userid = ?",
                [$USER->id]
            );
        }
    } catch (Exception $e) {
        // If database query fails, use default values
        error_log("Sidebar helper database error: " . $e->getMessage());
        $usercohorts = [];
    }

    if (!empty($usercohorts)) {
        $cohort = reset($usercohorts);
        $usercohortname = $cohort->name;
        $usercohortid = $cohort->id;
        
        // Determine dashboard type based on cohort
        if (preg_match('/grade\s*(?:1[0-2]|[8-9])/i', $usercohortname)) {
            $dashboardtype = 'highschool';
        } elseif (preg_match('/grade\s*[4-7]/i', $usercohortname)) {
            $dashboardtype = 'middle';
        } elseif (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
            $dashboardtype = 'elementary';
        }
    }
    
    // Return sidebar context
    return array_merge($active_flags, [
        // User information
        'user_cohort_name' => $usercohortname,
        'user_cohort_id' => $usercohortid,
        'student_name' => $USER->firstname,
        'dashboard_type' => $dashboardtype,
        
        // URLs for sidebar navigation
        'dashboardurl' => (new moodle_url('/my/'))->out(),
        'mycoursesurl' => (new moodle_url('/theme/remui_kids/moodle_mycourses.php'))->out(),
        'lessonsurl' => (new moodle_url('/theme/remui_kids/elementary_lessons.php'))->out(),
        'activitiesurl' => (new moodle_url('/theme/remui_kids/elementary_activities.php'))->out(),
        'achievementsurl' => (new moodle_url('/badges/mybadges.php'))->out(),
        'competenciesurl' => (new moodle_url('/admin/tool/lp/index.php'))->out(),
        'scheduleurl' => (new moodle_url('/calendar/view.php'))->out(),
        'treeviewurl' => (new moodle_url('/course/view.php'))->out(),
        'settingsurl' => (new moodle_url('/user/preferences.php'))->out(),
        'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
        'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
        
        // Quick action URLs
        'ebooksurl' => (new moodle_url('/mod/book/index.php'))->out(),
        'messagesurl' => (new moodle_url('/message/index.php'))->out(),
        'askteacherurl' => (new moodle_url('/message/index.php'))->out(),
        'shareclassurl' => (new moodle_url('/mod/forum/index.php'))->out(),
        'scratcheditorurl' => (new moodle_url('/theme/remui_kids/scratch_simple.php'))->out(),
        'codeeditorurl' => (new moodle_url('/theme/remui_kids/code_editor_simple.php'))->out(),
        
        // Additional URLs for G4G7 sidebar
        'gradesurl' => (new moodle_url('/grade/report/user/index.php'))->out(),
        'badgesurl' => (new moodle_url('/badges/mybadges.php'))->out(),
    ]);
}

/**
 * Render elementary sidebar
 *
 * @param string $current_page The current page identifier
 * @param object $USER The current user object
 * @return string Rendered sidebar HTML
 */
function theme_remui_kids_render_elementary_sidebar($current_page = 'dashboard', $USER = null) {
    global $OUTPUT;
    
    $sidebar_context = theme_remui_kids_get_elementary_sidebar_context($current_page, $USER);
    
    return $OUTPUT->render_from_template('theme_remui_kids/elementary_sidebar', $sidebar_context);
}

/**
 * Get elementary sidebar styles
 *
 * @return string Rendered sidebar styles HTML
 */
function theme_remui_kids_get_elementary_sidebar_styles() {
    global $OUTPUT;
    
    return $OUTPUT->render_from_template('theme_remui_kids/elementary_sidebar_styles', []);
}
