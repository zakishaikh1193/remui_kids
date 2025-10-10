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
 * Teacher's Courses Dashboard - Shows courses organized by categories
 *
 * @package   theme_remui_kids
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent any output buffering issues
ob_start();

require_once('../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Debug: Log that this page is being accessed
if (debugging()) {
    error_log("Teacher Courses Page Accessed - User ID: " . $USER->id);
    // Output some debug info immediately
    echo "<!-- DEBUG: Teacher Courses Page Starting -->";
}

// Require login and proper access.
require_login();

// Prevent any redirects
$PAGE->set_context(context_system::instance());
$context = $PAGE->context;

// Check if user has teacher capabilities - simplified check
$isteacher = false;
$can_create_courses = false;

// Check for site admin first
if (is_siteadmin()) {
    $isteacher = true;
    $can_create_courses = true;
} else {
    // Check for teacher roles
    $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher','manager')");
    $roleids = array_keys($teacherroles);

    if (!empty($roleids)) {
        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;
        
        $teacher_courses = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid AND ctx.contextlevel = :ctxlevel AND ra.roleid {$insql}
             LIMIT 1",
            $params
        );
        
        if (!empty($teacher_courses)) {
            $isteacher = true;
            // Allow course creation for any teacher role
            $can_create_courses = true;
        }
    }
    
    // Also check system context for course creation capability
    if ($isteacher && !$can_create_courses) {
        $can_create_courses = has_capability('moodle/course:create', context_system::instance());
    }
}

if (!$isteacher) {
    throw new moodle_exception('nopermissions', 'error', '', 'You must be a teacher to access this page');
}

// Debug information (remove in production)
if (debugging()) {
    error_log("Teacher Courses Debug - User ID: " . $USER->id);
    error_log("Teacher Courses Debug - Is Teacher: " . ($isteacher ? 'Yes' : 'No'));
    error_log("Teacher Courses Debug - Can Create Courses: " . ($can_create_courses ? 'Yes' : 'No'));
    error_log("Teacher Courses Debug - Is Site Admin: " . (is_siteadmin() ? 'Yes' : 'No'));
}

// Set up the page.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/teacher_courses.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title('My Courses - Teacher Dashboard');
$PAGE->set_heading('');

// Debug: Check if page is being set up correctly
if (debugging()) {
    error_log("Page URL set to: " . $PAGE->url->out());
}

// Add a specific body class so we can safely scope page-specific CSS overrides
$PAGE->add_body_class('teacher-courses-page');

// No breadcrumb needed for this page

// Add Font Awesome CSS directly to head
$PAGE->requires->js_init_code('
    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css";
    document.head.appendChild(link);
');

// Add sidebar toggle functionality
$PAGE->requires->js_init_code('
    // Add toggle function
    window.toggleTeacherSidebar = function() {
        const sidebar = document.querySelector(".teacher-sidebar");
        sidebar.classList.toggle("open");
    };
');

echo $OUTPUT->header();

// Add complete modern CSS
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

/* Remove the main page container */
#page.drawers.drag-container {
    background: transparent !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    border: 0 !important;
}

/* Remove the d-flex flex-wrap container */
div.d-flex.flex-wrap {
    display: none !important;
}

/* Modern Course Management Layout */
.courses-page {
    min-height: 100vh;
    background: #f8fafc;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* Unified Container */
.unified-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin: 24px;
    overflow: hidden;
}

.courses-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px;
    position: relative;
    overflow: hidden;
}

.courses-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'20\' cy=\'20\' r=\'2\' fill=\'rgba(255,255,255,0.1)\'/%3E%3Ccircle cx=\'80\' cy=\'30\' r=\'1.5\' fill=\'rgba(255,255,255,0.1)\'/%3E%3Ccircle cx=\'40\' cy=\'70\' r=\'1\' fill=\'rgba(255,255,255,0.1)\'/%3E%3Ccircle cx=\'90\' cy=\'80\' r=\'2.5\' fill=\'rgba(255,255,255,0.1)\'/%3E%3C/svg%3E") repeat;
    opacity: 0.3;
}

