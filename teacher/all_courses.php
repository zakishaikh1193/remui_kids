<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();

// Check if user is a teacher
$context = context_system::instance();
$isteacher = has_capability('moodle/course:update', $context) || 
             has_capability('moodle/course:manageactivities', $context);

if (!$isteacher) {
    print_error('You do not have permission to view this page.');
}

// Set up the page
$PAGE->set_url(new moodle_url('/theme/remui_kids/teacher/all_courses.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('All Courses');
$PAGE->set_heading('');

// Add breadcrumb
$PAGE->navbar->add('All Courses');

// Get filter parameters
$search = optional_param('search', '', PARAM_TEXT);
$category_filter = optional_param('category', 0, PARAM_INT);
$sort = optional_param('sort', 'name', PARAM_ALPHA);
$view = optional_param('view', 'grid', PARAM_ALPHA);

// Fetch all courses with detailed information
try {
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.startdate, c.enddate, 
                   c.visible, c.category, cc.name as categoryname, c.timecreated,
                   (SELECT COUNT(DISTINCT ue.userid) 
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                    WHERE e.courseid = c.id AND ue.status = 0) as enrolled_count,
                   (SELECT COUNT(*) 
                    FROM {course_modules} cm
                    WHERE cm.course = c.id) as activity_count
            FROM {course} c
            LEFT JOIN {course_categories} cc ON cc.id = c.category
            WHERE c.id != 1"; // Exclude site course
    
    $params = [];
    
    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (c.fullname LIKE :search1 OR c.shortname LIKE :search2 OR c.summary LIKE :search3)";
        $params['search1'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "%$search%";
    }
    
    // Add category filter
    if ($category_filter > 0) {
        $sql .= " AND c.category = :category";
        $params['category'] = $category_filter;
    }
    
    // Add sorting
    switch ($sort) {
        case 'newest':
            $sql .= " ORDER BY c.timecreated DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY c.timecreated ASC";
            break;
        case 'students':
            $sql .= " ORDER BY enrolled_count DESC";
            break;
        case 'name':
        default:
            $sql .= " ORDER BY c.fullname ASC";
            break;
    }
    
    $courses = $DB->get_records_sql($sql, $params);
    
} catch (Exception $e) {
    error_log('All Courses fetch error: ' . $e->getMessage());
    $courses = [];
}

// Get all categories for filter
try {
    $categories = $DB->get_records_sql(
        "SELECT DISTINCT cc.id, cc.name
         FROM {course_categories} cc
         JOIN {course} c ON c.category = cc.id
         WHERE c.id != 1
         ORDER BY cc.name ASC"
    );
} catch (Exception $e) {
    error_log('Categories fetch error: ' . $e->getMessage());
    $categories = [];
}

// Calculate statistics
$total_courses = count($courses);
$total_students = 0;
$total_activities = 0;
$active_courses = 0;

foreach ($courses as $course) {
    $total_students += $course->enrolled_count;
    $total_activities += $course->activity_count;
    if ($course->visible) {
        $active_courses++;
    }
}

// Start output
echo $OUTPUT->header();

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

// Advanced Professional UI with Modern Design
echo '<style>
/* All Courses Dashboard - Professional Design */
.all-courses-dashboard {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* Main Content Area */
.courses-main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
    width: 100%;
}

/* Top Header */
.courses-top-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.courses-top-header::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.header-content {
    position: relative;
    z-index: 1;
}

.header-title {
    color: white;
    font-size: 36px;
    font-weight: 700;
    margin: 0 0 10px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 0;
}

/* Statistics Cards */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 15px;
}

.stat-card:nth-child(1) .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card:nth-child(2) .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card:nth-child(3) .stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card:nth-child(4) .stat-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stat-label {
    font-size: 13px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    font-weight: 600;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

/* Control Panel */
.control-panel {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.control-section {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
}

.control-section:last-child {
    margin-bottom: 0;
}

.search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 45px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
}

.filter-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-select {
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.view-toggle {
    display: flex;
    gap: 5px;
    background: #f1f5f9;
    padding: 5px;
    border-radius: 10px;
}

.view-btn {
    padding: 10px 15px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #64748b;
}

.view-btn:hover {
    background: white;
    color: #667eea;
}

.view-btn.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Courses Grid View */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.course-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    cursor: pointer;
}

.course-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.course-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px 25px;
    position: relative;
    overflow: hidden;
}

.course-header::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.course-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    margin-bottom: 15px;
    backdrop-filter: blur(10px);
}

