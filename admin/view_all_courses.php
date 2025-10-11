<?php
/**
 * View All Courses Page
 * Displays all courses in a grid layout with course images
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get current user
global $USER, $DB, $OUTPUT;

// Get hierarchical categories structure: Parent → Sub → Courses
try {
    // Get parent categories (categories with parent = 0)
    $parent_categories = $DB->get_records('course_categories', ['visible' => 1, 'parent' => 0], 'name ASC');
    
    // Build hierarchical structure
    $hierarchical_categories = [];
    $all_courses = [];
    
    foreach ($parent_categories as $parent) {
        // Get courses directly under this parent category (no subcategory)
        $parent_courses = $DB->get_records('course', ['category' => $parent->id, 'visible' => 1], 'fullname ASC');
        $parent_course_count = count($parent_courses);
        
        // Add parent courses to all_courses array
        foreach ($parent_courses as $course) {
            $course->parent_category_name = $parent->name;
            $course->subcategory_name = 'Direct'; // Mark as directly under parent
            $course->category_name = $parent->name;
            $course->is_parent_course = true; // Flag to identify courses directly under parent
            
            // Get enrollment count for this course
            $course->enrolled_count = $DB->count_records_sql(
                'SELECT COUNT(DISTINCT ue.userid) 
                 FROM {user_enrolments} ue 
                 JOIN {enrol} e ON e.id = ue.enrolid 
                 WHERE e.courseid = ? AND ue.status = 0', 
                [$course->id]
            );
            
            $all_courses[] = $course;
        }
        
        // Get subcategories for this parent
        $subcategories = $DB->get_records('course_categories', ['visible' => 1, 'parent' => $parent->id], 'name ASC');
        
        $parent_data = [
            'id' => $parent->id,
            'name' => $parent->name,
            'description' => $parent->description,
            'direct_courses' => $parent_courses,
            'direct_course_count' => $parent_course_count,
            'subcategories' => []
        ];
        
        $total_courses = $parent_course_count; // Start with direct courses
        foreach ($subcategories as $subcategory) {
            // Get courses for this subcategory
            $courses = $DB->get_records('course', ['category' => $subcategory->id, 'visible' => 1], 'fullname ASC');
            $course_count = count($courses);
            $total_courses += $course_count;
            
            // Add courses to all_courses array with category info
            foreach ($courses as $course) {
                $course->parent_category_name = $parent->name;
                $course->subcategory_name = $subcategory->name;
                $course->category_name = $subcategory->name;
                $course->is_parent_course = false;
                
                // Get enrollment count for this course
                $course->enrolled_count = $DB->count_records_sql(
                    'SELECT COUNT(DISTINCT ue.userid) 
                     FROM {user_enrolments} ue 
                     JOIN {enrol} e ON e.id = ue.enrolid 
                     WHERE e.courseid = ? AND ue.status = 0', 
                    [$course->id]
                );
                
                $all_courses[] = $course;
            }
            
            $parent_data['subcategories'][] = [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'description' => $subcategory->description,
                'course_count' => $course_count,
                'courses' => $courses
            ];
        }
        
        $parent_data['total_courses'] = $total_courses;
        $hierarchical_categories[] = $parent_data;
    }
} catch (Exception $e) {
    // Handle database errors
    echo "Database error: " . $e->getMessage();
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/view_all_courses.php');
$PAGE->set_title('View All Courses');
$PAGE->set_heading('View All Courses');

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
echo "<i class='fa fa-trophy sidebar-icon'></i>";
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
echo "<i class='fa fa-file-alt sidebar-icon'></i>";
echo "<span class='sidebar-text'>Courses & Programs</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-certificate sidebar-icon'></i>";
echo "<span class='sidebar-text'>Certifications</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-clipboard-check sidebar-icon'></i>";
echo "<span class='sidebar-text'>Assessments</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/schools_management.php' class='sidebar-link'>";
echo "<i class='fa fa-building sidebar-icon'></i>";
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
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-chart-bar sidebar-icon'></i>";
echo "<span class='sidebar-text'>Analytics</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-box sidebar-icon'></i>";
echo "<span class='sidebar-text'>Predictive Models</span>";
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

// Main content area with sidebar
echo "<div class='admin-main-content'>";

?>

<style>
/* Sidebar Styles */
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
    padding: 0.75rem 2rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.admin-sidebar .sidebar-link:hover {
    background-color: #f8f9fa;
    color: #2c3e50;
    text-decoration: none;
    border-left-color: #667eea;
}

