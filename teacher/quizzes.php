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
 * Teacher Quizzes page - custom minimal UI listing quizzes for a selected course
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
    throw new moodle_exception('nopermissions', 'error', '', 'access teacher quizzes page');
}

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/quizzes.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Quizzes');
$PAGE->add_body_class('quizzes-page');

// Breadcrumb.
$PAGE->navbar->add('Quizzes');

// Teacher courses.
$teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');

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

echo '<div class="teacher-css-wrapper">';
// Layout wrapper and sidebar (same as students page).
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
// Assessments section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
        echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php" class="sidebar-link"><i class="fa fa-sitemap sidebar-icon"></i><span class="sidebar-text">Competencies</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-star sidebar-icon"></i><span class="sidebar-text">Grading</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Questions section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">QUESTIONS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/pages/questions_unified.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Questions Management</span></a></li>';
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
echo '<h1 class="students-page-title">Quizzes</h1>';
echo '<p class="students-page-subtitle">Browse and manage quizzes in your courses</p>';
echo '</div>';

// Calculate overall statistics across all teacher's courses
$courseids = array_keys($teachercourses);
if (!empty($courseids)) {
    list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
    
    // Total quizzes
    $totalquizzes = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT q.id) 
         FROM {quiz} q 
         JOIN {course_modules} cm ON cm.instance = q.id 
         JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
         WHERE q.course $insql",
        $params
    );
    
    // Get all quiz IDs for this teacher's courses
    $quizids = $DB->get_fieldset_sql(
        "SELECT DISTINCT q.id 
         FROM {quiz} q 
         WHERE q.course $insql",
        $params
    );
    
    $activestudents = 0;
    $totalattempts = 0;
    $finishedattempts = 0;
    
    if (!empty($quizids)) {
        list($quizinsql, $quizparams) = $DB->get_in_or_equal($quizids, SQL_PARAMS_NAMED);
        
        // Active students (unique students who have attempted any quiz)
        $activestudents = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT userid) 
             FROM {quiz_attempts} 
             WHERE quiz $quizinsql",
            $quizparams
        );
        
        // Total attempts
        $totalattempts = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {quiz_attempts} 
             WHERE quiz $quizinsql",
            $quizparams
        );
        
        // Finished attempts for completion rate
        $finishedattempts = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {quiz_attempts} 
             WHERE quiz $quizinsql AND state = 'finished'",
            $quizparams
        );
    }
    
    $completionrate = ($totalattempts > 0) ? round(($finishedattempts / $totalattempts) * 100, 1) : 0;
    
    // Display statistics cards
    echo '<div class="stats-grid">';
    
    // Card 1: Total Quizzes
    echo '<div class="stat-card">';
    echo '<div class="stat-icon"><i class="fa fa-question-circle"></i></div>';
    echo '<div class="stat-content">';
    echo '<div class="stat-value">' . $totalquizzes . '</div>';
    echo '<div class="stat-label">Total Quizzes</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 2: Active Students
    echo '<div class="stat-card">';
    echo '<div class="stat-icon"><i class="fa fa-users"></i></div>';
    echo '<div class="stat-content">';
    echo '<div class="stat-value">' . $activestudents . '</div>';
    echo '<div class="stat-label">Active Students</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 3: Total Attempts
    echo '<div class="stat-card">';
    echo '<div class="stat-icon"><i class="fa fa-edit"></i></div>';
    echo '<div class="stat-content">';
    echo '<div class="stat-value">' . $totalattempts . '</div>';
    echo '<div class="stat-label">Total Attempts</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 4: Completion Rate
    echo '<div class="stat-card">';
    echo '<div class="stat-icon"><i class="fa fa-check-circle"></i></div>';
    echo '<div class="stat-content">';
    echo '<div class="stat-value">' . $completionrate . '%</div>';
    echo '<div class="stat-label">Completion Rate</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // stats-grid
}

// Course selector
echo '<div class="course-selector">';
echo '<div class="course-dropdown-wrapper">';
echo '<label for="quizCourseSelect" class="course-dropdown-label">Select Course</label>';
echo '<select id="quizCourseSelect" class="course-dropdown" onchange="window.location.href=this.value">';
echo '<option value="">Choose a course...</option>';

$currentcourseid = optional_param('courseid', 0, PARAM_INT);
foreach ($teachercourses as $course) {
    $selected = ($currentcourseid == $course->id) ? 'selected' : '';
    $url = new moodle_url('/theme/remui_kids/teacher/quizzes.php', array('courseid' => $course->id));
    echo '<option value="' . $url->out() . '" ' . $selected . '>' . $course->shortname . ' - ' . $course->fullname . '</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

// If course selected, list quizzes.
if ($currentcourseid) {
    $course = get_course($currentcourseid);
    $modinfo = get_fast_modinfo($course);
    $quizzes = array();
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname === 'quiz' && $cm->uservisible) {
            $quizzes[] = $cm;
        }
    }

    echo '<div class="students-container">';

    if (empty($quizzes)) {
        echo '<div class="empty-state">';
        echo '<div class="empty-state-icon"><i class="fa fa-question-circle"></i></div>';
        echo '<h3 class="empty-state-title">No Quizzes Found</h3>';
        echo '<p class="empty-state-text">This course does not have any quizzes yet.</p>';
        echo '</div>';
    } else {
        echo '<div class="students-table-wrapper">';
        echo '<table class="students-table">';
        echo '<thead><tr><th>Quiz</th><th>Course</th><th>Attempts</th><th>Avg Grade</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($quizzes as $cm) {
            $quizid = $cm->instance; // quiz table id
            $quiz = $DB->get_record('quiz', array('id' => $quizid), 'id,sumgrades,grade,grademethod,name');
            $attemptcount = (int)$DB->get_field_sql('SELECT COUNT(1) FROM {quiz_attempts} WHERE quiz = ? AND state = ?', [ $quizid, 'finished' ]);

            // Respect quiz grading method by using {quiz_grades} which already stores per-user final grades.
            $avggrade = $DB->get_field_sql('SELECT AVG(grade) FROM {quiz_grades} WHERE quiz = ?', [ $quizid ]);
            $avgdisplay = '-';
            if ($avggrade !== false && $avggrade !== null) {
                $avgdisplay = format_float((float)$avggrade, 2) . ' / ' . format_float((float)$quiz->grade, 2);
            }

            $quizurl = new moodle_url('/mod/quiz/view.php', array('id' => $cm->id));
            $attemptsurl = new moodle_url('/theme/remui_kids/teacher/quiz_attempts.php', array('quizid' => $quizid));

            echo '<tr>';
            echo '<td class="student-name"><div class="student-avatar">QZ</div>' . format_string($cm->name) . '</td>';
            echo '<td class="student-email">' . format_string($course->fullname) . '</td>';
            echo '<td>' . $attemptcount . '</td>';
            echo '<td>' . $avgdisplay . '</td>';
            echo '<td>';
            echo '<a class="filter-btn" href="' . $attemptsurl->out() . '">Attempts</a> ';
            echo '<a class="filter-btn" href="' . $quizurl->out() . '" target="_blank">Open</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    echo '</div>'; // students-container
}

echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper
echo '</div>'; // teacher-css-wrapper
// Sidebar JS
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


