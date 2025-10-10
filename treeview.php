<?php
/**
 * Tree View Page - Learning Path Explorer
 * Displays Courses → Lessons → Activities in a hierarchical tree format
 * 
 * @package    theme_remui_kids
 * @copyright  2024 WisdmLabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once(__DIR__ . '/lib.php');

// Require login
require_login();

// Set page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/remui_kids/treeview.php'));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('sitename') . ' - Learning Path Explorer');
$PAGE->set_heading('Learning Path Explorer');

// Determine user's cohort and dashboard type
$usercohortid = null;
$usercohortname = '';
$dashboardtype = 'default';

$usercohorts = cohort_get_user_cohorts($USER->id);
if (!empty($usercohorts)) {
    $firstcohort = reset($usercohorts);
    $usercohortid = $firstcohort->id;
    $usercohortname = $firstcohort->name;
    
    // Determine dashboard type based on cohort name
    if (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
        $dashboardtype = 'elementary';
    } elseif (preg_match('/grade\s*[4-6]/i', $usercohortname)) {
        $dashboardtype = 'middle';
    } elseif (preg_match('/grade\s*[7-9]/i', $usercohortname)) {
        $dashboardtype = 'high';
    }
}

// Get all enrolled courses for the user
$courses = enrol_get_all_users_courses($USER->id, true);
$coursedata = [];
$totalcourses = 0;

foreach ($courses as $course) {
    $totalcourses++;
    
    // Get course completion info
    $completion = new completion_info($course);
    $coursecompletion = $completion->get_completion($USER->id, COMPLETION_CRITERIA_TYPE_COURSE);
    $courseprogress = $coursecompletion ? $coursecompletion->completionstate : 0;
    
    // Get all course modules
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    
    // Count sections
    $sectioncount = count($sections);
    
    // Get lessons (treating lessons as "sections" in the new design)
    $lessons = [];
    $lessonnumber = 1;
    
    foreach ($sections as $sectionnum => $section) {
        if ($sectionnum == 0) continue; // Skip section 0
        
        $sectionname = $section->name ?: "Lesson " . $lessonnumber;
        $sectionactivities = [];
        $activitycount = 0;
        $completedactivities = 0;
        
        // Get activities in this section
        $cms = $modinfo->get_cms();
        foreach ($cms as $cm) {
            if ($cm->sectionnum == $sectionnum && $cm->uservisible) {
                $completiondata = $completion->get_data($cm, false, $USER->id);
                $iscompleted = $completiondata->completionstate == COMPLETION_COMPLETE;
                
                if ($iscompleted) {
                    $completedactivities++;
                }
                $activitycount++;
                
                // Get estimated time for activity
                $estimatedtime = theme_remui_kids_get_activity_estimated_time($cm->modname);
                
                $sectionactivities[] = [
                    'activity_number' => $activitycount,
                    'id' => $cm->id,
                    'name' => $cm->name,
                    'type' => $cm->modname,
                    'duration' => $estimatedtime,
                    'points' => 100, // Default points
                    'icon' => theme_remui_kids_get_activity_icon($cm->modname),
                    'url' => $cm->url ? $cm->url->out() : '',
                    'completed' => $iscompleted
                ];
            }
        }
        
        if ($activitycount > 0) {
            $sectionprogress = $activitycount > 0 ? round(($completedactivities / $activitycount) * 100) : 0;
            
            $lessons[] = [
                'id' => $sectionnum,
                'name' => $sectionname,
                'activity_count' => $activitycount,
                'progress_percentage' => $sectionprogress,
                'has_activities' => !empty($sectionactivities),
                'activities' => $sectionactivities,
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $sectionnum]))->out()
            ];
        }
        
        $lessonnumber++;
    }
    
    // Calculate course progress
    $totalactivities = array_sum(array_column($lessons, 'activity_count'));
    $totalcompleted = 0;
    foreach ($lessons as $lesson) {
        $totalcompleted += round(($lesson['progress_percentage'] / 100) * $lesson['activity_count']);
    }
    $courseprogresspercentage = $totalactivities > 0 ? round(($totalcompleted / $totalactivities) * 100) : 0;
    
    $coursedata[] = [
        'id' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'total_sections' => count($lessons),
        'progress_percentage' => $courseprogresspercentage,
        'has_lessons' => !empty($lessons),
        'lessons' => $lessons,
        'course_url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out()
    ];
}

// Prepare template context
$templatecontext = [
    'custom_treeview' => true,
    'dashboard_type' => $dashboardtype,
    'user_cohort_name' => $usercohortname,
    'user_cohort_id' => $usercohortid,
    'student_name' => $USER->firstname,
    'student_fullname' => fullname($USER),
    
    // Tree data
    'total_courses' => $totalcourses,
    'has_courses' => !empty($coursedata),
    'courses' => $coursedata,
    
    // URLs for navigation based on dashboard type
    'dashboardurl' => (new moodle_url('/my/'))->out(),
    'mycoursesurl' => $dashboardtype === 'elementary' ? 
        (new moodle_url('/theme/remui_kids/mycourses.php'))->out() : 
        (new moodle_url('/my/courses.php'))->out(),
    'lessonsurl' => $dashboardtype === 'elementary' ? 
        (new moodle_url('/theme/remui_kids/elementary_lessons.php'))->out() : 
        (new moodle_url('/mod/lesson/index.php'))->out(),
    'activitiesurl' => $dashboardtype === 'elementary' ? 
        (new moodle_url('/theme/remui_kids/elementary_activities.php'))->out() : 
        (new moodle_url('/mod/quiz/index.php'))->out(),
    'achievementsurl' => (new moodle_url('/badges/mybadges.php'))->out(),
    'competenciesurl' => (new moodle_url('/admin/tool/lp/index.php'))->out(),
    'scheduleurl' => (new moodle_url('/theme/remui_kids/schedule.php'))->out(),
    'treeviewurl' => (new moodle_url('/theme/remui_kids/treeview.php'))->out(),
    'settingsurl' => (new moodle_url('/user/preferences.php'))->out(),
    'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
    'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
    'scratchemulatorurl' => (new moodle_url('/theme/remui_kids/scratch_emulator.php'))->out(),
    'calendarurl' => (new moodle_url('/calendar/view.php'))->out(),
    
    // Sidebar flags
    'show_elementary_sidebar' => $dashboardtype === 'elementary',
    'show_middle_sidebar' => $dashboardtype === 'middle',
    'show_high_sidebar' => $dashboardtype === 'high',
    'hide_default_navbar' => true,
    
    // Active state for navigation
    'is_treeview_active' => true,
    
    // Body attributes for styling
    'bodyattributes' => $OUTPUT->body_attributes(['class' => 'treeview-page ' . $dashboardtype . '-dashboard']),
];

// Render the template
echo $OUTPUT->render_from_template('theme_remui_kids/treeview_page', $templatecontext);