.course-title {
    color: white;
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.course-code {
    color: rgba(255, 255, 255, 0.8);
    font-size: 13px;
    margin: 0;
}

.course-body {
    padding: 25px;
}

.course-category {
    display: inline-block;
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
    color: #2d3436;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 15px;
}

.course-summary {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.meta-item {
    text-align: center;
}

.meta-icon {
    color: #667eea;
    font-size: 16px;
    margin-bottom: 5px;
}

.meta-value {
    display: block;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 3px;
}

.meta-label {
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.course-status {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: rgba(67, 233, 123, 0.2);
    color: #00b894;
}

.status-hidden {
    background: rgba(255, 107, 107, 0.2);
    color: #d63031;
}

/* List View */
.courses-list {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.list-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 100px;
    gap: 15px;
    padding: 20px 25px;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    color: #475569;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.list-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 100px;
    gap: 15px;
    padding: 20px 25px;
    border-bottom: 1px solid #e2e8f0;
    align-items: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.list-item:hover {
    background: #f8fafc;
}

.list-course-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.list-course-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.list-course-details h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #1e293b;
    font-weight: 600;
}

.list-course-details p {
    margin: 0;
    font-size: 12px;
    color: #94a3b8;
}

.list-value {
    font-size: 14px;
    color: #475569;
    font-weight: 500;
}

.action-btn {
    padding: 8px 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.empty-icon {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 24px;
    color: #475569;
    margin: 0 0 10px 0;
    font-weight: 600;
}

.empty-text {
    font-size: 14px;
    color: #94a3b8;
    margin: 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .courses-main-content {
        padding: 15px;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .list-header, .list-item {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .control-section {
        flex-direction: column;
    }
    
    .search-box {
        min-width: 100%;
    }
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
</style>';

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
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/teacher_courses.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-graduation-cap sidebar-icon"></i><span class="sidebar-text">Gradebook</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Courses section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">COURSES</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/all_courses.php" class="sidebar-link"><i class="fa fa-th-large sidebar-icon"></i><span class="sidebar-text">All Courses</span></a></li>';
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

// Main content area next to sidebar
echo '<div class="teacher-main-content">';

// Start Dashboard HTML
echo '<div class="all-courses-dashboard">';

// Main Content
echo '<main class="courses-main-content">';

// Top Header
echo '<div class="courses-top-header">';
echo '<div class="header-content">';
echo '<h1 class="header-title"><i class="fa fa-university"></i> All Courses</h1>';
echo '<p class="header-subtitle">Browse and manage all courses in the system</p>';
echo '</div>';
echo '</div>';

// Statistics Overview
echo '<div class="stats-overview">';

echo '<div class="stat-card">';
echo '<div class="stat-icon"><i class="fa fa-book"></i></div>';
echo '<div class="stat-label">Total Courses</div>';
echo '<div class="stat-value">' . $total_courses . '</div>';
echo '</div>';

echo '<div class="stat-card">';
echo '<div class="stat-icon"><i class="fa fa-users"></i></div>';
echo '<div class="stat-label">Total Students</div>';
echo '<div class="stat-value">' . number_format($total_students) . '</div>';
echo '</div>';

echo '<div class="stat-card">';
echo '<div class="stat-icon"><i class="fa fa-check-circle"></i></div>';
echo '<div class="stat-label">Active Courses</div>';
echo '<div class="stat-value">' . $active_courses . '</div>';
echo '</div>';

echo '<div class="stat-card">';
echo '<div class="stat-icon"><i class="fa fa-tasks"></i></div>';
echo '<div class="stat-label">Total Activities</div>';
echo '<div class="stat-value">' . number_format($total_activities) . '</div>';
echo '</div>';

echo '</div>'; // stats-overview

// Control Panel
echo '<div class="control-panel">';

// Search and Filters
echo '<div class="control-section">';
echo '<div class="search-box">';
echo '<i class="fa fa-search search-icon"></i>';
echo '<input type="text" class="search-input" id="searchInput" placeholder="Search courses by name, code, or description..." value="' . htmlspecialchars($search) . '">';
echo '</div>';

echo '<div class="filter-group">';
echo '<select class="filter-select" id="categoryFilter">';
echo '<option value="0">All Categories</option>';
foreach ($categories as $category) {
    $selected = ($category_filter == $category->id) ? 'selected' : '';
    echo '<option value="' . $category->id . '" ' . $selected . '>' . htmlspecialchars($category->name) . '</option>';
}
echo '</select>';

echo '<select class="filter-select" id="sortFilter">';
echo '<option value="name" ' . ($sort == 'name' ? 'selected' : '') . '>Sort by Name</option>';
echo '<option value="newest" ' . ($sort == 'newest' ? 'selected' : '') . '>Sort by Newest</option>';
echo '<option value="oldest" ' . ($sort == 'oldest' ? 'selected' : '') . '>Sort by Oldest</option>';
echo '<option value="students" ' . ($sort == 'students' ? 'selected' : '') . '>Sort by Students</option>';
echo '</select>';

echo '<div class="view-toggle">';
echo '<button class="view-btn ' . ($view == 'grid' ? 'active' : '') . '" id="gridViewBtn" data-view="grid">';
echo '<i class="fa fa-th"></i>';
echo '</button>';
echo '<button class="view-btn ' . ($view == 'list' ? 'active' : '') . '" id="listViewBtn" data-view="list">';
echo '<i class="fa fa-list"></i>';
echo '</button>';
echo '</div>';

echo '</div>'; // filter-group
echo '</div>'; // control-section

echo '</div>'; // control-panel

// Courses Display
if (empty($courses)) {
    echo '<div class="empty-state">';
    echo '<div class="empty-icon"><i class="fa fa-inbox"></i></div>';
    echo '<h3 class="empty-title">No Courses Found</h3>';
    echo '<p class="empty-text">Try adjusting your search or filter criteria</p>';
    echo '</div>';
} else {
    // Grid View
    if ($view == 'grid') {
        echo '<div class="courses-grid" id="coursesGrid">';
        
        foreach ($courses as $course) {
            $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
            
            echo '<div class="course-card" onclick="window.location.href=\'' . $course_url . '\'">';
            
            // Course Header
            echo '<div class="course-header">';
            echo '<div class="course-status ' . ($course->visible ? 'status-active' : 'status-hidden') . '">';
            echo $course->visible ? 'Active' : 'Hidden';
            echo '</div>';
            echo '<div class="course-icon"><i class="fa fa-graduation-cap"></i></div>';
            echo '<h3 class="course-title">' . htmlspecialchars($course->fullname) . '</h3>';
            echo '<p class="course-code">' . htmlspecialchars($course->shortname) . '</p>';
            echo '</div>';
            
            // Course Body
            echo '<div class="course-body">';
            
            if (!empty($course->categoryname)) {
                echo '<span class="course-category"><i class="fa fa-tag"></i> ' . htmlspecialchars($course->categoryname) . '</span>';
            }
            
            $summary = strip_tags($course->summary);
            if (!empty($summary)) {
                echo '<p class="course-summary">' . htmlspecialchars(substr($summary, 0, 150)) . '...</p>';
            } else {
                echo '<p class="course-summary">No description available</p>';
            }
            
            // Course Meta
            echo '<div class="course-meta">';
            
            echo '<div class="meta-item">';
            echo '<div class="meta-icon"><i class="fa fa-users"></i></div>';
            echo '<span class="meta-value">' . $course->enrolled_count . '</span>';
            echo '<span class="meta-label">Students</span>';
            echo '</div>';
            
            echo '<div class="meta-item">';
            echo '<div class="meta-icon"><i class="fa fa-tasks"></i></div>';
            echo '<span class="meta-value">' . $course->activity_count . '</span>';
            echo '<span class="meta-label">Activities</span>';
            echo '</div>';
            
            echo '<div class="meta-item">';
            echo '<div class="meta-icon"><i class="fa fa-calendar"></i></div>';
            echo '<span class="meta-value">' . date('Y', $course->timecreated) . '</span>';
            echo '<span class="meta-label">Created</span>';
            echo '</div>';
            
            echo '</div>'; // course-meta
            echo '</div>'; // course-body
            echo '</div>'; // course-card
        }
        
        echo '</div>'; // courses-grid
    } else {
        // List View
        echo '<div class="courses-list">';
        
        echo '<div class="list-header">';
        echo '<div>Course</div>';
        echo '<div>Category</div>';
        echo '<div>Students</div>';
        echo '<div>Activities</div>';
        echo '<div>Status</div>';
        echo '<div>Action</div>';
        echo '</div>';
        
        foreach ($courses as $course) {
            $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
            
            echo '<div class="list-item">';
            
            echo '<div class="list-course-info">';
            echo '<div class="list-course-icon"><i class="fa fa-graduation-cap"></i></div>';
            echo '<div class="list-course-details">';
            echo '<h4>' . htmlspecialchars($course->fullname) . '</h4>';
            echo '<p>' . htmlspecialchars($course->shortname) . '</p>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="list-value">' . htmlspecialchars($course->categoryname ?: 'N/A') . '</div>';
            echo '<div class="list-value">' . $course->enrolled_count . '</div>';
            echo '<div class="list-value">' . $course->activity_count . '</div>';
            
            echo '<div class="list-value">';
            echo '<span class="course-status ' . ($course->visible ? 'status-active' : 'status-hidden') . '">';
            echo $course->visible ? 'Active' : 'Hidden';
            echo '</span>';
            echo '</div>';
            
            echo '<div>';
            echo '<button class="action-btn" onclick="window.location.href=\'' . $course_url . '\'">View</button>';
            echo '</div>';
            
            echo '</div>'; // list-item
        }
        
        echo '</div>'; // courses-list
    }
}

echo '</main>'; // courses-main-content
echo '</div>'; // all-courses-dashboard
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper

// JavaScript for Interactivity
echo '<script>
// Real-time Search
const searchInput = document.getElementById("searchInput");
if (searchInput) {
    searchInput.addEventListener("input", debounce(function() {
        applyFilters();
    }, 500));
}

// Category Filter
const categoryFilter = document.getElementById("categoryFilter");
if (categoryFilter) {
    categoryFilter.addEventListener("change", function() {
        applyFilters();
    });
}

// Sort Filter
const sortFilter = document.getElementById("sortFilter");
if (sortFilter) {
    sortFilter.addEventListener("change", function() {
        applyFilters();
    });
}

// View Toggle
const gridViewBtn = document.getElementById("gridViewBtn");
const listViewBtn = document.getElementById("listViewBtn");

if (gridViewBtn) {
    gridViewBtn.addEventListener("click", function() {
        applyView("grid");
    });
}

if (listViewBtn) {
    listViewBtn.addEventListener("click", function() {
        applyView("list");
    });
}

// Apply Filters Function
function applyFilters() {
    const search = searchInput.value;
    const category = categoryFilter.value;
    const sort = sortFilter.value;
    const view = document.querySelector(".view-btn.active").dataset.view;
    
    const url = new URL(window.location.href);
    url.searchParams.set("search", search);
    url.searchParams.set("category", category);
    url.searchParams.set("sort", sort);
    url.searchParams.set("view", view);
    
    window.location.href = url.toString();
}

// Apply View Function
function applyView(view) {
    const url = new URL(window.location.href);
    url.searchParams.set("view", view);
    window.location.href = url.toString();
}

// Debounce Function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize
console.log("All Courses Dashboard initialized");
console.log("Total courses loaded: ' . $total_courses . '");
</script>';

echo $OUTPUT->footer();

