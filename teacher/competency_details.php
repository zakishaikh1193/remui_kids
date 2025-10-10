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
 * Competency Details AJAX Handler
 *
 * @package   theme_remui_kids
 * @copyright 2025 Kodeit
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_login();
$context = context_system::instance();

// Restrict to teachers/admins.
if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'access competency details page');
}

$competencyid = required_param('competencyid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/competency_details.php', array('competencyid' => $competencyid, 'courseid' => $courseid));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Competency Details');
$PAGE->add_body_class('quizzes-page'); // Reuse page styling

// Breadcrumb.
$PAGE->navbar->add('Competencies', new moodle_url('/theme/remui_kids/teacher/competencies.php'));
$PAGE->navbar->add('Competency Details');

// Output start.
echo $OUTPUT->header();

// Add CSS to remove the default main container
echo '<style>
/* Neutralize the default main container */
#region-main,
[role="main"] {
    background: transparent !important;
    box-shadow: none !important;
    border: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}
</style>';

// Layout wrapper and sidebar (same as other teacher pages).
echo '<div class="teacher-css-wrapper">';
echo '<div class="teacher-dashboard-wrapper">';
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

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
// Students section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">STUDENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/enroll_students.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Assessments + Competencies
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php" class="sidebar-link"><i class="fa fa-sitemap sidebar-icon"></i><span class="sidebar-text">Competencies</span></a></li>';
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

echo '<div class="teacher-main-content">';
echo '<div class="students-page-wrapper">';

// Header
echo '<div class="students-page-header">';
echo '<div class="header-content">';
echo '<a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php?courseid=' . $courseid . '" class="back-button" title="Back to Competencies">';
echo '<i class="fa fa-arrow-left"></i> Back to Competencies';
echo '</a>';
echo '<div class="header-text">';
echo '<h1 class="students-page-title">Competency Details</h1>';
echo '<p class="students-page-subtitle">Course: ' . format_string($course->fullname) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Get competency details
$competency = $DB->get_record('competency', array('id' => $competencyid));
if (!$competency) {
    echo '<div class="empty-state"><div class="empty-state-icon"><i class="fa fa-exclamation-triangle"></i></div><div class="empty-state-title">Competency Not Found</div><div class="empty-state-text">The requested competency could not be found.</div></div>';
    echo '</div>'; // students-page-wrapper
    echo '</div>'; // teacher-main-content
    echo '</div>'; // teacher-dashboard-wrapper
    echo $OUTPUT->footer();
    exit;
}

$course = get_course($courseid);
$coursecontext = context_course::instance($course->id);

// Get enrolled students
$students = get_enrolled_users($coursecontext, '', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname, u.firstname');

// Get linked activities count
$linkedactivitiescount = 0;
$hasmodulecomp = $DB->get_manager()->table_exists('competency_modulecomp');
$hasactivity = $DB->get_manager()->table_exists('competency_activity');

if ($hasmodulecomp) {
    $linkedactivitiescount = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cm.id)
           FROM {competency_modulecomp} mc
           JOIN {course_modules} cm ON cm.id = mc.cmid
          WHERE mc.competencyid = ? AND cm.course = ?",
        array($competencyid, $courseid)
    );
} elseif ($hasactivity) {
    $linkedactivitiescount = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cm.id)
           FROM {competency_activity} ca
           JOIN {course_modules} cm ON cm.id = ca.cmid
          WHERE ca.competencyid = ? AND cm.course = ?",
        array($competencyid, $courseid)
    );
}

// Get students with competent status
$competentstudents = 0;
foreach ($students as $student) {
    $usercomp = $DB->get_record('competency_usercomp', array(
        'userid' => $student->id,
        'competencyid' => $competencyid
    ));
    if ($usercomp && $usercomp->proficiency) {
        $competentstudents++;
    }
}

// Get students in progress
$inprogressstudents = 0;
foreach ($students as $student) {
    $usercomp = $DB->get_record('competency_usercomp', array(
        'userid' => $student->id,
        'competencyid' => $competencyid
    ));
    if ($usercomp && $usercomp->status == 1 && !$usercomp->proficiency) {
        $inprogressstudents++;
    }
}

// Simple Competency Overview
echo '<div class="competency-overview">';
echo '<div class="competency-header">';
echo '<h2>' . s($competency->shortname) . '</h2>';
echo '<p class="competency-description">' . format_text($competency->description, FORMAT_HTML) . '</p>';
echo '</div>';