.admin-sidebar .sidebar-icon {
    width: 20px;
    height: 20px;
    margin-right: 1rem;
    font-size: 1rem;
    color: #6c757d;
    text-align: center;
}

.admin-sidebar .sidebar-text {
    font-size: 0.9rem;
    font-weight: 500;
}

.admin-sidebar .sidebar-item.active .sidebar-link {
    background-color: #e3f2fd;
    color: #1976d2;
    border-left-color: #1976d2;
}

.admin-sidebar .sidebar-item.active .sidebar-icon {
    color: #1976d2;
}

/* Scrollbar styling */
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
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
    padding-top: 80px; /* Add padding to account for topbar */
}

/* Mobile responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .admin-sidebar.sidebar-open {
        transform: translateX(0);
    }
    
    .admin-main-content {
        left: 0;
        width: 100vw;
    }
    
    .sidebar-toggle {
        display: block;
    }
    
    .category-filter-section {
        padding: 15px;
    }
    
    .filter-group-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .courses-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .courses-controls {
        width: 100%;
    }
    
    .search-box {
        width: 100%;
        min-width: auto;
    }
}

.sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: #667eea;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
}

/* View All Courses Page Styles */
.view-courses-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.back-button {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-button:hover {
    background: linear-gradient(135deg, #c3e6cb 0%, #b8dacc 100%);
    transform: translateX(-2px);
}

/* Courses Grid */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.course-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #a8d8ea;
}

.course-thumbnail {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    position: relative;
    overflow: hidden;
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.course-thumbnail .fallback-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.7;
}

.course-content {
    padding: 20px;
}

.course-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 8px 0;
    line-height: 1.4;
}

