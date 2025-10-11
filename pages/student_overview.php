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


// Add debugging info
if (debugging()) {
    error_log("Student Overview Debug - Student ID: " . $studentid);
    error_log("Student Overview Debug - Overview data: " . print_r($overview, true));
    error_log("Student Overview Debug - Template context: " . print_r($templatecontext, true));
}

// Add Font Awesome CSS directly to head
$PAGE->requires->js_init_code('
    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css";
    document.head.appendChild(link);
');

// Add Teacher Sidebar CSS
$PAGE->requires->js_init_code('
    var style = document.createElement("style");
    style.textContent = `
        .teacher-dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 1001;
            background: #10b981;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .teacher-sidebar {
            width: 280px;
            background: white;
            color: #333;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            border-right: 1px solid #e5e7eb;
        }
        
        .sidebar-content {
            padding: 20px 0;
        }
        
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-category {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0 0 15px 0;
            padding: 0 20px;
            letter-spacing: 1px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-item {
            margin: 0;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover {
            background: #f3f4f6;
            color: #1f2937;
            border-left-color: #10b981;
        }
        
        .sidebar-item.active .sidebar-link {
            background: #f3f4f6;
            color: #1f2937;
            border-left-color: #10b981;
        }
        
        .sidebar-icon {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 16px;
        }
        
        .sidebar-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        .teacher-main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
            
            .teacher-sidebar {
                transform: translateX(-100%);
            }
            
            .teacher-sidebar.open {
                transform: translateX(0);
            }
            
            .teacher-main-content {
                margin-left: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Add toggle function
    window.toggleTeacherSidebar = function() {
        const sidebar = document.querySelector(".teacher-sidebar");
        sidebar.classList.toggle("open");
    };
');

// Include Moodle header for navigation bar
echo $OUTPUT->header();

// Teacher dashboard layout wrapper and sidebar
echo '<div class="teacher-dashboard-wrapper">';
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

// Teacher Sidebar Navigation
echo '<div class="teacher-sidebar">';
echo '  <div class="sidebar-content">';
// Dashboard section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">DASHBOARD</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/my/" class="sidebar-link"><i class="fa fa-th-large sidebar-icon"></i><span class="sidebar-text">Teacher Dashboard</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-graduation-cap sidebar-icon"></i><span class="sidebar-text">Gradebook</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Courses section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">COURSES</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">All Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/edit.php" class="sidebar-link"><i class="fa fa-plus sidebar-icon"></i><span class="sidebar-text">Create Course</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php?categoryid=0" class="sidebar-link"><i class="fa fa-folder sidebar-icon"></i><span class="sidebar-text">Course Categories</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Students section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">STUDENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/enrol/users.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Assessments section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-star sidebar-icon"></i><span class="sidebar-text">Grading</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Reports section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">REPORTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/log/index.php" class="sidebar-link"><i class="fa fa-chart-bar sidebar-icon"></i><span class="sidebar-text">Activity Logs</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/outline/index.php" class="sidebar-link"><i class="fa fa-file-alt sidebar-icon"></i><span class="sidebar-text">Course Reports</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Tracking</span></a></li>';
echo '      </ul>';
echo '    </div>';

echo '  </div>';
echo '</div>'; // end teacher-sidebar

// Main content area next to sidebar
echo '<div class="teacher-main-content">';

// Full Screen Dashboard Layout with Integrated Profile
echo html_writer::start_div('', ['style' => 'min-height: 100vh; background: #f8fafc; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding: 0; margin: 0; width: 100%; overflow-x: hidden;']);

// Include Moodle header for navigation bar
echo $OUTPUT->header();


// Full Width Dashboard Content
echo html_writer::start_div('', ['style' => 'max-width: 1400px; margin: 0 auto; padding: 24px;']);
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 2fr 1fr; gap: 32px;']);

// Left Column
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 24px;']);

// Student Information Card with Profile Inside Container
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); position: relative;']);
// Data indicator dot (green for real data)
echo html_writer::start_div('', ['style' => 'position: absolute; top: 12px; right: 12px; width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::end_div();

// Profile section inside container
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 20px; margin-bottom: 20px;']);
echo html_writer::img($templatecontext['student']['avatar_url'], 'Profile', ['style' => 'width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb;']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 8px;']);
echo html_writer::tag('h1', fullname($student), ['style' => 'margin: 0; font-size: 24px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px;']);
echo html_writer::tag('span', 'Message', ['style' => 'color: #6b7280; font-size: 14px;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Student details below profile
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 20px;']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::tag('div', 'Name: ' . fullname($student), ['style' => 'font-size: 14px; color: #374151;']);
echo html_writer::tag('div', 'Email: ' . $student->email, ['style' => 'font-size: 14px; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::tag('div', 'ID: ' . $student->id, ['style' => 'font-size: 14px; color: #374151;']);
echo html_writer::tag('div', 'Status: Active Student', ['style' => 'font-size: 14px; color: #059669; font-weight: 500;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Overall Performance Card
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; position: relative;']);
// Data indicator dot (green for real data)
echo html_writer::start_div('', ['style' => 'position: absolute; top: 12px; right: 12px; width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::end_div();
echo html_writer::tag('h3', 'Overall performance', ['style' => 'font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;']);
echo html_writer::tag('p', 'Course completion rate', ['style' => 'font-size: 14px; color: #6b7280; margin: 0 0 24px 0;']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: center; align-items: center; margin: 24px 0;']);
echo html_writer::start_div('', ['style' => 'width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#10b981 0deg 288deg, #e5e7eb 288deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto;']);
echo html_writer::start_div('', ['style' => 'width: 80px; height: 80px; background: white; border-radius: 50%; position: absolute;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'text-align: center; z-index: 1;']);
echo html_writer::tag('span', $overview['overall']['percent'] . '%', ['style' => 'display: block; font-size: 24px; font-weight: 700; color: #1f2937; line-height: 1;']);
echo html_writer::tag('span', 'PRO LEARNER', ['style' => 'display: block; font-size: 12px; font-weight: 600; color: #10b981; margin-top: 4px;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Real upcoming classes data
$upcoming_classes = [];

try {
    $html = $OUTPUT->render_from_template('theme_remui_kids/student_overview', $templatecontext);
    echo $html;
} catch (Throwable $e) {
    echo html_writer::div('Student Overview is temporarily unavailable.', 'alert alert-warning');
    echo html_writer::div(format_text($e->getMessage(), FORMAT_PLAIN), 'text-muted');
}
echo $OUTPUT->footer();

?>