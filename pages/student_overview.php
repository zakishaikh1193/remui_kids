<?php
// This page renders a per-student overview dashboard using real Moodle/IOMAD data

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

$studentid = optional_param('id', 0, PARAM_INT);

require_login();

// If no student ID provided, use current user (for testing)
if (!$studentid) {
    $studentid = $USER->id;
}

$student = core_user::get_user($studentid, '*', MUST_EXIST);

// Capability: allow teachers and managers to view, and the user themself
$context = context_user::instance($student->id);
if (!is_siteadmin() && $USER->id !== $student->id) {
    // Allow course-level teachers of any of the student's courses to view
    $isteacher = false;
    try {
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher','manager')");
        $roleids = (is_array($teacherroles) && !empty($teacherroles)) ? array_keys($teacherroles) : [];
        if (!empty($roleids)) {
            list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
            $params['userid'] = $USER->id;
            $params['ctxlevel'] = CONTEXT_COURSE;
            $courseids = $DB->get_records_sql(
                "SELECT DISTINCT ctx.instanceid as courseid
                 FROM {role_assignments} ra
                 JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.userid = :userid AND ctx.contextlevel = :ctxlevel AND ra.roleid {$insql}",
                $params
            );
            $teacherCourseIds = array_map(function($r){return $r->courseid;}, $courseids);
            if (!empty($teacherCourseIds)) {
                list($csql, $cparams) = $DB->get_in_or_equal($teacherCourseIds, SQL_PARAMS_NAMED, 'c');
                $cparams['studentid'] = $student->id;
                $incommon = $DB->record_exists_sql(
                    "SELECT 1 FROM {enrol} e JOIN {user_enrolments} ue ON ue.enrolid = e.id
                     WHERE ue.userid = :studentid AND e.courseid {$csql}", $cparams);
                $isteacher = $incommon;
            }
        }
    } catch (Exception $e) {
        $isteacher = false;
    }
    if (!$isteacher) {
        require_capability('moodle/user:viewdetails', $context);
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/remui_kids/pages/student_overview.php', ['id' => $student->id]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('userdetails', 'moodle') . ': ' . fullname($student));
$PAGE->set_heading(fullname($student));

// Include student overview CSS
$PAGE->requires->css('/theme/remui_kids/style/student_overview.css');

// Fetch overview data with error handling
try {
$overview = theme_remui_kids_get_student_overview($student->id);
} catch (Exception $e) {
    // Fallback data if function fails
    $overview = [
        'overall' => ['percent' => 0],
        'overview_counts' => ['total_courses' => 0, 'completed_courses' => 0, 'hours_spent' => '0h'],
        'engagement' => ['live_classes_percent' => 0, 'quiz_attempts' => 0, 'assignments_done' => 0],
        'upcoming_classes' => [],
        'courses' => [],
        'assignments' => [],
        'quizzes' => [],
        'streak' => ['summary' => 'Data unavailable']
    ];
    if (debugging()) {
        error_log("Student Overview Function Error: " . $e->getMessage());
    }
}

// Template context
$templatecontext = array_merge([
    'student' => [
        'id' => $student->id,
        'fullname' => fullname($student),
        'firstname' => $student->firstname,
        'lastname' => $student->lastname,
        'email' => $student->email,
        'avatar_url' => (new moodle_url('/user/pix.php/' . $student->id . '/f1.jpg'))->out(),
        'profile_url' => (new moodle_url('/user/profile.php', ['id' => $student->id]))->out()
    ],
    'debug' => debugging()
], $overview);

echo $OUTPUT->header();
try {
    $html = $OUTPUT->render_from_template('theme_remui_kids/student_overview', $templatecontext);
    echo $html;
} catch (Throwable $e) {
    echo html_writer::div('Student Overview is temporarily unavailable.', 'alert alert-warning');
    echo html_writer::div(format_text($e->getMessage(), FORMAT_PLAIN), 'text-muted');
}
echo $OUTPUT->footer();

?>