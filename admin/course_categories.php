<?php
/**
 * Course Categories & Courses Management Page
 * Two-panel interface for managing course categories and courses
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get current user
global $USER, $DB, $OUTPUT;

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_courses_by_category':
            $category_id = intval($_GET['category_id']);
            $courses = $DB->get_records('course', ['category' => $category_id, 'visible' => 1], 'fullname ASC');
            echo json_encode(['status' => 'success', 'courses' => array_values($courses)]);
            exit;
            
        case 'delete_course':
            $course_id = intval($_GET['course_id']);
            if ($course_id > 1) {
                $course = $DB->get_record('course', ['id' => $course_id]);
                if ($course) {
                    $course->visible = 0;
                    $course->timemodified = time();
                    if ($DB->update_record('course', $course)) {
                        echo json_encode(['status' => 'success', 'message' => 'Course deleted successfully']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to delete course']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete system course']);
            }
            exit;
            
        case 'delete_category':
            $category_id = intval($_GET['category_id']);
            if ($category_id > 0) {
                // Check if category has courses
                $course_count = $DB->count_records('course', ['category' => $category_id]);
                if ($course_count > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Cannot delete category with existing courses']);
                } else {
                    if ($DB->delete_records('course_categories', ['id' => $category_id])) {
                        echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to delete category']);
                    }
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid category ID']);
            }
            exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_course':
                $fullname = trim($_POST['fullname']);
                $shortname = trim($_POST['shortname']);
                $summary = trim($_POST['summary']);
                $category_id = intval($_POST['category_id']);
                $course_format = trim($_POST['course_format']) ?: 'topics';
                $start_date = !empty($_POST['start_date']) ? strtotime($_POST['start_date']) : time();
                $end_date = !empty($_POST['end_date']) ? strtotime($_POST['end_date']) : 0;
                
                if ($fullname && $shortname && $category_id) {
                    $course = new stdClass();
                    $course->fullname = $fullname;
                    $course->shortname = $shortname;
                    $course->summary = $summary;
                    $course->category = $category_id;
                    $course->format = $course_format;
                    $course->numsections = 1;
                    $course->startdate = $start_date;
                    $course->enddate = $end_date;
                    $course->timecreated = time();
                    $course->timemodified = time();
                    $course->visible = 1;
                    
                    if ($DB->insert_record('course', $course)) {
                        $success_message = "Course created successfully!";
                        $message_type = "success";
                    } else {
                        $success_message = "Failed to create course. Please try again.";
                        $message_type = "error";
                    }
                } else {
                    $success_message = "Please fill in all required fields.";
                    $message_type = "error";
                }
                break;
                
            case 'create_category':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $parent_category = intval($_POST['parent_category']);
                
                if ($name) {
                    $category = new stdClass();
                    $category->name = $name;
                    $category->description = $description;
                    $category->parent = $parent_category;
                    $category->sortorder = 0;
                    $category->timecreated = time();
                    $category->timemodified = time();
                    $category->visible = 1;
                    
                    if ($DB->insert_record('course_categories', $category)) {
                        $success_message = "Category created successfully!";
                        $message_type = "success";
                    } else {
                        $success_message = "Failed to create category. Please try again.";
                        $message_type = "error";
                    }
                } else {
                    $success_message = "Please enter category name.";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all categories
$categories = $DB->get_records('course_categories', ['visible' => 1], 'name ASC');

// Get category counts
$category_counts = [];
foreach ($categories as $category) {
    $count = $DB->count_records('course', ['category' => $category->id, 'visible' => 1]);
    $category_counts[$category->id] = $count;
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/course_categories.php');
$PAGE->set_title('Course Categories & Courses');
$PAGE->set_heading('Course Categories & Courses');

echo $OUTPUT->header();

// Admin Sidebar Navigation
echo "<div class='admin-sidebar'>";
echo "<div class='sidebar-content'>";
echo "<!-- DASHBOARD Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>DASHBOARD</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/my/' class='sidebar-link'>";
echo "<i class='fa fa-th-large sidebar-icon'></i>";
echo "<span class='sidebar-text'>Admin Dashboard</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/admin/search.php' class='sidebar-link'>";
echo "<i class='fa fa-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>Site Administration</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-users sidebar-icon'></i>";
echo "<span class='sidebar-text'>Community</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/enrollments.php' class='sidebar-link'>";
echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
echo "<span class='sidebar-text'>Enrollments</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- TEACHERS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>TEACHERS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/teachers_list.php' class='sidebar-link'>";
echo "<i class='fa fa-users sidebar-icon'></i>";
echo "<span class='sidebar-text'>Teachers</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-medal sidebar-icon'></i>";
echo "<span class='sidebar-text'>Master Trainers</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- COURSES & PROGRAMS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>COURSES & PROGRAMS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item active'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/courses.php' class='sidebar-link'>";
echo "<i class='fa fa-book sidebar-icon'></i>";
echo "<span class='sidebar-text'>Courses & Programs</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
echo "<span class='sidebar-text'>Certifications</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-clipboard-list sidebar-icon'></i>";
echo "<span class='sidebar-text'>Assessments</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-school sidebar-icon'></i>";
echo "<span class='sidebar-text'>Schools</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- INSIGHTS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>INSIGHTS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/local/edwiserreports/index.php' class='sidebar-link'>";
echo "<i class='fa fa-chart-bar sidebar-icon'></i>";
echo "<span class='sidebar-text'>Analytics</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-chart-line sidebar-icon'></i>";
echo "<span class='sidebar-text'>Predictive Models</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-file-alt sidebar-icon'></i>";
echo "<span class='sidebar-text'>Reports</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-map sidebar-icon'></i>";
echo "<span class='sidebar-text'>Competencies Map</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- SETTINGS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>SETTINGS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/user_profile_management.php' class='sidebar-link'>";
echo "<i class='fa fa-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>System Settings</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/users_management_dashboard.php' class='sidebar-link'>";
echo "<i class='fa fa-user-friends sidebar-icon'></i>";
echo "<span class='sidebar-text'>User Management</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-users-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>Cohort Navigation</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";
echo "</div>";
echo "</div>";

// Sidebar toggle button for mobile
echo "<button class='sidebar-toggle' onclick='toggleSidebar()' aria-label='Toggle sidebar'>";
echo "<i class='fa fa-bars'></i>";
echo "</button>";

// Main content wrapper
echo "<div class='admin-main-content'>";
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #fef7f7 0%, #f0f9ff 50%, #f0fdf4 100%);
    min-height: 100vh;
    overflow-x: hidden;
}

/* Admin Sidebar Navigation - Sticky on all pages */
.admin-sidebar {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: white;
    border-right: 1px solid #e9ecef;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    will-change: transform;
    backface-visibility: hidden;
}

