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
 * Teacher's view of enrolled students
 *
 * @package   theme_remui_kids
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Require login and proper access.
require_login();
$context = context_system::instance();

// Check if user has teacher capabilities.
if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'access teacher students page');
}

// Set up the page.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/students.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('teacher_students', 'theme_remui_kids'));
// Removed set_heading to prevent duplicate "My Students" header

// Add a specific body class so we can safely scope page-specific CSS overrides
$PAGE->add_body_class('students-page');

// Add breadcrumb.
$PAGE->navbar->add(get_string('teacher_students', 'theme_remui_kids'));

// Get all courses where the current user is a teacher.
$teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');

// Start output.
echo $OUTPUT->header();

// Teacher dashboard layout wrapper and sidebar (same as dashboard)
echo '<div class="teacher-dashboard-wrapper">';
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

// Sidebar navigation
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
        echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php" class="sidebar-link"><i class="fa fa-sitemap sidebar-icon"></i><span class="sidebar-text">Competencies</span></a></li>';
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

// Wrap everything in our custom students container inside main content
echo '<div class="students-page-wrapper">';

// Page Header - Simplified
echo '<div class="students-page-header">';
echo '<h1 class="students-page-title">My Students</h1>';
echo '<p class="students-page-subtitle">Manage and view your enrolled students</p>';
echo '</div>';

if (empty($teachercourses)) {
    echo '<div class="students-container">';
    echo '<div class="empty-state">';
    echo '<div class="empty-state-icon"><i class="fa fa-book"></i></div>';
    echo '<h3 class="empty-state-title">No Teaching Courses</h3>';
    echo '<p class="empty-state-text">You are not enrolled as a teacher in any courses yet.</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // Close students-page-wrapper
    echo $OUTPUT->footer();
    exit;
}

// Course Selector - Professional Dropdown
echo '<div class="course-selector">';
echo '<div class="course-dropdown-wrapper">';
echo '<label for="courseSelect" class="course-dropdown-label">Select Course</label>';
echo '<select id="courseSelect" class="course-dropdown" onchange="window.location.href=this.value">';
echo '<option value="">Choose a course...</option>';

$currentCourseId = optional_param('courseid', 0, PARAM_INT);
foreach ($teachercourses as $course) {
    $selected = ($currentCourseId == $course->id) ? 'selected' : '';
    $courseUrl = new moodle_url('/theme/remui_kids/teacher/students.php', array('courseid' => $course->id));
    echo '<option value="' . $courseUrl->out() . '" ' . $selected . '>' . $course->shortname . ' - ' . $course->fullname . '</option>';
}

echo '</select>';
echo '</div>';
echo '</div>';

// Get the selected course.
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid) {
    $course = get_course($courseid);
    $context = context_course::instance($course->id);
    
    // Get all enrolled users with student role.
    $enrolledusers = get_enrolled_users($context, 'moodle/course:isincompletionreports');
    
    echo '<div class="students-container">';
    
    // Students Header - Removed to eliminate empty div
    
    if (empty($enrolledusers)) {
        echo '<div class="empty-state">';
        echo '<div class="empty-state-icon"><i class="fa fa-users"></i></div>';
        echo '<h3 class="empty-state-title">No Students Enrolled</h3>';
        echo '<p class="empty-state-text">There are no students enrolled in this course yet.</p>';
        echo '</div>';
    } else {
        // Search and Filter Controls
        echo '<div class="students-controls">';
        echo '<div class="search-box">';
        echo '<span class="search-icon"><i class="fa fa-search"></i></span>';
        echo '<input type="text" class="search-input" placeholder="Search students..." id="studentSearch">';
        echo '</div>';
        echo '<div class="filter-buttons">';
        echo '<button class="filter-btn active" data-filter="all">All</button>';
        echo '<button class="filter-btn" data-filter="active">Active</button>';
        echo '<button class="filter-btn" data-filter="inactive">Inactive</button>';
        echo '</div>';
        echo '</div>';
        
        // Students Table
        echo '<div class="students-table-wrapper">';
        echo '<table class="students-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Full Name</th>';
        echo '<th>Email Address</th>';
        echo '<th>Last Access</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($enrolledusers as $user) {
            $userlastaccess = $user->lastaccess ? userdate($user->lastaccess) : get_string('never');
            $lastAccessClass = $user->lastaccess ? 'last-access-recent' : 'last-access-never';
            $userInitials = strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1));
            
            echo '<tr>';
            echo '<td class="student-name">';
            echo '<div class="student-avatar">' . $userInitials . '</div>';
            echo fullname($user);
            echo '</td>';
            echo '<td class="student-email">' . $user->email . '</td>';
            echo '<td class="last-access ' . $lastAccessClass . '">' . $userlastaccess . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        // Add JavaScript for search and filter functionality
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("studentSearch");
            const filterButtons = document.querySelectorAll(".filter-btn");
            const tableRows = document.querySelectorAll(".students-table tbody tr");
            
            // Search functionality
            searchInput.addEventListener("input", function() {
                const searchTerm = this.value.toLowerCase();
                tableRows.forEach(row => {
                    const name = row.querySelector(".student-name").textContent.toLowerCase();
                    const email = row.querySelector(".student-email").textContent.toLowerCase();
                    if (name.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
            
            // Filter functionality
            filterButtons.forEach(button => {
                button.addEventListener("click", function() {
                    filterButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    
                    const filter = this.dataset.filter;
                    tableRows.forEach(row => {
                        const lastAccess = row.querySelector(".last-access").textContent;
                        if (filter === "all") {
                            row.style.display = "";
                        } else if (filter === "active" && lastAccess !== "Never") {
                            row.style.display = "";
                        } else if (filter === "inactive" && lastAccess === "Never") {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    });
                });
            });
        });
        </script>';
    }
    
    echo '</div>'; // Close students-container
}

echo '</div>'; // Close students-page-wrapper

// Close main content and wrapper
echo '</div>'; // End teacher-main-content
echo '</div>'; // End teacher-dashboard-wrapper

// Sidebar toggle script (reuse from dashboard template)
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