// Current Status Summary
echo '<div class="status-summary">';
echo '<div class="summary-card">';
echo '<div class="summary-icon"><i class="fa fa-users"></i></div>';
echo '<div class="summary-content">';
echo '<div class="summary-number">' . count($students) . '</div>';
echo '<div class="summary-label">Enrolled Students</div>';
echo '</div>';
echo '</div>';

echo '<div class="summary-card">';
echo '<div class="summary-icon"><i class="fa fa-trophy"></i></div>';
echo '<div class="summary-content">';
echo '<div class="summary-number">' . $competentstudents . '</div>';
echo '<div class="summary-label">Competent Students</div>';
echo '</div>';
echo '</div>';

echo '<div class="summary-card">';
echo '<div class="summary-icon"><i class="fa fa-link"></i></div>';
echo '<div class="summary-content">';
echo '<div class="summary-number">' . $linkedactivitiescount . '</div>';
echo '<div class="summary-label">Linked Activities</div>';
echo '</div>';
echo '</div>';

echo '<div class="summary-card">';
echo '<div class="summary-icon"><i class="fa fa-clock"></i></div>';
echo '<div class="summary-content">';
echo '<div class="summary-number">' . $inprogressstudents . '</div>';
echo '<div class="summary-label">In Progress</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';


// Students Overview
echo '<div class="students-section">';
echo '<h3><i class="fa fa-users"></i> Students Overview</h3>';

if (empty($students)) {
    echo '<div class="empty-state">';
    echo '<div class="empty-state-icon"><i class="fa fa-users"></i></div>';
    echo '<div class="empty-state-title">No Students Enrolled</div>';
    echo '<div class="empty-state-text">There are no enrolled students in this course.</div>';
    echo '</div>';
} else {
    echo '<div class="students-table">';
    echo '<div class="students-table-header">';
    echo '<div class="student-col-avatar">Student</div>';
    echo '<div class="student-col-status">Status</div>';
    echo '<div class="student-col-actions">Actions</div>';
    echo '</div>';
    
    foreach ($students as $student) {
        $fullname = $student->firstname . ' ' . $student->lastname;
        $initials = strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1));
        
        // Get student's competency status from course-specific table (what Moodle report uses)
        $usercomp = $DB->get_record('competency_usercompcourse', array(
            'userid' => $student->id,
            'competencyid' => $competencyid,
            'courseid' => $courseid
        ));
        
        // If not found in course table, check global table as fallback
        if (!$usercomp) {
            $usercomp = $DB->get_record('competency_usercomp', array(
                'userid' => $student->id,
                'competencyid' => $competencyid
            ));
        }
        
        $status = 'Not Yet Competent';
        $statusclass = 'status-not-competent';
        if ($usercomp) {
            if ($usercomp->proficiency) {
                $status = 'Competent';
                $statusclass = 'status-competent';
            } elseif ($usercomp->status == 1) {
                $status = 'In Progress';
                $statusclass = 'status-in-progress';
            }
        }
        
        echo '<div class="student-row">';
        echo '<div class="student-col-avatar">';
        echo '<div class="student-avatar">' . $initials . '</div>';
        echo '<div class="student-name">' . s($fullname) . '</div>';
        echo '</div>';
        echo '<div class="student-col-status">';
        echo '<span class="student-status ' . $statusclass . '">' . $status . '</span>';
        echo '</div>';
        echo '<div class="student-col-actions">';
        echo '<a href="' . new moodle_url('/theme/remui_kids/teacher/student_competency_evidence.php', array('userid' => $student->id, 'competencyid' => $competencyid, 'courseid' => $courseid)) . '" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> View Evidence</a>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}
echo '</div>';

echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper
echo '</div>'; // teacher-css-wrapper
// Simple sidebar JS
echo '<script>
function toggleTeacherSidebar() {
  const sidebar = document.querySelector(".teacher-sidebar");
  sidebar.classList.toggle("sidebar-open");
}
document.addEventListener("click", function(event) {
  const sidebar = document.querySelector(".teacher-sidebar");
  const toggleButton = document.querySelector(".sidebar-toggle");
  if (!sidebar || !toggleButton) return;
  if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
    sidebar.classList.remove("sidebar-open");
  }
});
window.addEventListener("resize", function() {
  const sidebar = document.querySelector(".teacher-sidebar");
  if (!sidebar) return;
  if (window.innerWidth > 768) {
    sidebar.classList.remove("sidebar-open");
  }
});
</script>';

echo $OUTPUT->footer();
?>