.admin-sidebar .sidebar-content {
    padding: 6rem 0 2rem 0;
}

.admin-sidebar .sidebar-section {
    margin-bottom: 2rem;
}

.admin-sidebar .sidebar-category {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1rem;
    padding: 0 2rem;
    margin-top: 0;
}

.admin-sidebar .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-sidebar .sidebar-item {
    margin-bottom: 0.25rem;
}

.admin-sidebar .sidebar-link {
    display: flex;
    align-items: center;
    padding: 1rem 2rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 500;
    font-size: 0.95rem;
}

.admin-sidebar .sidebar-link:hover {
    background: #f8f9fa;
    color: #2196F3;
    padding-left: 2.5rem;
}

.admin-sidebar .sidebar-item.active .sidebar-link {
    background: linear-gradient(90deg, rgba(33, 150, 243, 0.1) 0%, transparent 100%);
    color: #2196F3;
    border-left: 4px solid #2196F3;
    font-weight: 600;
}

.admin-sidebar .sidebar-icon {
    margin-right: 1rem;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

/* Main content area with sidebar - FULL SCREEN */
.admin-main-content {
    position: fixed;
    top: 0;
    left: 280px;
    width: calc(100vw - 280px);
    height: 100vh;
    background-color: #ffffff;
    overflow-y: auto;
    z-index: 99;
    will-change: transform;
    backface-visibility: hidden;
    padding-top: 80px;
}

/* Sidebar toggle button for mobile */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: #2196F3;
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: #1976D2;
    transform: scale(1.1);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        transition: left 0.3s ease;
    }
    
    .admin-sidebar.sidebar-open {
        left: 0;
    }
    
    .admin-main-content {
        position: relative;
        left: 0;
        width: 100vw;
        height: auto;
        min-height: 100vh;
        padding-top: 20px;
    }
    
    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

