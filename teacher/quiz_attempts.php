<?php
require_once('../../../config.php');

require_login();
$quizid = required_param('quizid', PARAM_INT);

$quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
$context = context_course::instance($course->id);

if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'access quiz attempts');
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/quiz_attempts.php', array('quizid' => $quizid));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Quiz Attempts - ' . format_string($quiz->name));
$PAGE->add_body_class('quizzes-page');

echo $OUTPUT->header();

echo '<div class="teacher-dashboard-wrapper">';
// Mobile toggle
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

// Sidebar (same as other teacher pages)
echo '<div class="teacher-sidebar">';
echo '  <div class="sidebar-content">';
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">DASHBOARD</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/my/" class="sidebar-link"><i class="fa fa-th-large sidebar-icon"></i><span class="sidebar-text">Teacher Dashboard</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-graduation-cap sidebar-icon"></i><span class="sidebar-text">Gradebook</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '      </ul>';
echo '    </div>';
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">STUDENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/enrol/users.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '      </ul>';
echo '    </div>';
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-star sidebar-icon"></i><span class="sidebar-text">Grading</span></a></li>';
echo '      </ul>';
echo '    </div>';
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">REPORTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/log/index.php" class="sidebar-link"><i class="fa fa-chart-bar sidebar-icon"></i><span class="sidebar-text">Activity Logs</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/outline/index.php" class="sidebar-link"><i class="fa fa-file-alt sidebar-icon"></i><span class="sidebar-text">Course Reports</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Tracking</span></a></li>';
echo '      </ul>';
echo '    </div>';
echo '  </div>';
echo '</div>'; // sidebar

echo '<div class="teacher-main-content">';
echo '<div class="students-page-wrapper">';

echo '<div class="students-page-header">';
echo '<h1 class="students-page-title">Attempts - ' . format_string($quiz->name) . '</h1>';
echo '<p class="students-page-subtitle">Course: ' . format_string($course->fullname) . '</p>';
echo '</div>';

// Fetch attempts (finished) with user info.
$sql = "SELECT qa.id, qa.userid, qa.state, qa.sumgrades, qa.timestart, qa.timefinish, u.firstname, u.lastname, u.email
        FROM {quiz_attempts} qa
        JOIN {user} u ON u.id = qa.userid
        WHERE qa.quiz = ? AND qa.state = ?
        ORDER BY qa.timefinish DESC";
$records = $DB->get_records_sql($sql, array($quizid, 'finished'));

echo '<div class="students-container">';
echo '<div class="students-table-wrapper">';
echo '<table class="students-table">';
echo '<thead><tr><th>Student</th><th>Email</th><th>Score</th><th>Finished</th><th>Actions</th></tr></thead>';
echo '<tbody>';
foreach ($records as $r) {
    $user = (object)array('firstname' => $r->firstname, 'lastname' => $r->lastname);
    // Convert raw sumgrades to quiz total scale (quiz->grade), respecting original out-of scale.
    $score = '-';
    if (!is_null($r->sumgrades)) {
        if ((float)$quiz->sumgrades > 0) {
            $scaled = ($r->sumgrades / (float)$quiz->sumgrades) * (float)$quiz->grade;
            $score = format_float($scaled, 2) . ' / ' . format_float((float)$quiz->grade, 2);
        } else {
            $score = format_float($r->sumgrades, 2);
        }
    }
    $finished = $r->timefinish ? userdate($r->timefinish) : '-';
    $initials = strtoupper(substr($r->firstname, 0, 1) . substr($r->lastname, 0, 1));
    echo '<tr>';
    echo '<td class="student-name"><div class="student-avatar">' . $initials . '</div>' . fullname($user) . '</td>';
    echo '<td class="student-email">' . s($r->email) . '</td>';
    echo '<td>' . $score . '</td>';
    echo '<td>' . $finished . '</td>';
    echo '<td>';
    $reviewurl = new moodle_url('/theme/remui_kids/teacher/quiz_review.php', array('attemptid' => $r->id));
    echo '<a class="filter-btn" href="' . $reviewurl->out() . '" title="Review attempt"><i class="fa fa-eye"></i></a>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
echo '</div>'; // students-container

echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper

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