.courses-container {
    padding: 32px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.header-text h1 {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin: 0 0 8px 0;
}

.header-text p {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

.new-course-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.new-course-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.new-course-btn:disabled {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
    opacity: 0.6;
}

.new-course-btn:disabled:hover {
    transform: none;
    background: rgba(255, 255, 255, 0.1);
}

.filters-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.category-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.category-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #f3f4f6;
    color: #374151;
    position: relative;
    z-index: 10;
    pointer-events: auto;
}

.category-btn.active {
    background: #1f2937;
    color: white;
}

.category-btn:hover:not(.active) {
    background: #e5e7eb;
}

.category-btn:active {
    transform: scale(0.95);
    background: #d1d5db;
}

.view-toggles {
    display: flex;
    gap: 4px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 8px;
}

.view-toggle {
    padding: 8px 12px;
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
    z-index: 10;
    pointer-events: auto;
}

.view-toggle.active {
    background: white;
    color: #1f2937;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.view-toggle:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.view-toggle:active {
    transform: scale(0.95);
    background: #d1d5db;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.courses-grid.list-view {
    grid-template-columns: 1fr;
    gap: 16px;
}

.courses-grid.list-view .course-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 16px;
    height: auto;
    min-height: 120px;
}

.courses-grid.list-view .course-illustration {
    width: 80px;
    height: 80px;
    margin-right: 16px;
    margin-bottom: 0;
    flex-shrink: 0;
}

.courses-grid.list-view .course-info {
    flex: 1;
}

.courses-grid.list-view .course-category {
    margin-bottom: 8px;
}

.course-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.course-category {
    display: inline-block;
    padding: 4px 12px;
    background: #ede9fe;
    color: #7c3aed;
    font-size: 12px;
    font-weight: 600;
    border-radius: 12px;
    margin-bottom: 16px;
}

.course-illustration {
    width: 100%;
    height: 140px;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}