<style>
/* Course Categories Page Styles */
.categories-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.categories-header {
    text-align: center;
    margin-bottom: 40px;
    color: white;
    position: relative;
}

.categories-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    animation: titleGlow 2s ease-in-out infinite alternate;
}

.categories-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 30px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    opacity: 0.8;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.3s ease;
}

.breadcrumb a:hover {
    opacity: 0.7;
}

.breadcrumb-separator {
    color: rgba(255, 255, 255, 0.6);
}

.header-actions {
    position: absolute;
    top: 0;
    right: 0;
    display: flex;
    gap: 15px;
}

.header-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

@keyframes titleGlow {
    from { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 20px rgba(255,255,255,0.3); }
    to { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 30px rgba(255,255,255,0.6); }
}

/* Two Panel Layout */
.panels-container {
    display: flex;
    gap: 30px;
    height: 70vh;
    min-height: 600px;
}

.panel {
    flex: 1;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.panel-header {
    padding: 25px 30px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header.purple {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
}

.panel-header.green {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
}

.panel-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.panel-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin: 5px 0 0 0;
}

.panel-action-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.panel-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.panel-content {
    flex: 1;
    padding: 20px 30px;
    overflow-y: auto;
}

/* Categories List */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.category-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.category-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.category-item.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.category-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.category-item.selected .category-icon {
    background: rgba(255, 255, 255, 0.2);
}

.category-info {
    flex: 1;
}

.category-name {
    font-weight: 600;
    font-size: 1rem;
    margin: 0 0 5px 0;
}

.category-count {
    font-size: 0.85rem;
    opacity: 0.7;
    margin: 0;
}

.category-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.category-item:hover .category-actions {
    opacity: 1;
}

.category-action-btn {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.edit-btn {
    background: #17a2b8;
    color: white;
}

.edit-btn:hover {
    background: #138496;
}

.move-btn {
    background: #6c757d;
    color: white;
}

.move-btn:hover {
    background: #5a6268;
}

.delete-btn {
    background: #dc3545;
    color: white;
}

.delete-btn:hover {
    background: #c82333;
}

/* Courses List */
.courses-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.course-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.course-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.course-checkbox {
    width: 18px;
    height: 18px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
}

.course-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.course-info {
    flex: 1;
}

.course-title {
    font-weight: 600;
    font-size: 1rem;
    margin: 0 0 5px 0;
    color: #333;
}

.course-name {
    font-size: 0.85rem;
    color: #667eea;
    margin: 0 0 8px 0;
    font-weight: 500;
}

.course-dates {
    display: flex;
    gap: 20px;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 8px;
}

.course-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.course-tag {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.tag-topics {
    background: #e9ecef;
    color: #495057;
}

.tag-visible {
    background: #d4edda;
    color: #155724;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    color: #6c757d;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #495057;
}

.empty-description {
    font-size: 0.9rem;
    opacity: 0.8;
    max-width: 300px;
    line-height: 1.5;
}

/* Sort Controls */
.sort-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.sort-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #495057;
}

.sort-select {
    padding: 6px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.85rem;
    background: white;
    cursor: pointer;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 20px;
    width: 90%;
    max-width: 900px;
    height: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
    position: relative;
    top: 45%;
    transform: translateY(-50%);
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-50%) scale(0.9); }
    to { opacity: 1; transform: translateY(-50%) scale(1); }
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-modal-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.category-modal-header {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.modal-header-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.modal-header-text h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.modal-header-text p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.close:hover {
    opacity: 0.7;
}

.modal-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 12px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 12px;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.course-create-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.course-create-btn:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

.category-create-btn {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
}

.category-create-btn:hover {
    background: linear-gradient(135deg, #5a32a3 0%, #d63384 100%);
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Message Styles */
.message {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 500;
    animation: slideInDown 0.5s ease-out;
}

.message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .categories-container {
        padding: 15px;
    }
    
    .categories-header h1 {
        font-size: 2rem;
    }
    
    .header-actions {
        position: static;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .panels-container {
        flex-direction: column;
        height: auto;
        min-height: auto;
    }
    
    .panel {
        min-height: 400px;
    }
    
    .panel-header {
        padding: 20px;
    }
    
    .panel-content {
        padding: 15px 20px;
    }
    
    .category-item,
    .course-item {
        padding: 12px 15px;
    }
    
    .category-actions {
        opacity: 1;
    }
}
</style>

<div class="categories-container">
    <div class="categories-header">
        <div class="header-actions">
            <button class="header-btn" onclick="refreshPage()">
                <i class="fa fa-refresh"></i>
                Refresh
            </button>
        </div>
        <h1>Course Categories & Courses</h1>
        <p>Manage your course structure and organization.</p>
        <div class="breadcrumb">
            <a href="admin_dashboard.php">
                <i class="fa fa-home"></i>
                Dashboard
            </a>
            <span class="breadcrumb-separator">></span>
            <span>Courses & Categories</span>
            <span class="breadcrumb-separator">></span>
            <span>Create Course/Categories</span>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Two Panel Layout -->
    <div class="panels-container">
        <!-- Left Panel: Course Categories -->
        <div class="panel">
            <div class="panel-header purple">
                <div>
                    <h3 class="panel-title">
                        <div class="panel-icon">
                            <i class="fa fa-folder"></i>
                        </div>
                        Course Categories
                    </h3>
                    <p class="panel-subtitle"><?php echo count($categories); ?> categories available</p>
                </div>
                <button class="panel-action-btn" onclick="openModal('createCategoryModal')">
                    <i class="fa fa-plus"></i>
                    Create Category
                </button>
            </div>
            <div class="panel-content">
                <div class="categories-list" id="categoriesList">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item" data-category-id="<?php echo $category->id; ?>" onclick="selectCategory(<?php echo $category->id; ?>)">
                            <div class="category-icon">
                                <i class="fa fa-folder"></i>
                            </div>
                            <div class="category-info">
                                <h4 class="category-name"><?php echo htmlspecialchars($category->name); ?></h4>
                                <p class="category-count"><?php echo $category_counts[$category->id]; ?> courses</p>
                            </div>
                            <div class="category-actions">
                                <button class="category-action-btn edit-btn" onclick="editCategory(<?php echo $category->id; ?>)" title="Edit Category">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="category-action-btn move-btn" onclick="moveCategory(<?php echo $category->id; ?>)" title="Move Category">
                                    <i class="fa fa-arrows"></i>
                                </button>
                                <button class="category-action-btn delete-btn" onclick="deleteCategory(<?php echo $category->id; ?>)" title="Delete Category">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Panel: Courses -->
        <div class="panel">
            <div class="panel-header green">
                <div>
                    <h3 class="panel-title" id="coursesPanelTitle">
                        <div class="panel-icon">
                            <i class="fa fa-book"></i>
                        </div>
                        Select a Category
                    </h3>
                    <p class="panel-subtitle" id="coursesPanelSubtitle">Choose a category to view courses.</p>
                </div>
                <button class="panel-action-btn" onclick="openModal('createCourseModal')" id="createCourseBtn" style="display: none;">
                    <i class="fa fa-plus"></i>
                    Create Course
                </button>
            </div>
            <div class="panel-content">
                <div id="coursesContent">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa fa-folder-plus"></i>
                        </div>
                        <h3 class="empty-title">No Category Selected</h3>
                        <p class="empty-description">Select a category from the left panel to view its courses.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Course Modal -->
<div id="createCourseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header course-modal-header">
            <div class="modal-header-content">
                <div class="modal-header-icon">
                    <i class="fa fa-book"></i>
                </div>
                <div class="modal-header-text">
                    <h3>Create New Course</h3>
                    <p id="courseModalSubtitle">In '<span id="selectedCategoryName">Select Category</span>'</p>
                </div>
            </div>
            <span class="close" onclick="closeModal('createCourseModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_course">
                <input type="hidden" id="selectedCategoryId" name="category_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname">Course Name *</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter course name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortname">Short Name *</label>
                        <input type="text" id="shortname" name="shortname" placeholder="Enter short name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category_display">Category</label>
                    <input type="text" id="category_display" readonly value="Select a category first">
                </div>
                
                <div class="form-group">
                    <label for="course_format">Course Format</label>
                    <select id="course_format" name="course_format">
                        <option value="topics">Topics format</option>
                        <option value="weeks">Weekly format</option>
                        <option value="social">Social format</option>
                        <option value="singleactivity">Single activity format</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="summary">Course Summary</label>
                    <textarea id="summary" name="summary" placeholder="Enter course description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createCourseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary course-create-btn">
                    <i class="fa fa-book"></i>
                    Create Course
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Category Modal -->
<div id="createCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header category-modal-header">
            <div class="modal-header-content">
                <div class="modal-header-icon">
                    <i class="fa fa-folder-plus"></i>
                </div>
                <div class="modal-header-text">
                    <h3>Create New Category</h3>
                    <p>Add a new course category</p>
                </div>
            </div>
            <span class="close" onclick="closeModal('createCategoryModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_category">
                
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" placeholder="Enter category name" required>
                </div>
                
                <div class="form-group">
                    <label for="parent_category">Parent Category</label>
                    <select id="parent_category" name="parent_category">
                        <option value="0">Top Level Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>"><?php echo htmlspecialchars($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" placeholder="Enter category description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary category-create-btn">
                    <i class="fa fa-folder-plus"></i>
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedCategoryId = null;

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Category selection
function selectCategory(categoryId) {
    // Remove previous selection
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selection to clicked item
    const categoryItem = document.querySelector(`[data-category-id="${categoryId}"]`);
    categoryItem.classList.add('selected');
    
    selectedCategoryId = categoryId;
    
    // Update course modal with selected category
    updateCourseModalCategory(categoryId);
    
    // Update right panel
    loadCoursesForCategory(categoryId);
}

// Update course modal with selected category
function updateCourseModalCategory(categoryId) {
    const categoryName = document.querySelector(`[data-category-id="${categoryId}"] .category-name`).textContent;
    
    // Update modal subtitle
    document.getElementById('selectedCategoryName').textContent = categoryName;
    
    // Update hidden input and display field
    document.getElementById('selectedCategoryId').value = categoryId;
    document.getElementById('category_display').value = categoryName;
    
    // Enable create course button
    document.getElementById('createCourseBtn').style.display = 'flex';
}

// Load courses for selected category
function loadCoursesForCategory(categoryId) {
    fetch(`?action=get_courses_by_category&category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayCourses(data.courses, categoryId);
            } else {
                console.error('Error loading courses:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Display courses in right panel
function displayCourses(courses, categoryId) {
    const categoryName = document.querySelector(`[data-category-id="${categoryId}"] .category-name`).textContent;
    
    // Update panel header
    document.getElementById('coursesPanelTitle').innerHTML = `
        <div class="panel-icon">
            <i class="fa fa-book"></i>
        </div>
        Courses in "${categoryName}"
    `;
    document.getElementById('coursesPanelSubtitle').textContent = `${courses.length} courses found`;
    document.getElementById('createCourseBtn').style.display = 'flex';
    
    // Update courses content
    const coursesContent = document.getElementById('coursesContent');
    
    if (courses.length === 0) {
        coursesContent.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa fa-book"></i>
                </div>
                <h3 class="empty-title">No Courses Found</h3>
                <p class="empty-description">This category doesn't have any courses yet. Create a new course to get started.</p>
            </div>
        `;
    } else {
        let coursesHtml = `
            <div class="sort-controls">
                <span class="sort-label">Sort by:</span>
                <select class="sort-select" onchange="sortCourses(this.value)">
                    <option value="name">Name</option>
                    <option value="date">Date</option>
                    <option value="enrollments">Enrolments</option>
                </select>
            </div>
            <div class="courses-list" id="coursesList">
        `;
        
        courses.forEach(course => {
            const startDate = new Date(course.startdate * 1000).toLocaleDateString();
            const endDate = course.enddate ? new Date(course.enddate * 1000).toLocaleDateString() : 'No end date';
            
            coursesHtml += `
                <div class="course-item" data-course-id="${course.id}">
                    <input type="checkbox" class="course-checkbox">
                    <div class="course-icon">
                        <i class="fa fa-book"></i>
                    </div>
                    <div class="course-info">
                        <h4 class="course-title">${course.fullname}</h4>
                        <p class="course-name">${course.shortname}</p>
                        <div class="course-dates">
                            <span>Start: ${startDate}</span>
                            <span>End: ${endDate}</span>
                        </div>
                        <div class="course-tags">
                            <span class="course-tag tag-topics">topics</span>
                            <span class="course-tag tag-visible">Visible</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        coursesHtml += '</div>';
        coursesContent.innerHTML = coursesHtml;
    }
}

// Course management functions
function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        fetch(`?action=delete_course&course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove course from display
                    const courseItem = document.querySelector(`[data-course-id="${courseId}"]`);
                    if (courseItem) {
                        courseItem.remove();
                    }
                    showMessage('Course deleted successfully', 'success');
                } else {
                    showMessage(data.message || 'Failed to delete course', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while deleting the course', 'error');
            });
    }
}

// Category management functions
function editCategory(categoryId) {
    // Implement category editing
    console.log('Edit category:', categoryId);
}

function moveCategory(categoryId) {
    // Implement category moving
    console.log('Move category:', categoryId);
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        fetch(`?action=delete_category&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove category from display
                    const categoryItem = document.querySelector(`[data-category-id="${categoryId}"]`);
                    if (categoryItem) {
                        categoryItem.remove();
                    }
                    showMessage('Category deleted successfully', 'success');
                } else {
                    showMessage(data.message || 'Failed to delete category', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while deleting the category', 'error');
            });
    }
}

// Utility functions
function refreshPage() {
    location.reload();
}

function sortCourses(sortBy) {
    // Implement course sorting
    console.log('Sort courses by:', sortBy);
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    
    const container = document.querySelector('.categories-container');
    container.insertBefore(messageDiv, container.querySelector('.panels-container'));
    
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Set default category selection if any categories exist
    const firstCategory = document.querySelector('.category-item');
    if (firstCategory) {
        const categoryId = firstCategory.getAttribute('data-category-id');
        selectCategory(categoryId);
    }
});
</script>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('sidebar-open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('sidebar-open');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
});
</script>

<?php
echo "</div>"; // End admin-main-content
echo $OUTPUT->footer();
?>
