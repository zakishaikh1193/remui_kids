<?php
/**
 * Custom Elementary Activities page for remui_kids theme
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
$PAGE->set_url('/theme/remui_kids/elementary_activities.php');
$PAGE->set_pagelayout('elementary_activities');
$PAGE->set_title('My Activities');
$PAGE->set_heading('My Activities');

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

// Get elementary student's activities
$studentactivities = [];
$activitytypes = [
    'quiz' => 'Quiz',
    'assign' => 'Assignment',
    'lesson' => 'Lesson',
    'forum' => 'Discussion',
    'choice' => 'Poll',
    'glossary' => 'Glossary',
    'wiki' => 'Wiki',
    'workshop' => 'Workshop',
    'scorm' => 'SCORM Package',
    'hvp' => 'Interactive Content',
    'lti' => 'External Tool'
];

try {
    // Get user's enrolled courses
    $courses = enrol_get_all_users_courses($USER->id, true);
    
    foreach ($courses as $course) {
        // Get all course modules (activities)
        $activities = $DB->get_records_sql(
            "SELECT cm.id as cmid, cm.instance, cm.completion, cm.completionview, cm.visible as cmvisible,
                    m.name as modulename, m.id as moduleid,
                    c.id as courseid, c.fullname as coursename, c.shortname as courseshortname,
                    COALESCE(q.name, a.name, l.name, f.name, ch.name, g.name, w.name, ws.name, s.name, h.name, lt.name) as activityname,
                    COALESCE(q.intro, a.intro, l.intro, f.intro, ch.intro, g.intro, w.intro, ws.intro, s.intro, h.intro, lt.intro) as activityintro
             FROM {course_modules} cm
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             LEFT JOIN {quiz} q ON (m.name = 'quiz' AND cm.instance = q.id)
             LEFT JOIN {assign} a ON (m.name = 'assign' AND cm.instance = a.id)
             LEFT JOIN {lesson} l ON (m.name = 'lesson' AND cm.instance = l.id)
             LEFT JOIN {forum} f ON (m.name = 'forum' AND cm.instance = f.id)
             LEFT JOIN {choice} ch ON (m.name = 'choice' AND cm.instance = ch.id)
             LEFT JOIN {glossary} g ON (m.name = 'glossary' AND cm.instance = g.id)
             LEFT JOIN {wiki} w ON (m.name = 'wiki' AND cm.instance = w.id)
             LEFT JOIN {workshop} ws ON (m.name = 'workshop' AND cm.instance = ws.id)
             LEFT JOIN {scorm} s ON (m.name = 'scorm' AND cm.instance = s.id)
             LEFT JOIN {hvp} h ON (m.name = 'hvp' AND cm.instance = h.id)
             LEFT JOIN {lti} lt ON (m.name = 'lti' AND cm.instance = lt.id)
             WHERE cm.course = ? AND cm.visible = 1 AND cm.deletioninprogress = 0
             AND m.name IN ('quiz', 'assign', 'lesson', 'forum', 'choice', 'glossary', 'wiki', 'workshop', 'scorm', 'hvp', 'lti')
             ORDER BY cm.section, cm.sequence",
            [$course->id]
        );
        
        foreach ($activities as $activity) {
            // Skip if no valid cmid or activity name
            if (empty($activity->cmid) || empty($activity->activityname)) {
                continue;
            }
            
            try {
                // Get activity progress
                $progress = theme_remui_kids_get_activity_progress($USER->id, $activity->cmid, $activity->modulename);
                
                // Create activity URL safely
                $activityurl = '';
                try {
                    $activityurl = (new moodle_url('/mod/' . $activity->modulename . '/view.php', ['id' => $activity->cmid]))->out();
                } catch (Exception $e) {
                    continue; // Skip this activity if URL creation fails
                }
                
                // Get activity icon
                $activityicon = theme_remui_kids_get_activity_icon($activity->modulename);
                
                $studentactivities[] = [
                    'id' => $activity->cmid,
                    'name' => $activity->activityname,
                    'intro' => $activity->activityintro ?: 'Complete this activity to continue your learning journey!',
                    'type' => $activity->modulename,
                    'typename' => isset($activitytypes[$activity->modulename]) ? $activitytypes[$activity->modulename] : ucfirst($activity->modulename),
                    'courseid' => $activity->courseid,
                    'coursename' => $activity->coursename,
                    'courseshortname' => $activity->courseshortname,
                    'cmid' => $activity->cmid,
                    'progress_percentage' => $progress['percentage'],
                    'completed' => $progress['completed'],
                    'in_progress' => $progress['in_progress'],
                    'not_started' => $progress['not_started'],
                    'activityurl' => $activityurl,
                    'icon' => $activityicon,
                    'estimated_time' => theme_remui_kids_get_activity_estimated_time($activity->modulename)
                ];
            } catch (Exception $e) {
                continue; // Skip problematic activities
            }
        }
    }
} catch (Exception $e) {
    $studentactivities = []; // Fallback to empty array
}

// Prepare template context for elementary dashboard integration
$templatecontext = [
    'custom_elementary_activities' => true,
    'dashboard_type' => 'elementary',
    'user_cohort_name' => $usercohortname,
    'user_cohort_id' => $usercohortid,
    'student_name' => $USER->firstname,
    'student_activities' => $studentactivities,
    'has_student_activities' => !empty($studentactivities),
    'total_activities_count' => count($studentactivities),
    
    // URLs for sidebar navigation - pointing to elementary pages
    'dashboardurl' => (new moodle_url('/my/'))->out(),
    'mycoursesurl' => (new moodle_url('/theme/remui_kids/mycourses.php'))->out(),
    'lessonsurl' => (new moodle_url('/theme/remui_kids/elementary_lessons.php'))->out(),
    'activitiesurl' => (new moodle_url('/theme/remui_kids/elementary_activities.php'))->out(),
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
$templatecontext['bodyattributes'] = 'class="elementary-activities-page has-student-sidebar"';

// Flag to hide the default navbar
$templatecontext['hide_default_navbar'] = true;

// Render the elementary activities page
echo $OUTPUT->render_from_template('theme_remui_kids/elementary_activities_page', $templatecontext);

