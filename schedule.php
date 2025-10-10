<?php
/**
 * Schedule Page - Upcoming Activities by Date
 * Displays upcoming activities, assignments, quizzes organized by date
 * 
 * @package    theme_remui_kids
 * @copyright  2024 WisdmLabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');

// Require login
require_login();

// Set page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/remui_kids/schedule.php'));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('sitename') . ' - Schedule');
$PAGE->set_heading('My Schedule');

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

// Get enrolled courses
$courses = enrol_get_all_users_courses($USER->id, true);
$courseids = array_keys($courses);

// Prepare schedule data
$scheduleactivities = [];
$now = time();
$futuredate = strtotime('+30 days'); // Get activities for next 30 days

// Get upcoming assignments
if (!empty($courseids)) {
    $courseids_sql = implode(',', $courseids);
    
    // Get assignments with due dates
    $assignments = $DB->get_records_sql(
        "SELECT a.id, a.name, a.duedate, a.allowsubmissionsfromdate, a.course, a.intro,
                c.fullname as coursename, c.shortname as courseshortname,
                cm.id as cmid
         FROM {assign} a
         JOIN {course} c ON a.course = c.id
         JOIN {course_modules} cm ON cm.instance = a.id
         JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
         WHERE a.course IN ($courseids_sql)
         AND a.duedate > ?
         AND a.duedate <= ?
         AND cm.visible = 1
         AND cm.deletioninprogress = 0
         ORDER BY a.duedate ASC",
        [$now, $futuredate]
    );
    
    foreach ($assignments as $assign) {
        $scheduleactivities[] = [
            'type' => 'assignment',
            'icon' => 'fa-file-text',
            'name' => $assign->name,
            'coursename' => $assign->coursename,
            'date' => $assign->duedate,
            'dateformatted' => userdate($assign->duedate, '%A, %d %B %Y'),
            'timeformatted' => userdate($assign->duedate, '%I:%M %p'),
            'dayname' => userdate($assign->duedate, '%A'),
            'daynum' => userdate($assign->duedate, '%d'),
            'monthname' => userdate($assign->duedate, '%B'),
            'url' => (new moodle_url('/mod/assign/view.php', ['id' => $assign->cmid]))->out(),
            'description' => strip_tags($assign->intro),
            'color' => 'blue'
        ];
    }
    
    // Get quizzes with due dates
    $quizzes = $DB->get_records_sql(
        "SELECT q.id, q.name, q.timeclose, q.timeopen, q.course, q.intro,
                c.fullname as coursename, c.shortname as courseshortname,
                cm.id as cmid
         FROM {quiz} q
         JOIN {course} c ON q.course = c.id
         JOIN {course_modules} cm ON cm.instance = q.id
         JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
         WHERE q.course IN ($courseids_sql)
         AND q.timeclose > ?
         AND q.timeclose <= ?
         AND cm.visible = 1
         AND cm.deletioninprogress = 0
         ORDER BY q.timeclose ASC",
        [$now, $futuredate]
    );
    
    foreach ($quizzes as $quiz) {
        $scheduleactivities[] = [
            'type' => 'quiz',
            'icon' => 'fa-question-circle',
            'name' => $quiz->name,
            'coursename' => $quiz->coursename,
            'date' => $quiz->timeclose,
            'dateformatted' => userdate($quiz->timeclose, '%A, %d %B %Y'),
            'timeformatted' => userdate($quiz->timeclose, '%I:%M %p'),
            'dayname' => userdate($quiz->timeclose, '%A'),
            'daynum' => userdate($quiz->timeclose, '%d'),
            'monthname' => userdate($quiz->timeclose, '%B'),
            'url' => (new moodle_url('/mod/quiz/view.php', ['id' => $quiz->cmid]))->out(),
            'description' => strip_tags($quiz->intro),
            'color' => 'green'
        ];
    }
    
    // Get lessons with available dates
    $lessons = $DB->get_records_sql(
        "SELECT l.id, l.name, l.available, l.deadline, l.course, l.intro,
                c.fullname as coursename, c.shortname as courseshortname,
                cm.id as cmid
         FROM {lesson} l
         JOIN {course} c ON l.course = c.id
         JOIN {course_modules} cm ON cm.instance = l.id
         JOIN {modules} m ON m.id = cm.module AND m.name = 'lesson'
         WHERE l.course IN ($courseids_sql)
         AND (l.deadline > ? OR l.available > ?)
         AND (l.deadline <= ? OR l.available <= ?)
         AND cm.visible = 1
         AND cm.deletioninprogress = 0
         ORDER BY COALESCE(l.deadline, l.available) ASC",
        [$now, $now, $futuredate, $futuredate]
    );
    
    foreach ($lessons as $lesson) {
        $targetdate = $lesson->deadline > 0 ? $lesson->deadline : $lesson->available;
        $scheduleactivities[] = [
            'type' => 'lesson',
            'icon' => 'fa-play-circle',
            'name' => $lesson->name,
            'coursename' => $lesson->coursename,
            'date' => $targetdate,
            'dateformatted' => userdate($targetdate, '%A, %d %B %Y'),
            'timeformatted' => userdate($targetdate, '%I:%M %p'),
            'dayname' => userdate($targetdate, '%A'),
            'daynum' => userdate($targetdate, '%d'),
            'monthname' => userdate($targetdate, '%B'),
            'url' => (new moodle_url('/mod/lesson/view.php', ['id' => $lesson->cmid]))->out(),
            'description' => strip_tags($lesson->intro),
            'color' => 'purple'
        ];
    }
}

// Sort by date
usort($scheduleactivities, function($a, $b) {
    return $a['date'] - $b['date'];
});

// Group by date
$groupedactivities = [];
foreach ($scheduleactivities as $activity) {
    $datekey = date('Y-m-d', $activity['date']);
    if (!isset($groupedactivities[$datekey])) {
        $groupedactivities[$datekey] = [
            'date' => $activity['date'],
            'dateformatted' => $activity['dateformatted'],
            'dayname' => $activity['dayname'],
            'daynum' => $activity['daynum'],
            'monthname' => $activity['monthname'],
            'activities' => []
        ];
    }
    $groupedactivities[$datekey]['activities'][] = $activity;
}

// Convert to indexed array
$scheduledata = array_values($groupedactivities);

// Calculate statistics
$totalactivities = count($scheduleactivities);
$todayactivities = 0;
$thisweekactivities = 0;
$weekstart = strtotime('monday this week');
$weekend = strtotime('sunday this week');

foreach ($scheduleactivities as $activity) {
    if (date('Y-m-d', $activity['date']) == date('Y-m-d')) {
        $todayactivities++;
    }
    if ($activity['date'] >= $weekstart && $activity['date'] <= $weekend) {
        $thisweekactivities++;
    }
}

// Prepare template context
$templatecontext = [
    'custom_schedule' => true,
    'dashboard_type' => $dashboardtype,
    'user_cohort_name' => $usercohortname,
    'user_cohort_id' => $usercohortid,
    'student_name' => $USER->firstname,
    'student_fullname' => fullname($USER),
    'schedule_data' => $scheduledata,
    'has_activities' => !empty($scheduleactivities),
    'total_activities' => $totalactivities,
    'today_activities' => $todayactivities,
    'thisweek_activities' => $thisweekactivities,
    
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
    'is_schedule_active' => true,
    
    // Body attributes for styling
    'bodyattributes' => $OUTPUT->body_attributes(['class' => 'schedule-page ' . $dashboardtype . '-dashboard']),
];

// Render the template
echo $OUTPUT->render_from_template('theme_remui_kids/schedule_page', $templatecontext);