.course-category {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.course-category i {
    color: #a8d8ea;
}

.course-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.enrolled-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: white;
    background: rgba(21, 87, 36, 0.9);
    backdrop-filter: blur(10px);
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.enrolled-count i {
    color: white;
    font-size: 0.9rem;
}

.course-description {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0 0 15px 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-actions {
    display: flex;
    gap: 10px;
}

.view-course-btn {
    flex: 1;
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    color: #2c3e50;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.view-course-btn:hover {
    background: linear-gradient(135deg, #98c8da 0%, #b2d9eb 100%);
    transform: translateY(-1px);
}

.manage-course-btn {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.manage-course-btn:hover {
    background: linear-gradient(135deg, #c3e6cb 0%, #b8dacc 100%);
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-state .empty-icon {
    font-size: 4rem;
    color: #a8d8ea;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2c3e50;
    margin: 0 0 10px 0;
    font-size: 1.5rem;
}

.empty-state p {
    color: #6c757d;
    margin: 0;
    font-size: 1rem;
}

/* Stats Bar */
.stats-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

/* Section Titles */
.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #a8d8ea;
}

/* Category Filter Dropdown Section */
.category-filter-section {
    margin-bottom: 30px;
    padding: 25px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.filter-group-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.filter-dropdown-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.filter-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label i {
    color: #a8d8ea;
    font-size: 1rem;
}

.category-filter-dropdown {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: #2c3e50;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    outline: none;
}

.category-filter-dropdown:hover:not(:disabled) {
    border-color: #a8d8ea;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.category-filter-dropdown:focus {
    border-color: #a8d8ea;
    box-shadow: 0 0 0 3px rgba(168, 216, 234, 0.2);
    background: white;
}

.category-filter-dropdown:disabled {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.category-filter-dropdown option {
    padding: 10px;
    font-size: 0.9rem;
}

.category-filter-dropdown option[style*="display: none"] {
    display: none !important;
}

/* All Courses Section */
.all-courses-section {
    margin-bottom: 30px;
}

/* Courses Header */
.courses-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.courses-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.search-box input:focus {
    outline: none;
    border-color: #a8d8ea;
    box-shadow: 0 0 0 3px rgba(168, 216, 234, 0.1);
}

.search-box i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 0.9rem;
}

.view-options {
    display: flex;
    gap: 5px;
    background: #f8f9fa;
    padding: 4px;
    border-radius: 8px;
}

.view-btn {
    background: transparent;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6c757d;
}

.view-btn:hover {
    background: #e9ecef;
    color: #2c3e50;
}

.view-btn.active {
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    color: #2c3e50;
}

/* Courses Info */
.courses-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#coursesCount {
    font-weight: 600;
    color: #2c3e50;
}

.sort-options select {
    padding: 8px 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    color: #2c3e50;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sort-options select:focus {
    outline: none;
    border-color: #a8d8ea;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    flex-wrap: wrap;
    gap: 15px;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.9rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-btn {
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    color: #2c3e50;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #98c8da 0%, #b2d9eb 100%);
    transform: translateY(-1px);
}

.pagination-btn:disabled {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    transform: none;
}

.pagination-numbers {
    display: flex;
    gap: 5px;
}

.page-number {
    background: white;
    color: #2c3e50;
    border: 2px solid #e9ecef;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 40px;
    text-align: center;
}

.page-number:hover {
    border-color: #a8d8ea;
    background: #f8f9fa;
}

.page-number.active {
    background: linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%);
    border-color: #a8d8ea;
    color: #2c3e50;
}

/* List View Styles */
.courses-grid.list-view {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.courses-grid.list-view .course-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 20px;
}

.courses-grid.list-view .course-thumbnail {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    margin-right: 20px;
}

.courses-grid.list-view .course-content {
    flex: 1;
    padding: 0;
}

.courses-grid.list-view .course-actions {
    flex-shrink: 0;
    margin-left: 20px;
}

.stats-item {
    text-align: center;
}

/* Highlight animation for course jump */
@keyframes highlight {
    0% {
        box-shadow: 0 0 0 0 rgba(168, 216, 234, 0.7);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 20px 10px rgba(168, 216, 234, 0.3);
        transform: scale(1.02);
    }
    100% {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transform: scale(1);
    }
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.stats-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}
</style>

<div class="view-courses-container">
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="page-icon">
                <i class="fa fa-list"></i>
            </div>
            <h1 class="page-title">All Courses</h1>
        </div>
        <button class="back-button" onclick="window.history.back()">
            <i class="fa fa-arrow-left"></i>
            Back
        </button>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stats-item">
            <p class="stats-number"><?php echo count($all_courses); ?></p>
            <p class="stats-label">Total Courses</p>
        </div>
        <div class="stats-item">
            <p class="stats-number"><?php echo count($hierarchical_categories); ?></p>
            <p class="stats-label">Parent Categories</p>
        </div>
        <div class="stats-item">
            <p class="stats-number"><?php echo array_sum(array_map(function($parent) { return count($parent['subcategories']); }, $hierarchical_categories)); ?></p>
            <p class="stats-label">Subcategories</p>
        </div>
        <div class="stats-item">
            <p class="stats-number"><?php echo count(array_filter($all_courses, function($course) { return !empty($course->summary); })); ?></p>
            <p class="stats-label">With Description</p>
        </div>
    </div>

    <?php if (count($hierarchical_categories) > 0): ?>
        <!-- Three-Level Filter Dropdowns -->
        <div class="category-filter-section">
            <div class="filter-group-container">
                <!-- Parent Category Dropdown -->
                <div class="filter-dropdown-group">
                    <label for="parentCategoryFilter" class="filter-label">
                        <i class="fa fa-folder-open"></i>
                        Parent Category
                    </label>
                    <select id="parentCategoryFilter" class="category-filter-dropdown" onchange="updateSubcategoryDropdown()">
                        <option value="all">All Parent Categories</option>
                        <?php foreach ($hierarchical_categories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" data-parent-id="<?php echo $parent['id']; ?>">
                                <?php echo htmlspecialchars($parent['name']); ?> (<?php echo $parent['total_courses']; ?> courses)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subcategory Dropdown -->
                <div class="filter-dropdown-group">
                    <label for="subcategoryFilter" class="filter-label">
                        <i class="fa fa-folder"></i>
                        Subcategory
                    </label>
                    <select id="subcategoryFilter" class="category-filter-dropdown" onchange="updateCourseDropdown()" disabled>
                        <option value="all">All Subcategories</option>
                        <?php foreach ($hierarchical_categories as $parent): ?>
                            <?php if ($parent['direct_course_count'] > 0): ?>
                                <option value="direct-<?php echo $parent['id']; ?>" 
                                        data-parent-id="<?php echo $parent['id']; ?>"
                                        data-subcategory-id="direct-<?php echo $parent['id']; ?>"
                                        data-is-direct="true"
                                        style="display: none;">
                                    Direct Courses (<?php echo $parent['direct_course_count']; ?> courses)
                                </option>
                            <?php endif; ?>
                            <?php foreach ($parent['subcategories'] as $subcategory): ?>
                                <option value="<?php echo $subcategory['id']; ?>" 
                                        data-parent-id="<?php echo $parent['id']; ?>"
                                        data-subcategory-id="<?php echo $subcategory['id']; ?>"
                                        data-is-direct="false"
                                        style="display: none;">
                                    <?php echo htmlspecialchars($subcategory['name']); ?> (<?php echo $subcategory['course_count']; ?> courses)
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course Dropdown -->
                <div class="filter-dropdown-group">
                    <label for="courseFilter" class="filter-label">
                        <i class="fa fa-book"></i>
                        Course
                    </label>
                    <select id="courseFilter" class="category-filter-dropdown" onchange="jumpToCourse()" disabled>
                        <option value="all">All Courses</option>
                        <?php foreach ($all_courses as $course): ?>
                            <option value="<?php echo $course->id; ?>"
                                    data-parent-id="<?php 
                                        foreach ($hierarchical_categories as $parent) {
                                            if ($parent['name'] === $course->parent_category_name) {
                                                echo $parent['id'];
                                                break;
                                            }
                                        }
                                    ?>"
                                    data-subcategory-id="<?php echo isset($course->is_parent_course) && $course->is_parent_course ? 'direct-' . $course->category : $course->category; ?>"
                                    data-course-id="<?php echo $course->id; ?>"
                                    data-is-direct="<?php echo isset($course->is_parent_course) && $course->is_parent_course ? 'true' : 'false'; ?>"
                                    style="display: none;">
                                <?php echo htmlspecialchars($course->fullname); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- All Courses Section -->
        <div class="all-courses-section">
            <div class="courses-header">
                <h2 class="section-title">All Courses</h2>
                <div class="courses-controls">
                    <div class="search-box">
                        <input type="text" id="courseSearch" placeholder="Search courses..." onkeyup="searchCourses()">
                        <i class="fa fa-search"></i>
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" onclick="changeView('grid')" data-view="grid">
                            <i class="fa fa-th"></i>
                        </button>
                        <button class="view-btn" onclick="changeView('list')" data-view="list">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="courses-info">
                <span id="coursesCount"><?php echo count($all_courses); ?> courses found</span>
                <div class="sort-options">
                    <select id="sortCourses" onchange="sortCourses()">
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="category-asc">Category (A-Z)</option>
                        <option value="category-desc">Category (Z-A)</option>
                    </select>
                </div>
            </div>
            
            <div class="courses-grid" id="coursesGrid">
                <?php foreach ($all_courses as $course): 
                    // Get course image using the theme function
                    $courseimage = '';
                    try {
                        $courseimage = theme_remui_kids_get_course_image($course);
                    } catch (Exception $e) {
                        // Fallback to default
                    }
                    
                    // Default gradient colors
                    $gradient_colors = [
                        'linear-gradient(135deg, #a8d8ea 0%, #c2e9fb 100%)',
                        'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)',
                        'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)',
                        'linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%)',
                        'linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%)',
                        'linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%)'
                    ];
                    $gradient_index = $course->id % count($gradient_colors);
                    $default_gradient = $gradient_colors[$gradient_index];
                ?>
                    <div class="course-card" 
                         data-parent-category="<?php echo $course->parent_category_name; ?>" 
                         data-subcategory="<?php echo $course->subcategory_name; ?>"
                         data-parent-id="<?php 
                             foreach ($hierarchical_categories as $parent) {
                                 if ($parent['name'] === $course->parent_category_name) {
                                     echo $parent['id'];
                                     break;
                                 }
                             }
                         ?>"
                         data-subcategory-id="<?php echo isset($course->is_parent_course) && $course->is_parent_course ? 'direct-' . $course->category : $course->category; ?>"
                         data-is-direct="<?php echo isset($course->is_parent_course) && $course->is_parent_course ? 'true' : 'false'; ?>">
                        <div class="course-thumbnail" <?php if (empty($courseimage)): ?>style="background: <?php echo $default_gradient; ?>"<?php endif; ?>>
                            <?php if (!empty($courseimage)): ?>
                                <img src="<?php echo $courseimage; ?>" alt="<?php echo htmlspecialchars($course->fullname); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="fa fa-book fallback-icon" style="display: none;"></i>
                            <?php else: ?>
                                <i class="fa fa-book fallback-icon"></i>
                            <?php endif; ?>
                            <div class="course-badge">
                                <span class="enrolled-count">
                                    <i class="fa fa-users"></i>
                                    <?php echo $course->enrolled_count; ?>
                                </span>
                            </div>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($course->fullname); ?></h3>
                            <p class="course-category">
                                <i class="fa fa-folder"></i>
                                <?php echo htmlspecialchars($course->parent_category_name); ?> → <?php echo htmlspecialchars($course->subcategory_name); ?>
                            </p>
                            <p class="course-description">
                                <?php echo htmlspecialchars($course->summary ?: 'No description available.'); ?>
                            </p>
                            <div class="course-actions">
                                <button class="view-course-btn" onclick="viewCourse(<?php echo $course->id; ?>)">
                                    <i class="fa fa-eye"></i>
                                    View Course
                                </button>
                                <button class="manage-course-btn" onclick="manageCourse(<?php echo $course->id; ?>)">
                                    <i class="fa fa-cog"></i>
                                    Manage
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span id="paginationInfo">Showing 1-12 of <?php echo count($all_courses); ?> courses</span>
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prevBtn" onclick="changePage(-1)" disabled>
                        <i class="fa fa-chevron-left"></i>
                        Previous
                    </button>
                    <div class="pagination-numbers" id="paginationNumbers">
                        <!-- Page numbers will be generated by JavaScript -->
                    </div>
                    <button class="pagination-btn" id="nextBtn" onclick="changePage(1)">
                        Next
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa fa-book"></i>
            </div>
            <h3>No Courses Found</h3>
            <p>There are no courses available at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Function to view course
function viewCourse(courseId) {
    window.location.href = '<?php echo $CFG->wwwroot; ?>/course/view.php?id=' + courseId;
}

// Function to manage course
function manageCourse(courseId) {
    window.location.href = '<?php echo $CFG->wwwroot; ?>/course/edit.php?id=' + courseId;
}

// Global variables for pagination and filtering
let currentPage = 1;
let coursesPerPage = 12;
let currentFilter = 'all';
let currentSearch = '';
let currentSort = 'name-asc';
let currentView = 'grid';

// Function to update subcategory dropdown based on parent category selection
function updateSubcategoryDropdown() {
    const parentDropdown = document.getElementById('parentCategoryFilter');
    const subcategoryDropdown = document.getElementById('subcategoryFilter');
    const courseDropdown = document.getElementById('courseFilter');
    const selectedParentId = parentDropdown.value;
    
    // Reset subcategory and course dropdowns
    subcategoryDropdown.value = 'all';
    courseDropdown.value = 'all';
    courseDropdown.disabled = true;
    
    // Hide all subcategory options first
    const subcategoryOptions = subcategoryDropdown.querySelectorAll('option');
    subcategoryOptions.forEach(option => {
        if (option.value === 'all') {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
    
    if (selectedParentId === 'all') {
        // If "All Parent Categories" is selected, disable subcategory dropdown and show all courses
        subcategoryDropdown.disabled = true;
        currentFilter = 'all';
    } else {
        // Enable subcategory dropdown and show only subcategories for selected parent
        subcategoryDropdown.disabled = false;
        subcategoryOptions.forEach(option => {
            if (option.getAttribute('data-parent-id') === selectedParentId) {
                option.style.display = 'block';
            }
        });
        currentFilter = 'parent-' + selectedParentId;
    }
    
    currentPage = 1;
    updateCoursesDisplay();
    updateSectionTitle();
}

// Function to update course dropdown based on subcategory selection
function updateCourseDropdown() {
    const subcategoryDropdown = document.getElementById('subcategoryFilter');
    const courseDropdown = document.getElementById('courseFilter');
    const selectedSubcategoryId = subcategoryDropdown.value;
    const selectedOption = subcategoryDropdown.options[subcategoryDropdown.selectedIndex];
    const isDirect = selectedOption.getAttribute('data-is-direct') === 'true';
    
    // Reset course dropdown
    courseDropdown.value = 'all';
    
    // Hide all course options first
    const courseOptions = courseDropdown.querySelectorAll('option');
    courseOptions.forEach(option => {
        if (option.value === 'all') {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
    
    if (selectedSubcategoryId === 'all') {
        // If "All Subcategories" is selected, disable course dropdown and show all courses under parent
        courseDropdown.disabled = true;
        const parentDropdown = document.getElementById('parentCategoryFilter');
        // Keep the parent filter to show all courses under this parent (direct + subcategories)
        currentFilter = parentDropdown.value === 'all' ? 'all' : 'parent-' + parentDropdown.value;
    } else {
        // Enable course dropdown and show only courses for selected subcategory
        courseDropdown.disabled = false;
        
        if (isDirect) {
            // Show only direct courses (courses directly under parent)
            courseOptions.forEach(option => {
                if (option.getAttribute('data-is-direct') === 'true' && 
                    option.getAttribute('data-subcategory-id') === selectedSubcategoryId) {
                    option.style.display = 'block';
                }
            });
            // Use direct filter to show only direct courses
            currentFilter = 'direct-' + selectedSubcategoryId;
        } else {
            // Show courses for selected subcategory
            courseOptions.forEach(option => {
                if (option.getAttribute('data-subcategory-id') === selectedSubcategoryId) {
                    option.style.display = 'block';
                }
            });
            currentFilter = 'subcategory-' + selectedSubcategoryId;
        }
    }
    
    currentPage = 1;
    updateCoursesDisplay();
    updateSectionTitle();
}

// Function to jump to a specific course
function jumpToCourse() {
    const courseDropdown = document.getElementById('courseFilter');
    const selectedCourseId = courseDropdown.value;
    
    if (selectedCourseId !== 'all') {
        // Scroll to the course card
        const courseCard = document.querySelector(`.course-card[data-subcategory-id="${document.querySelector('#courseFilter option:checked').getAttribute('data-subcategory-id')}"]`);
        if (courseCard) {
            courseCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            courseCard.style.animation = 'highlight 1s ease';
        }
    }
}

// Function to update section title
function updateSectionTitle() {
    const sectionTitle = document.querySelector('.all-courses-section .section-title');
    const parentDropdown = document.getElementById('parentCategoryFilter');
    const subcategoryDropdown = document.getElementById('subcategoryFilter');
    const courseDropdown = document.getElementById('courseFilter');
    
    if (courseDropdown.value !== 'all') {
        const courseName = courseDropdown.options[courseDropdown.selectedIndex].text;
        sectionTitle.textContent = `Course: ${courseName}`;
    } else if (subcategoryDropdown.value !== 'all') {
        const subcategoryName = subcategoryDropdown.options[subcategoryDropdown.selectedIndex].text.split('(')[0].trim();
        sectionTitle.textContent = `Courses in ${subcategoryName}`;
    } else if (parentDropdown.value !== 'all') {
        const parentName = parentDropdown.options[parentDropdown.selectedIndex].text.split('(')[0].trim();
        sectionTitle.textContent = `Courses in ${parentName}`;
    } else {
        sectionTitle.textContent = 'All Courses';
    }
}

// Function to search courses
function searchCourses() {
    currentSearch = document.getElementById('courseSearch').value.toLowerCase();
    currentPage = 1;
    updateCoursesDisplay();
}

// Function to sort courses
function sortCourses() {
    currentSort = document.getElementById('sortCourses').value;
    currentPage = 1;
    updateCoursesDisplay();
}

// Function to change view (grid/list)
function changeView(viewType) {
    currentView = viewType;
    
    // Update view buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-view="${viewType}"]`).classList.add('active');
    
    // Update courses grid class
    const coursesGrid = document.getElementById('coursesGrid');
    if (viewType === 'list') {
        coursesGrid.classList.add('list-view');
    } else {
        coursesGrid.classList.remove('list-view');
    }
    
    updateCoursesDisplay();
}

// Function to change page
function changePage(direction) {
    const totalPages = Math.ceil(getVisibleCourses().length / coursesPerPage);
    
    if (direction === -1 && currentPage > 1) {
        currentPage--;
    } else if (direction === 1 && currentPage < totalPages) {
        currentPage++;
    }
    
    updateCoursesDisplay();
}

// Function to go to specific page
function goToPage(page) {
    const totalPages = Math.ceil(getVisibleCourses().length / coursesPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        updateCoursesDisplay();
    }
}

// Function to get visible courses based on current filters and search
function getVisibleCourses() {
    const allCards = document.querySelectorAll('.course-card');
    const visibleCards = [];
    
    allCards.forEach(card => {
        let isVisible = true;
        
        // Apply filter
        if (currentFilter !== 'all') {
            if (currentFilter.startsWith('parent-')) {
                const parentId = currentFilter.replace('parent-', '');
                const cardParentId = card.getAttribute('data-parent-id');
                isVisible = cardParentId === parentId;
            } else if (currentFilter.startsWith('direct-')) {
                // Filter for direct courses under parent category
                const directFilter = currentFilter.replace('direct-', '');
                const cardSubcategoryId = card.getAttribute('data-subcategory-id');
                const cardIsDirect = card.getAttribute('data-is-direct') === 'true';
                isVisible = cardSubcategoryId === directFilter && cardIsDirect;
            } else if (currentFilter.startsWith('subcategory-')) {
                const subcategoryId = currentFilter.replace('subcategory-', '');
                const cardSubcategoryId = card.getAttribute('data-subcategory-id');
                isVisible = cardSubcategoryId === subcategoryId;
            }
        }
        
        // Apply search
        if (isVisible && currentSearch) {
            const title = card.querySelector('.course-title').textContent.toLowerCase();
            const category = card.querySelector('.course-category').textContent.toLowerCase();
            const description = card.querySelector('.course-description').textContent.toLowerCase();
            
            isVisible = title.includes(currentSearch) || 
                       category.includes(currentSearch) || 
                       description.includes(currentSearch);
        }
        
        if (isVisible) {
            visibleCards.push(card);
        }
    });
    
    // Sort courses
    visibleCards.sort((a, b) => {
        const titleA = a.querySelector('.course-title').textContent;
        const titleB = b.querySelector('.course-title').textContent;
        const categoryA = a.querySelector('.course-category').textContent;
        const categoryB = b.querySelector('.course-category').textContent;
        
        switch (currentSort) {
            case 'name-asc':
                return titleA.localeCompare(titleB);
            case 'name-desc':
                return titleB.localeCompare(titleA);
            case 'category-asc':
                return categoryA.localeCompare(categoryB);
            case 'category-desc':
                return categoryB.localeCompare(categoryA);
            default:
                return 0;
        }
    });
    
    return visibleCards;
}

// Function to update courses display
function updateCoursesDisplay() {
    const visibleCourses = getVisibleCourses();
    const totalCourses = visibleCourses.length;
    const totalPages = Math.ceil(totalCourses / coursesPerPage);
    
    // Hide all courses first
    document.querySelectorAll('.course-card').forEach(card => {
        card.style.display = 'none';
    });
    
    // Show courses for current page
    const startIndex = (currentPage - 1) * coursesPerPage;
    const endIndex = startIndex + coursesPerPage;
    
    for (let i = startIndex; i < endIndex && i < totalCourses; i++) {
        visibleCourses[i].style.display = 'block';
    }
    
    // Update courses count
    document.getElementById('coursesCount').textContent = `${totalCourses} courses found`;
    
    // Update pagination
    updatePagination(totalCourses, totalPages);
}

// Function to update pagination
function updatePagination(totalCourses, totalPages) {
    const startCourse = totalCourses > 0 ? (currentPage - 1) * coursesPerPage + 1 : 0;
    const endCourse = Math.min(currentPage * coursesPerPage, totalCourses);
    
    // Update pagination info
    document.getElementById('paginationInfo').textContent = 
        `Showing ${startCourse}-${endCourse} of ${totalCourses} courses`;
    
    // Update pagination buttons
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage === totalPages;
    
    // Update page numbers
    const paginationNumbers = document.getElementById('paginationNumbers');
    paginationNumbers.innerHTML = '';
    
    if (totalPages <= 7) {
        // Show all pages
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => goToPage(i);
            paginationNumbers.appendChild(pageBtn);
        }
    } else {
        // Show first, last, current, and surrounding pages
        const pages = [1];
        
        if (currentPage > 3) pages.push('...');
        
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
            pages.push(i);
        }
        
        if (currentPage < totalPages - 2) pages.push('...');
        if (totalPages > 1) pages.push(totalPages);
        
        pages.forEach(page => {
            const pageBtn = document.createElement('button');
            if (page === '...') {
                pageBtn.className = 'page-number';
                pageBtn.textContent = '...';
                pageBtn.disabled = true;
            } else {
                pageBtn.className = `page-number ${page === currentPage ? 'active' : ''}`;
                pageBtn.textContent = page;
                pageBtn.onclick = () => goToPage(page);
            }
            paginationNumbers.appendChild(pageBtn);
        });
    }
}

// Initialize page on load
document.addEventListener('DOMContentLoaded', function() {
    updateCoursesDisplay();
});

// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('sidebar-open');
}

document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('sidebar-open');
        }
    }
});

window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
});
</script>

<?php
echo "</div>"; // Close admin-main-content
echo $OUTPUT->footer();
?>