.course-illustration.math {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.course-illustration.science {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.course-illustration.language {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.course-illustration.history {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.course-illustration.art {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.course-illustration.technology {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.course-illustration.default {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
}

.course-illustration::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(1px);
}

.course-illustration .illustration-icon {
    font-size: 48px;
    color: rgba(255, 255, 255, 0.9);
    z-index: 2;
    position: relative;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.course-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
    line-height: 1.4;
}

.course-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.course-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.course-detail-label {
    color: #6b7280;
    font-weight: 500;
}

.course-detail-value {
    color: #1f2937;
    font-weight: 600;
}

.course-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.course-status.published {
    background: #d1fae5;
    color: #059669;
}

.course-status.unpublished {
    background: #f3f4f6;
    color: #6b7280;
}

.course-status.archived {
    background: #fef3c7;
    color: #d97706;
}

/* Teacher Sidebar Styles */
.teacher-dashboard-wrapper {
    min-height: 100vh;
    background: #f8fafc;
    position: relative;
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
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .filters-section {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
}
</style>';

// Add a simple test message to verify page is loading
if (debugging()) {
    echo '<div style="background: #d1fae5; color: #059669; padding: 10px; margin: 10px; border-radius: 5px;">DEBUG: Teacher Courses Page Loading Successfully</div>';
}

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
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/teacher_courses.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
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

// Main content area
echo '<div class="teacher-main-content">';
echo '<div class="courses-page">';

// Unified Container
echo '<div class="unified-container">';

// Header Section
echo '<div class="courses-header">';
echo '<div class="header-content">';
echo '<div class="header-text">';
echo '<h1>Courses</h1>';
echo '<p>Create and manage courses in your school.</p>';
echo '</div>';
// For now, always show the button as enabled for teachers
// You can change this back to the permission check if needed
if ($isteacher) {
    echo '<a href="' . $CFG->wwwroot . '/course/edit.php" class="new-course-btn">';
    echo '<i class="fa fa-plus"></i>';
    echo 'New Course';
    echo '</a>';
} else {
    echo '<button class="new-course-btn" disabled title="You do not have permission to create courses">';
    echo '<i class="fa fa-lock"></i>';
    echo 'New Course';
    echo '</button>';
}
echo '</div>';

// Filters Section
echo '<div class="filters-section">';
echo '<div class="category-filters">';
echo '<button class="category-btn active" data-category="all">All Category</button>';
echo '</div>';
echo '<div class="view-toggles">';
echo '<button class="view-toggle active" data-view="grid"><i class="fa fa-th"></i></button>';
echo '<button class="view-toggle" data-view="list"><i class="fa fa-list"></i></button>';
echo '</div>';
echo '</div>';
echo '</div>';

// Courses Grid
echo '<div class="courses-container">';
echo '<div class="courses-grid" id="coursesGrid">';

// Get courses organized by categories
try {
    // Get all categories
    $categories = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC');
    
    // Get courses for current user (where user is teacher)
    $teacher_courses = [];
    $teacher_roles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher','manager')");
    $roleids = array_keys($teacher_roles);
    
    if (!empty($roleids)) {
        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;
        
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT c.*, cat.name as category_name, cat.id as category_id
             FROM {course} c
             JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
             JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.userid = :userid AND ra.roleid {$insql}
             LEFT JOIN {course_categories} cat ON c.category = cat.id
             WHERE c.visible = 1 AND c.id > 1
             ORDER BY cat.sortorder ASC, c.sortorder ASC",
            $params
        );
        
        // Organize courses by category
        foreach ($courses as $course) {
            $category_id = $course->category_id ?: 0;
            $category_name = $course->category_name ?: 'Uncategorized';
            
            if (!isset($teacher_courses[$category_id])) {
                $teacher_courses[$category_id] = [
                    'name' => $category_name,
                    'courses' => []
                ];
            }
            
            // Get course statistics
            $enrollment_count = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ue.userid)
                 FROM {enrol} e
                 JOIN {user_enrolments} ue ON e.id = ue.enrolid
                 WHERE e.courseid = ?",
                [$course->id]
            );
            
            $activity_count = $DB->count_records_sql(
                "SELECT COUNT(*)
                 FROM {course_modules}
                 WHERE course = ? AND visible = 1",
                [$course->id]
            );
            
            // Get course completion percentage (average across all enrolled students)
            $completion_percentage = 0;
            if ($enrollment_count > 0) {
                try {
                    $completed_count = $DB->count_records_sql(
                        "SELECT COUNT(DISTINCT cc.userid)
                         FROM {course_completions} cc
                         WHERE cc.course = ? AND cc.timecompleted IS NOT NULL",
                        [$course->id]
                    );
                    if ($completed_count > 0) {
                        $completion_percentage = round(($completed_count / $enrollment_count) * 100);
                    }
                } catch (Exception $compl_ex) {
                    // If completion tracking fails, just set to 0
                    $completion_percentage = 0;
                }
            }
            
            $teacher_courses[$category_id]['courses'][] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'summary' => $course->summary,
                'startdate' => $course->startdate,
                'enrollment_count' => $enrollment_count,
                'activity_count' => $activity_count,
                'completion_percentage' => $completion_percentage,
                'status' => $course->visible ? 'active' : 'draft'
            ];
        }
    }
    
    // Display courses with modern card format
    if (!empty($teacher_courses)) {
        $all_categories = [];
        
        // Collect all categories for filter buttons
        foreach ($teacher_courses as $category_id => $category_data) {
            if (!empty($category_data['courses'])) {
                $all_categories[$category_data['name']] = true;
            }
        }
        
        // Add category filter buttons
        foreach (array_keys($all_categories) as $category) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const categoryFilters = document.querySelector(".category-filters");
                if (categoryFilters) {
                    const btn = document.createElement("button");
                    btn.className = "category-btn";
                    btn.dataset.category = "' . htmlspecialchars($category) . '";
                    btn.textContent = "' . htmlspecialchars($category) . '";
                    categoryFilters.appendChild(btn);
                    
                    console.log("Created category button:", "' . htmlspecialchars($category) . '");
                    
                    // Add event listener to the new button
                    btn.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const category = this.dataset.category;
                        console.log("Dynamic category button clicked:", category);
                        filterByCategory(category, this);
                    });
                }
            });
            </script>';
        }
        
        foreach ($teacher_courses as $category_id => $category_data) {
            if (empty($category_data['courses'])) {
                continue;
            }
            
            foreach ($category_data['courses'] as $course) {
                $creation_date = date('j M Y', $course['startdate'] ?: time());
                
                // Determine illustration class and icon based on course category
                $illustration_class = 'default';
                $illustration_icon = 'fa-graduation-cap';
                
                $category_lower = strtolower($category_data['name']);
                $course_lower = strtolower($course['shortname']);
                
                if (strpos($category_lower, 'math') !== false || strpos($course_lower, 'math') !== false || strpos($course_lower, 'h.') !== false) {
                    $illustration_class = 'math';
                    $illustration_icon = 'fa-calculator';
                } elseif (strpos($category_lower, 'science') !== false || strpos($course_lower, 'phys') !== false || strpos($course_lower, 'chem') !== false) {
                    $illustration_class = 'science';
                    $illustration_icon = 'fa-flask';
                } elseif (strpos($category_lower, 'language') !== false || strpos($course_lower, 'english') !== false) {
                    $illustration_class = 'language';
                    $illustration_icon = 'fa-book';
                } elseif (strpos($category_lower, 'history') !== false || strpos($category_lower, 'social') !== false) {
                    $illustration_class = 'history';
                    $illustration_icon = 'fa-landmark';
                } elseif (strpos($category_lower, 'art') !== false || strpos($category_lower, 'design') !== false) {
                    $illustration_class = 'art';
                    $illustration_icon = 'fa-palette';
                } elseif (strpos($category_lower, 'technology') !== false || strpos($course_lower, 'computer') !== false || strpos($course_lower, 'programming') !== false) {
                    $illustration_class = 'technology';
                    $illustration_icon = 'fa-laptop-code';
                }
                
                echo '<div class="course-card" data-category="' . htmlspecialchars($category_data['name']) . '">';
                echo '<div class="course-category">' . htmlspecialchars($category_data['name']) . '</div>';
                echo '<div class="course-illustration ' . $illustration_class . '">';
                echo '<i class="fa ' . $illustration_icon . ' illustration-icon"></i>';
                echo '</div>';
                echo '<h3 class="course-title">' . htmlspecialchars($course['fullname']) . '</h3>';
                echo '<div class="course-details">';
                echo '<div class="course-detail">';
                echo '<span class="course-detail-label">Creation Date</span>';
                echo '<span class="course-detail-value">' . $creation_date . '</span>';
                echo '</div>';
                echo '<div class="course-detail">';
                echo '<span class="course-detail-label">Enrolled Students</span>';
                echo '<span class="course-detail-value">' . $course['enrollment_count'] . '</span>';
                echo '</div>';
                echo '<div class="course-detail">';
                echo '<span class="course-detail-label">Completion Rate</span>';
                echo '<span class="course-detail-value">' . $course['completion_percentage'] . '%</span>';
                echo '</div>';
                echo '<div class="course-detail">';
                echo '<span class="course-detail-label">Status</span>';
                echo '<span class="course-status ' . $course['status'] . '">' . ucfirst($course['status']) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
    } else {
        // Empty state
        echo '<div class="empty-state">';
        echo '<div class="empty-icon"><i class="fas fa-book-open"></i></div>';
        echo '<h3 class="empty-title">No courses found</h3>';
        echo '<p class="empty-text">You are not assigned as a teacher in any courses yet.</p>';
        echo '<a href="' . $CFG->wwwroot . '/course/edit.php" class="btn-create">Create Your First Course</a>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    // Error handling
    echo '<div class="empty-state">';
    echo '<div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>';
    echo '<h3 class="empty-title">Error loading courses</h3>';
    echo '<p class="empty-text">There was an error loading your courses. Please try again later.</p>';
    if (debugging()) {
        echo '<div style="background: #fee2e2; color: #991b1b; padding: 15px; margin: 20px; border-radius: 8px; text-align: left;">';
        echo '<strong>Debug Information:</strong><br>';
        echo 'Error: ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo 'File: ' . htmlspecialchars($e->getFile()) . '<br>';
        echo 'Line: ' . $e->getLine() . '<br>';
        echo '<pre style="overflow-x: auto; margin-top: 10px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    echo '</div>';
    
    error_log("Teacher Courses Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

echo '</div>'; // End courses-grid
echo '</div>'; // End courses-container
echo '</div>'; // End unified-container
echo '</div>'; // End courses-page
echo '</div>'; // End teacher-main-content
echo '</div>'; // End teacher-dashboard-wrapper

// JavaScript for interactivity
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM loaded, initializing teacher courses functionality");
    
    // Define functions inside DOMContentLoaded to ensure DOM is ready
    function filterByCategory(category, element) {
        console.log("Filtering by category:", category);
        
        // Update active button
        document.querySelectorAll(".category-btn").forEach(btn => {
            btn.classList.remove("active");
        });
        if (element) {
            element.classList.add("active");
        }
        
        // Filter courses
        const courseCards = document.querySelectorAll(".course-card");
        console.log("Found course cards:", courseCards.length);
        
        courseCards.forEach(card => {
            const cardCategory = card.dataset.category;
            console.log("Card category:", cardCategory, "Filter:", category);
            
            if (category === "all" || cardCategory === category) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }

    function setView(view, element) {
        console.log("Setting view to:", view);
        console.log("Element:", element);
        
        // Update active button
        document.querySelectorAll(".view-toggle").forEach(btn => {
            btn.classList.remove("active");
            console.log("Removed active from button:", btn);
        });
        if (element) {
            element.classList.add("active");
            console.log("Added active to button:", element);
        }
        
        // Change grid layout
        const grid = document.getElementById("coursesGrid");
        console.log("Found grid element:", grid);
        
        if (grid) {
            if (view === "list") {
                grid.style.gridTemplateColumns = "1fr";
                grid.classList.add("list-view");
                grid.classList.remove("grid-view");
                console.log("Set to list view");
            } else {
                grid.style.gridTemplateColumns = "repeat(auto-fill, minmax(350px, 1fr))";
                grid.classList.add("grid-view");
                grid.classList.remove("list-view");
                console.log("Set to grid view");
            }
        } else {
            console.error("Grid element not found!");
        }
    }

    function toggleTeacherSidebar() {
        const sidebar = document.querySelector(".teacher-sidebar");
        if (sidebar) {
            sidebar.classList.toggle("open");
        }
    }
    
    // Make functions globally available
    window.filterByCategory = filterByCategory;
    window.setView = setView;
    window.toggleTeacherSidebar = toggleTeacherSidebar;
    
    // Add click event listeners to category buttons
    const categoryButtons = document.querySelectorAll(".category-btn");
    console.log("Found category buttons:", categoryButtons.length);
    
    categoryButtons.forEach((btn, index) => {
        console.log("Adding event listener to category button:", index, btn);
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            const category = this.dataset.category || this.textContent.trim();
            console.log("Category button clicked:", category);
            console.log("Button element:", this);
            filterByCategory(category, this);
        });
        
        // Test if button is clickable
        console.log("Category button clickable test:", btn.offsetWidth > 0 && btn.offsetHeight > 0);
    });
    
    // Add click event listeners to view toggle buttons
    const viewToggleButtons = document.querySelectorAll(".view-toggle");
    console.log("Found view toggle buttons:", viewToggleButtons.length);
    
    viewToggleButtons.forEach((btn, index) => {
        console.log("Adding event listener to view toggle button:", index, btn);
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const view = this.dataset.view || (this.querySelector("i").classList.contains("fa-th") ? "grid" : "list");
            console.log("View toggle clicked:", view);
            console.log("Button element:", this);
            console.log("Button dataset:", this.dataset);
            
            setView(view, this);
        });
        
        // Test if button is clickable
        console.log("Button clickable test:", btn.offsetWidth > 0 && btn.offsetHeight > 0);
    });
    
    // Add click event listener to New Course button
    const newCourseBtn = document.querySelector(".new-course-btn");
    if (newCourseBtn && !newCourseBtn.disabled) {
        newCourseBtn.addEventListener("click", function(e) {
            e.preventDefault();
            console.log("New Course button clicked");
            
            // Check if user has permission to create courses
            if (confirm("Do you want to create a new course? This will redirect you to the course creation page.")) {
                // Show loading state
                this.innerHTML = \'<i class="fa fa-spinner fa-spin"></i> Creating...\';
                this.style.pointerEvents = "none";
                
                // Redirect to course creation page
                setTimeout(() => {
                    window.location.href = "' . $CFG->wwwroot . '/course/edit.php";
                }, 500);
            }
        });
    } else if (newCourseBtn && newCourseBtn.disabled) {
        console.log("New Course button is disabled - user does not have permission to create courses");
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function(event) {
        const sidebar = document.querySelector(".teacher-sidebar");
        const toggle = document.querySelector(".sidebar-toggle");
        
        if (window.innerWidth <= 768 && 
            sidebar && toggle &&
            !sidebar.contains(event.target) && 
            !toggle.contains(event.target) && 
            sidebar.classList.contains("open")) {
            sidebar.classList.remove("open");
        }
    });
    
    // Fallback: Use event delegation for all buttons
    document.addEventListener("click", function(e) {
        // Handle category buttons
        if (e.target.closest(".category-btn")) {
            const btn = e.target.closest(".category-btn");
            e.preventDefault();
            e.stopPropagation();
            
            const category = btn.dataset.category || btn.textContent.trim();
            console.log("Category button clicked via delegation:", category);
            console.log("Button element:", btn);
            
            filterByCategory(category, btn);
        }
        
        // Handle view toggle buttons
        if (e.target.closest(".view-toggle")) {
            const btn = e.target.closest(".view-toggle");
            e.preventDefault();
            e.stopPropagation();
            
            const view = btn.dataset.view || (btn.querySelector("i").classList.contains("fa-th") ? "grid" : "list");
            console.log("View toggle clicked via delegation:", view);
            console.log("Button element:", btn);
            
            setView(view, btn);
        }
    });
    
    console.log("All event listeners initialized successfully");
});
</script>';

echo $OUTPUT->footer();
