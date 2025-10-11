<?php
/**
 * Custom Elementary Lessons page for remui_kids theme
 * Specifically designed for Grade 1-3 students
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
$PAGE->set_url('/theme/remui_kids/elementary_lessons.php');
$PAGE->set_pagelayout('elementary_lessons');
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
$dashboardtype = 'elementary'; // Force elementary for this page

if (!empty($usercohorts)) {
    $cohort = reset($usercohorts);
    $usercohortname = $cohort->name;
    $usercohortid = $cohort->id;
    
    // Ensure this is for elementary students
    if (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
        $dashboardtype = 'elementary';
    }
}

// Get elementary student's lessons
$studentlessons = [];
try {
    // Get user's enrolled courses
    $courses = enrol_get_all_users_courses($USER->id, true);
    
    // Debug: Log the number of enrolled courses
    error_log("Elementary Lessons: User {$USER->id} has " . count($courses) . " enrolled courses");
    
    foreach ($courses as $course) {
        // Debug: Log course info
        error_log("Elementary Lessons: Processing course: {$course->fullname} (ID: {$course->id})");
        
        // Get lessons with proper validation - improved query
        $lessons = $DB->get_records_sql(
            "SELECT l.id, l.name, l.intro, l.timelimit, l.retake, l.attempts, l.course,
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
        
        // Debug: Log the number of lessons found
        error_log("Elementary Lessons: Found " . count($lessons) . " lessons in course {$course->fullname}");
        
        foreach ($lessons as $lesson) {
            // Skip if no valid cmid
            if (empty($lesson->cmid)) {
                error_log("Elementary Lessons: Skipping lesson '{$lesson->name}' - no valid cmid");
                continue;
            }
            
            try {
                // Get lesson progress
                $progress = theme_remui_kids_get_lesson_progress($USER->id, $lesson->id);
                
                // Create lesson URL safely
                $lessonurl = '';
                try {
                    $lessonurl = (new moodle_url('/mod/lesson/view.php', ['id' => $lesson->cmid]))->out();
                } catch (Exception $e) {
                    error_log("Elementary Lessons: Failed to create URL for lesson '{$lesson->name}': " . $e->getMessage());
                    continue; // Skip this lesson if URL creation fails
                }
                
                // Clean lesson intro text
                $intro = $lesson->intro ? strip_tags($lesson->intro) : 'Complete this lesson to continue your learning journey!';
                if (strlen($intro) > 150) {
                    $intro = substr($intro, 0, 150) . '...';
                }
                
                $studentlessons[] = [
                    'id' => $lesson->id,
                    'name' => $lesson->name,
                    'intro' => $intro,
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
                
                // Debug: Log successful lesson processing
                error_log("Elementary Lessons: Successfully processed lesson '{$lesson->name}' from course '{$lesson->coursename}'");
                
            } catch (Exception $e) {
                error_log("Elementary Lessons: Error processing lesson '{$lesson->name}': " . $e->getMessage());
                continue; // Skip problematic lessons
            }
        }
    }
    
    // Debug: Log total lessons found
    error_log("Elementary Lessons: Total lessons found for user {$USER->id}: " . count($studentlessons));
    
    // If no lessons found, try a broader search to see if there are any lessons in enrolled courses
    if (empty($studentlessons)) {
        error_log("Elementary Lessons: No lessons found, trying broader search...");
        
        $courseids = array_keys($courses);
        if (!empty($courseids)) {
            $courseids_sql = implode(',', $courseids);
            $all_lessons = $DB->get_records_sql(
                "SELECT l.id, l.name, l.intro, l.timelimit, l.retake, l.attempts, l.course,
                        cm.id as cmid, cm.completion, cm.completionview, cm.visible as cmvisible,
                        c.id as courseid, c.fullname as coursename, c.shortname as courseshortname
                 FROM {lesson} l
                 JOIN {course_modules} cm ON l.id = cm.instance
                 JOIN {modules} m ON cm.module = m.id AND m.name = 'lesson'
                 JOIN {course} c ON l.course = c.id
                 WHERE l.course IN ($courseids_sql) AND cm.visible = 1 AND cm.deletioninprogress = 0
                 ORDER BY c.fullname, l.name",
                []
            );
            
            error_log("Elementary Lessons: Found " . count($all_lessons) . " total lessons in all enrolled courses");
            
            // Process these lessons as well
            foreach ($all_lessons as $lesson) {
                if (empty($lesson->cmid)) {
                    continue;
                }
                
                try {
                    $progress = theme_remui_kids_get_lesson_progress($USER->id, $lesson->id);
                    
                    $lessonurl = '';
                    try {
                        $lessonurl = (new moodle_url('/mod/lesson/view.php', ['id' => $lesson->cmid]))->out();
                    } catch (Exception $e) {
                        continue;
                    }
                    
                    $intro = $lesson->intro ? strip_tags($lesson->intro) : 'Complete this lesson to continue your learning journey!';
                    if (strlen($intro) > 150) {
                        $intro = substr($intro, 0, 150) . '...';
                    }
                    
                    $studentlessons[] = [
                        'id' => $lesson->id,
                        'name' => $lesson->name,
                        'intro' => $intro,
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
                    continue;
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Elementary Lessons: Error fetching lessons for user {$USER->id}: " . $e->getMessage());
    $studentlessons = []; // Fallback to empty array
}

// Prepare template context for elementary dashboard integration
$templatecontext = [
    'custom_elementary_lessons' => true,
    'dashboard_type' => 'elementary',
    'user_cohort_name' => $usercohortname,
    'user_cohort_id' => $usercohortid,
    'student_name' => $USER->firstname,
    'student_lessons' => $studentlessons,
    'has_student_lessons' => !empty($studentlessons),
    'total_lessons_count' => count($studentlessons),
    
    // URLs for sidebar navigation - pointing to elementary pages
    'dashboardurl' => (new moodle_url('/my/'))->out(),
    'mycoursesurl' => (new moodle_url('/theme/remui_kids/mycourses.php'))->out(),
    'lessonsurl' => (new moodle_url('/theme/remui_kids/elementary_lessons.php'))->out(),
    'activitiesurl' => (new moodle_url('/mod/quiz/index.php'))->out(),
    'achievementsurl' => (new moodle_url('/badges/mybadges.php'))->out(),
    'competenciesurl' => (new moodle_url('/admin/tool/lp/index.php'))->out(),
    'scheduleurl' => (new moodle_url('/calendar/view.php'))->out(),
    'treeviewurl' => (new moodle_url('/course/view.php'))->out(),
    'settingsurl' => (new moodle_url('/user/preferences.php'))->out(),
    'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(),
    'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(),
];

// Set elementary flag
$templatecontext['elementary'] = true;

// Add body class for styling
$templatecontext['bodyattributes'] = 'class="elementary-lessons-page has-student-sidebar"';

// Flag to hide the default navbar
$templatecontext['hide_default_navbar'] = true;

// Render the elementary lessons page
echo $OUTPUT->render_from_template('theme_remui_kids/elementary_lessons_page', $templatecontext);
