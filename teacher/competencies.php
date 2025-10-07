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
 * Teacher Competencies page - minimal UI to browse course competencies
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
    throw new moodle_exception('nopermissions', 'error', '', 'access teacher competencies page');
}

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/competencies.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Competencies');
$PAGE->add_body_class('quizzes-page'); // Reuse page styling

// Breadcrumb.
$PAGE->navbar->add('Competencies');

// Teacher courses.
$teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');

// Output start.
echo $OUTPUT->header();

// Layout wrapper and sidebar (same as other teacher pages).
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
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/enrol/users.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
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
echo '<h1 class="students-page-title">Competencies</h1>';
echo '<p class="students-page-subtitle">Browse and manage competencies linked to your courses</p>';
echo '</div>';

// Overall stats across teacher courses
$courseids = array_keys($teachercourses);
if (!empty($courseids)) {
    list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

    // Total linked competencies across teacher's courses
    $totalcompetencies = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cc.competencyid)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    // Total course-competency links (can be > competencies due to reuse across courses)
    $totallinks = $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    // Number of courses that have at least one competency
    $courseswithcomps = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cc.courseid)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    echo '<div class="stats-grid">';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-sitemap"></i></div><div class="stat-content"><div class="stat-value">' . (int)$totalcompetencies . '</div><div class="stat-label">Unique Competencies</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-link"></i></div><div class="stat-content"><div class="stat-value">' . (int)$totallinks . '</div><div class="stat-label">Total Links</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-book"></i></div><div class="stat-content"><div class="stat-value">' . (int)$courseswithcomps . '</div><div class="stat-label">Courses with Competencies</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-database"></i></div><div class="stat-content"><div class="stat-value">' . count($teachercourses) . '</div><div class="stat-label">Total Courses</div></div></div>';
    echo '</div>';
}

// Course selector
echo '<div class="course-selector">';
echo '<div class="course-dropdown-wrapper">';
echo '<label for="compCourseSelect" class="course-dropdown-label">Select Course</label>';
echo '<select id="compCourseSelect" class="course-dropdown" onchange="window.location.href=this.value">';
echo '<option value="">Choose a course...</option>';

$currentcourseid = optional_param('courseid', 0, PARAM_INT);
foreach ($teachercourses as $course) {
    $selected = ($currentcourseid == $course->id) ? 'selected' : '';
    $url = new moodle_url('/theme/remui_kids/teacher/competencies.php', array('courseid' => $course->id));
    echo '<option value="' . $url->out() . '" ' . $selected . '>' . $course->shortname . ' - ' . $course->fullname . '</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

// If course selected, list competencies for that course
if ($currentcourseid) {
    $course = get_course($currentcourseid);
    echo '<div class="students-container">';

    // Controls
    echo '<div class="students-controls">';
    echo '<div class="search-box">';
    echo '<i class="fa fa-search search-icon"></i>';
    echo '<input type="text" id="compSearch" class="search-input" placeholder="Search competencies..." onkeyup="filterComps()">';
    echo '</div>';
    echo '</div>';

    // Fetch course competencies
    $sql = "SELECT c.id, c.shortname, c.idnumber, c.description
              FROM {competency_coursecomp} cc
              JOIN {competency} c ON c.id = cc.competencyid
             WHERE cc.courseid = ?
          ORDER BY c.shortname ASC";
    $comps = $DB->get_records_sql($sql, array($currentcourseid));

    echo '<div class="students-table-wrapper">';
    echo '<table class="students-table" id="compTable">';
    echo '<thead><tr><th>Name</th><th>ID number</th><th>Description</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    if (empty($comps)) {
        echo '<tr><td colspan="4">No competencies linked to this course yet.</td></tr>';
    } else {
        foreach ($comps as $c) {
            $manageurl = new moodle_url('/admin/tool/lp/coursecompetencies.php', array('courseid' => $currentcourseid));
            echo '<tr data-name="' . s(strtolower($c->shortname)) . ' ' . s(strtolower($c->idnumber)) . '">';
            echo '<td class="student-name"><div class="student-avatar">CP</div>' . format_string($c->shortname) . '</td>';
            echo '<td class="student-email">' . s($c->idnumber) . '</td>';
            echo '<td>' . format_text($c->description, FORMAT_HTML) . '</td>';
            echo '<td><a class="filter-btn" href="' . $manageurl->out() . '" target="_blank">Manage</a></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    echo '</div>';

    echo '</div>';
}

echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper

// Sidebar + page JS
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

function filterComps() {
  const term = (document.getElementById("compSearch")?.value || "").toLowerCase();
  const rows = document.querySelectorAll("#compTable tbody tr");
  rows.forEach(r => {
    if (!term) { r.style.display = ""; return; }
    const bag = (r.getAttribute("data-name") || "").toLowerCase();
    r.style.display = bag.includes(term) ? "" : "none";
  });
}
</script>';

echo $OUTPUT->footer();


