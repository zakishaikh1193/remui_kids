<?php


/**
 * Custom Lessons page for remui_kids theme
 *
 * @package    theme_remui_kids
 * @copyright  2024 KodeIt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

require_login();

global $USER, $DB, $PAGE, $OUTPUT, $CFG;

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/lessons.php');
$PAGE->set_pagelayout('lessons');
$PAGE->set_title('My Lessons');
$PAGE->set_heading('My Lessons');

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
$dashboardtype = 'default';

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

// Get student's lessons based on dashboard type
$studentlessons = [];
try {
    if ($dashboardtype === 'elementary') {
        $studentlessons = theme_remui_kids_get_elementary_lessons($USER->id);
    } else {
    // For other grade levels, get all lessons from enrolled courses
    $courses = enrol_get_all_users_courses($USER->id, true);
    foreach ($courses as $course) {
        // First check if there are any lessons in this course
        $lessoncount = $DB->count_records('lesson', ['course' => $course->id]);
        if ($lessoncount == 0) {
            continue;
        }
        
        $lessons = $DB->get_records_sql(
            "SELECT l.id, l.name, l.intro, l.timelimit, l.retake, l.attempts,
                    cm.id as cmid, cm.completion, cm.completionview, cm.visible as cmvisible,
                    c.id as courseid, c.fullname as coursename, c.shortname as courseshortname
             FROM {lesson} l
             JOIN {course_modules} cm ON l.id = cm.instance
             JOIN {modules} m ON cm.module = m.id AND m.name = 'lesson'
             JOIN {course} c ON l.course = c.id
             WHERE l.course = ? AND cm.visible = 1 AND cm.deletioninprogress = 0
             ORDER BY l.name",
            [$course->id]
        );
        
        foreach ($lessons as $lesson) {
            // Skip if no valid cmid
            if (empty($lesson->cmid)) {
                error_log("Skipping lesson {$lesson->id} - no valid cmid");
                continue;
            }
            
            try {
                // Get lesson progress
                $progress = theme_remui_kids_get_lesson_progress($USER->id, $lesson->id);
                
                // Create lesson URL with error handling
                $lessonurl = '';
                try {
                    $lessonurl = (new moodle_url('/mod/lesson/view.php', ['id' => $lesson->cmid]))->out();
                } catch (Exception $e) {
                    error_log("Error creating lesson URL for lesson {$lesson->id}, cmid {$lesson->cmid}: " . $e->getMessage());
                    continue;
                }
                
                $studentlessons[] = [
                    'id' => $lesson->id,
                    'name' => $lesson->name,
                    'intro' => $lesson->intro,
                    'timelimit' => $lesson->timelimit,
                    'retake' => $lesson->retake,
                    'attempts' => $lesson->attempts,
                    'courseid' => $lesson->courseid,
                    'coursename' => $lesson->coursename,
                    'courseshortname' => $lesson->courseshortname,
                    'cmid' => $lesson->cmid,
                    'progress_percentage' => $progress['percentage'],
                    'completed_attempts' => $progress['attempts'],
                    'best_grade' => $progress['best_grade'],
                    'lessonurl' => $lessonurl,
                    'completed' => $progress['percentage'] >= 100,
                    'in_progress' => $progress['percentage'] > 0 && $progress['percentage'] < 100,
                    'not_started' => $progress['percentage'] == 0,
                    'estimated_time' => $lesson->timelimit > 0 ? gmdate("H:i", $lesson->timelimit) : 'No limit'
                ];
            } catch (Exception $e) {
                error_log("Error processing lesson {$lesson->id}: " . $e->getMessage());
                continue;
            }
        }
    }
}
} catch (Exception $e) {
    error_log("Error getting lessons for user {$USER->id}: " . $e->getMessage());
    $studentlessons = []; // Fallback to empty array
}

// Prepare template context
$templatecontext = [
    'custom_lessons' => true,
    'dashboard_type' => $dashboardtype,
    'user_cohort_name' => $usercohortname,
    'user_cohort_id' => $usercohortid,
    'student_name' => $USER->firstname,
    'student_lessons' => $studentlessons,
    'has_student_lessons' => !empty($studentlessons),
    'total_lessons_count' => count($studentlessons),
    
    // URLs for sidebar navigation
    'dashboardurl' => (new moodle_url('/my/'))->out(),
    'mycoursesurl' => (new moodle_url('/theme/remui_kids/mycourses.php'))->out(),
    'lessonsurl' => (new moodle_url('/theme/remui_kids/lessons.php'))->out(),
    'activitiesurl' => (new moodle_url('/mod/quiz/index.php'))->out(),
    'achievementsurl' => (new moodle_url('/badges/mybadges.php'))->out(),
    'competenciesurl' => (new moodle_url('/admin/tool/lp/index.php'))->out(),
    'scheduleurl' => (new moodle_url('/calendar/view.php'))->out(),
    'treeviewurl' => (new moodle_url('/course/view.php'))->out(),
    'settingsurl' => (new moodle_url('/user/preferences.php'))->out(),
    'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
    'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
];

// Set individual dashboard type flags
$templatecontext['elementary'] = ($dashboardtype === 'elementary');
$templatecontext['middle'] = ($dashboardtype === 'middle');
$templatecontext['highschool'] = ($dashboardtype === 'highschool');
$templatecontext['default'] = ($dashboardtype === 'default');

// Add body class for styling
$templatecontext['bodyattributes'] = 'class="custom-lessons-page has-student-sidebar"';

// Flag to hide the default navbar
$templatecontext['hide_default_navbar'] = true;

// Debug information
error_log("Lessons page - User ID: {$USER->id}, Dashboard Type: {$dashboardtype}, Lessons Count: " . count($studentlessons));

try {
    echo $OUTPUT->render_from_template('theme_remui_kids/lessons_page', $templatecontext);
} catch (Exception $e) {
    error_log("Error rendering lessons page template: " . $e->getMessage());
    
    // Fallback - show a simple error page
    echo "<h1>Lessons Page</h1>";
    echo "<p>There was an error loading the lessons. Please try again later.</p>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='/my/'>Back to Dashboard</a>";
}
