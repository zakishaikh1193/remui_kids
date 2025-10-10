<?php
/**
 * Assign Courses to School - Modern UI
 * Beautiful animated interface for managing school course assignments
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
        case 'get_schools':
            try {
                // First check if company table exists
                if (!$DB->get_manager()->table_exists('company')) {
                    error_log('Company table does not exist, falling back to course categories');
                    // Fallback to course categories if company table doesn't exist
                $schools = $DB->get_records_sql(
                    "SELECT id, name 
                     FROM {course_categories} 
                     WHERE visible = 1 
                     AND id > 1 
                     AND (name NOT LIKE '%Miscellaneous%' 
                          AND name NOT LIKE '%Default%' 
                          AND name NOT LIKE '%System%'
                          AND name NOT LIKE '%General%')
                     AND parent = 0
                     ORDER BY name ASC",
                    []
                );
                } else {
                    // Fetch schools from company table
                    $schools = $DB->get_records_sql(
                        "SELECT id, name 
                         FROM {company} 
                         ORDER BY name ASC",
                        []
                    );
                }
                
                // Debug: Log the result
                error_log('AJAX Schools fetched: ' . print_r($schools, true));
                
                // If no schools found, return empty array
                if (empty($schools)) {
                    $schools = [];
                }
                
                echo json_encode(['status' => 'success', 'schools' => array_values($schools)]);
            } catch (Exception $e) {
                error_log('Error fetching schools: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to load schools: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_school_courses':
            $school_id = intval($_GET['school_id']);
            try {
                // Check if company_course table exists
                if (!$DB->get_manager()->table_exists('company_course')) {
                    error_log('company_course table does not exist, falling back to course category lookup');
                    
                    // Fallback: Get courses from company's category
                    $company = $DB->get_record('company', ['id' => $school_id]);
                    if (!$company) {
                        echo json_encode(['status' => 'error', 'message' => 'Company not found']);
                        exit;
                    }
                    
                    $category = $DB->get_record('course_categories', ['name' => $company->name]);
                    if ($category) {
                $courses = $DB->get_records_sql(
                    "SELECT c.*, cc.name as category_name 
                     FROM {course} c 
                     LEFT JOIN {course_categories} cc ON c.category = cc.id 
                     WHERE c.visible = 1 
                     AND c.id > 1 
                     AND c.category = ?
                             ORDER BY c.fullname ASC",
                            [$category->id]
                        );
                    } else {
                        $courses = [];
                    }
                } else {
                    // Get courses that are already assigned to this company/school
                    $courses = $DB->get_records_sql(
                        "SELECT c.*, comp.name as company_name 
                         FROM {course} c 
                         LEFT JOIN {company_course} cc ON c.id = cc.courseid
                         LEFT JOIN {company} comp ON cc.companyid = comp.id
                         WHERE c.visible = 1 
                         AND c.id > 1 
                         AND cc.companyid = ?
                     ORDER BY c.fullname ASC",
                    [$school_id]
                );
                }
                echo json_encode(['status' => 'success', 'courses' => array_values($courses)]);
            } catch (Exception $e) {
                error_log('Error in get_school_courses: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to load school courses: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_potential_courses':
            $school_id = intval($_GET['school_id']);
            try {
                // Check if company_course table exists
                if (!$DB->get_manager()->table_exists('company_course')) {
                    error_log('company_course table does not exist, falling back to course category lookup');
                    
                    // Fallback: Get courses that are NOT in the company's category
                    $company = $DB->get_record('company', ['id' => $school_id]);
                    if (!$company) {
                        echo json_encode(['status' => 'error', 'message' => 'Company not found']);
                        exit;
                    }
                    
                    $category = $DB->get_record('course_categories', ['name' => $company->name]);
                    $exclude_category_id = $category ? $category->id : 0;
                    
                $courses = $DB->get_records_sql(
                        "SELECT c.*, 
                                cc.name as category_name,
                                cc.id as category_id,
                                cc.parent as category_parent,
                                parent_cc.name as parent_category_name
                     FROM {course} c 
                     LEFT JOIN {course_categories} cc ON c.category = cc.id 
                         LEFT JOIN {course_categories} parent_cc ON cc.parent = parent_cc.id
                     WHERE c.visible = 1 
                     AND c.id > 1 
                         AND c.category > 1
                     AND c.category != ?
                         ORDER BY parent_cc.name ASC, cc.name ASC, c.fullname ASC",
                        [$exclude_category_id]
                    );
                } else {
                    // Get courses that are NOT assigned to this company/school (available to assign)
                    // with hierarchical category information
                    $courses = $DB->get_records_sql(
                        "SELECT c.*, 
                                cc.name as category_name,
                                cc.id as category_id,
                                cc.parent as category_parent,
                                parent_cc.name as parent_category_name
                         FROM {course} c 
                         LEFT JOIN {course_categories} cc ON c.category = cc.id
                         LEFT JOIN {course_categories} parent_cc ON cc.parent = parent_cc.id
                         LEFT JOIN {company_course} comp_course ON c.id = comp_course.courseid AND comp_course.companyid = ?
                         WHERE c.visible = 1 
                         AND c.id > 1 
                     AND c.category > 1
                         AND comp_course.courseid IS NULL
                         ORDER BY parent_cc.name ASC, cc.name ASC, c.fullname ASC",
                    [$school_id]
                );
                }
                
                // Organize courses by category hierarchy
                $hierarchical_courses = [];
                foreach ($courses as $course) {
                    $parent_id = $course->category_parent ?: $course->category_id;
                    $parent_name = $course->parent_category_name ?: $course->category_name;
                    
                    if (!isset($hierarchical_courses[$parent_id])) {
                        $hierarchical_courses[$parent_id] = [
                            'parent_category' => [
                                'id' => $parent_id,
                                'name' => $parent_name
                            ],
                            'subcategories' => []
                        ];
                    }
                    
                    // If course is directly under parent category
                    if ($course->category_parent == 0 || $course->category_parent == $parent_id) {
                        if (!isset($hierarchical_courses[$parent_id]['direct_courses'])) {
                            $hierarchical_courses[$parent_id]['direct_courses'] = [];
                        }
                        $hierarchical_courses[$parent_id]['direct_courses'][] = $course;
                    } else {
                        // Course is under a subcategory
                        $subcategory_id = $course->category_id;
                        if (!isset($hierarchical_courses[$parent_id]['subcategories'][$subcategory_id])) {
                            $hierarchical_courses[$parent_id]['subcategories'][$subcategory_id] = [
                                'id' => $subcategory_id,
                                'name' => $course->category_name,
                                'courses' => []
                            ];
                        }
                        $hierarchical_courses[$parent_id]['subcategories'][$subcategory_id]['courses'][] = $course;
                    }
                }
                
                echo json_encode(['status' => 'success', 'courses' => array_values($hierarchical_courses)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to load potential courses: ' . $e->getMessage()]);
            }
            exit;
            
        case 'assign_course':
            $school_id = intval($_POST['school_id']);
            $course_id = intval($_POST['course_id']);
            
            try {
                // Check if company_course table exists
                if (!$DB->get_manager()->table_exists('company_course')) {
                    error_log('company_course table does not exist, falling back to course category assignment');
                    
                    // Fallback: Move course to the company's category (if company has a category)
                    $company = $DB->get_record('company', ['id' => $school_id]);
                    if (!$company) {
                        echo json_encode(['status' => 'error', 'message' => 'Company not found']);
                        exit;
                    }
                    
                    // Check if company has a corresponding category
                    $category = $DB->get_record('course_categories', ['name' => $company->name]);
                    if (!$category) {
                        // Create a category for this company
                        $new_category = new stdClass();
                        $new_category->name = $company->name;
                        $new_category->description = 'Category for ' . $company->name;
                        $new_category->parent = 0;
                        $new_category->sortorder = 999;
                        $new_category->visible = 1;
                        $new_category->timecreated = time();
                        $new_category->timemodified = time();
                        
                        $category_id = $DB->insert_record('course_categories', $new_category);
                        if (!$category_id) {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to create category for company']);
                            exit;
                        }
                    } else {
                        $category_id = $category->id;
                    }
                    
                    // Move course to company's category
                $course = $DB->get_record('course', ['id' => $course_id]);
                if ($course) {
                        $course->category = $category_id;
                    $course->timemodified = time();
                    if ($DB->update_record('course', $course)) {
                        echo json_encode(['status' => 'success', 'message' => 'Course assigned successfully']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to assign course']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
                    }
                    exit;
                }
                
                // Check if course is already assigned to this company
                $existing = $DB->get_record('company_course', ['companyid' => $school_id, 'courseid' => $course_id]);
                if ($existing) {
                    error_log('Course ' . $course_id . ' is already assigned to company ' . $school_id);
                    echo json_encode(['status' => 'error', 'message' => 'Course is already assigned to this school']);
                    exit;
                }
                
                // Debug: Log the assignment attempt
                error_log('Attempting to assign course ' . $course_id . ' to company ' . $school_id);
                
                // Assign course to company
                $company_course = new stdClass();
                $company_course->companyid = $school_id;
                $company_course->courseid = $course_id;
                $company_course->departmentid = 0; // Default department, can be updated later
                
                // Debug: Log the data being inserted
                error_log('Attempting to insert company_course record: ' . print_r($company_course, true));
                
                $result = $DB->insert_record('company_course', $company_course);
                if ($result) {
                    error_log('Successfully inserted company_course record with ID: ' . $result);
                    echo json_encode(['status' => 'success', 'message' => 'Course assigned successfully']);
                } else {
                    error_log('Failed to insert company_course record');
                    echo json_encode(['status' => 'error', 'message' => 'Failed to assign course - database insert failed']);
                }
            } catch (Exception $e) {
                error_log('Error in assign_course: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to assign course: ' . $e->getMessage()]);
            }
            exit;
            
        case 'unassign_course':
            $school_id = intval($_POST['school_id']);
            $course_id = intval($_POST['course_id']);
            
            try {
                // Check if company_course table exists
                if (!$DB->get_manager()->table_exists('company_course')) {
                    error_log('company_course table does not exist, falling back to course category unassignment');
                    
                    // Fallback: Move course to default category
                    $company = $DB->get_record('company', ['id' => $school_id]);
                    if (!$company) {
                        echo json_encode(['status' => 'error', 'message' => 'Company not found']);
                        exit;
                    }
                    
                    // Find the company's category
                    $category = $DB->get_record('course_categories', ['name' => $company->name]);
                    if ($category) {
                        // Move course to default category (category 1)
                        $course = $DB->get_record('course', ['id' => $course_id]);
                        if ($course && $course->category == $category->id) {
                            $course->category = 1; // Default category
                        $course->timemodified = time();
                        if ($DB->update_record('course', $course)) {
                            echo json_encode(['status' => 'success', 'message' => 'Course unassigned successfully']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to unassign course']);
                        }
                    } else {
                            echo json_encode(['status' => 'error', 'message' => 'Course was not assigned to this company']);
                    }
                } else {
                        echo json_encode(['status' => 'error', 'message' => 'Company category not found']);
                    }
                    exit;
                }
                
                // Remove course from company
                $deleted = $DB->delete_records('company_course', ['companyid' => $school_id, 'courseid' => $course_id]);
                if ($deleted) {
                    echo json_encode(['status' => 'success', 'message' => 'Course unassigned successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to unassign course or course was not assigned']);
                }
            } catch (Exception $e) {
                error_log('Error in unassign_course: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to unassign course: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get all schools from company table
try {
    // First check if company table exists
    if (!$DB->get_manager()->table_exists('company')) {
        error_log('Company table does not exist, falling back to course categories');
        // Fallback to course categories if company table doesn't exist
    $schools = $DB->get_records_sql(
        "SELECT id, name 
         FROM {course_categories} 
         WHERE visible = 1 
         AND id > 1 
         AND (name NOT LIKE '%Miscellaneous%' 
              AND name NOT LIKE '%Default%' 
              AND name NOT LIKE '%System%'
              AND name NOT LIKE '%General%')
         AND parent = 0
         ORDER BY name ASC",
        []
    );
    } else {
        // Fetch schools from company table
        $schools = $DB->get_records_sql(
            "SELECT id, name 
             FROM {company} 
             ORDER BY name ASC",
            []
        );
    }
    
    // Debug: Log the result
    error_log('Main schools fetched: ' . print_r($schools, true));
    
    // If no schools found, return empty array
    if (empty($schools)) {
        $schools = [];
    }
} catch (Exception $e) {
    error_log('Error in main schools fetch: ' . $e->getMessage());
    // If all fails, return empty array
    $schools = [];
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/assign_to_school.php');
$PAGE->set_title('Assign Courses to School');
$PAGE->set_heading('Assign Courses to School');

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
    background: linear-gradient(135deg, #fce4ec 0%, #f3e5f5 50%, #e8f5e8 100%);
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
/* Modern Assign to School Page Styles */
.assign-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, #e1bee7 0%, #f8bbd9 100%);
    min-height: 100vh;
}

.assign-header {
    text-align: center;
    margin-bottom: 40px;
    color: #4a148c;
    position: relative;
}

.assign-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(74, 20, 140, 0.2);
    animation: titleGlow 2s ease-in-out infinite alternate;
    color: #4a148c;
}

.assign-header p {
    font-size: 1.1rem;
    opacity: 0.8;
    margin-bottom: 30px;
    color: #4a148c;
}

@keyframes titleGlow {
    from { text-shadow: 2px 2px 4px rgba(74, 20, 140, 0.2), 0 0 20px rgba(74, 20, 140, 0.3); }
    to { text-shadow: 2px 2px 4px rgba(74, 20, 140, 0.2), 0 0 30px rgba(74, 20, 140, 0.6); }
}

/* School Selection */
.school-selection {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    animation: fadeInUp 0.6s ease-out;
}

.school-selection h3 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5rem;
    font-weight: 600;
}

.school-dropdown {
    position: relative;
    width: 100%;
}

.school-select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    font-size: 1.1rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
}

.school-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Main Assignment Interface */
.assignment-interface {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 30px;
    margin-bottom: 30px;
    animation: fadeInUp 0.8s ease-out;
}

.course-panel {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.course-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.panel-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.course-count {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.search-container {
    margin-bottom: 20px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.1rem;
}

.course-list {
    max-height: 500px;
    overflow-y: auto;
    border-radius: 15px;
    background: #f8f9fa;
    padding: 10px;
}

.course-item {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.course-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.course-item:hover::before {
    transform: scaleX(1);
}

.course-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.course-item.selected {
    border-color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
}

.course-item.selected::before {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    transform: scaleX(1);
}

.course-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    line-height: 1.3;
}

.course-category {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 8px;
}

.course-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #6c757d;
}

.enrollment-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.warning-badge {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: center;
    justify-content: center;
}

.action-btn {
    background: linear-gradient(135deg, #e1bee7 0%, #f8bbd9 100%);
    color: #4a148c;
    border: none;
    padding: 15px 25px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(225, 190, 231, 0.3);
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 120px;
    justify-content: center;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(225, 190, 231, 0.4);
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.action-btn.add {
    background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
    color: #2e7d32;
    box-shadow: 0 5px 15px rgba(200, 230, 201, 0.3);
}

.action-btn.add:hover {
    box-shadow: 0 8px 25px rgba(200, 230, 201, 0.4);
}

.action-btn.remove {
    background: linear-gradient(135deg, #ffcdd2 0%, #ef9a9a 100%);
    color: #c62828;
    box-shadow: 0 5px 15px rgba(255, 205, 210, 0.3);
}

.action-btn.remove:hover {
    box-shadow: 0 8px 25px rgba(255, 205, 210, 0.4);
}

/* Warning Section */
.warning-section {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    animation: fadeInUp 1s ease-out;
}

.warning-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.warning-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    animation: pulse 2s infinite;
}

.warning-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.warning-content {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.warning-text {
    color: #856404;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

.confirmation-section {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px solid #e9ecef;
}

.confirmation-checkbox {
    width: 20px;
    height: 20px;
    accent-color: #dc3545;
    cursor: pointer;
}

.confirmation-label {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    cursor: pointer;
    margin: 0;
}

/* Loading States */
.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.course-item {
    animation: slideInLeft 0.3s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .assignment-interface {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
    
    .assign-header h1 {
        font-size: 2rem;
    }
    
    .course-panel {
        padding: 20px;
    }
}

/* Custom Scrollbar */
.course-list::-webkit-scrollbar {
    width: 8px;
}

.course-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.course-list::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.course-list::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

/* Hierarchical Course Structure - Pastel Colors */
.category-group {
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.category-header {
    background: linear-gradient(135deg, #e1bee7 0%, #f8bbd9 100%);
    color: #4a148c;
    padding: 15px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    border-bottom: 1px solid #e1bee7;
}

.category-header:hover {
    background: linear-gradient(135deg, #ce93d8 0%, #f48fb1 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(225, 190, 231, 0.3);
}

.category-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4a148c;
}

.category-count {
    background: rgba(74, 20, 140, 0.1);
    color: #4a148c;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    border: 1px solid rgba(74, 20, 140, 0.2);
}

.toggle-icon {
    transition: transform 0.3s ease;
    color: #4a148c;
}

.category-content {
    padding: 20px;
    background: linear-gradient(135deg, #fce4ec 0%, #f3e5f5 100%);
}

.direct-courses-section {
    margin-bottom: 20px;
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #81c784;
    box-shadow: 0 2px 6px rgba(129, 199, 132, 0.2);
}

/* Subcategories Container */
.subcategories-container {
    margin-top: 20px;
    padding: 15px;
    background: linear-gradient(135deg, #f3e5f5 0%, #e8eaf6 100%);
    border-radius: 10px;
    border: 1px solid #ce93d8;
}

.subcategories-header {
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #7b1fa2;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ba68c8;
}

.subcategory-section {
    margin-bottom: 15px;
    background: linear-gradient(135deg, #e3f2fd 0%, #f0f4ff 100%);
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #64b5f6;
    box-shadow: 0 2px 6px rgba(100, 181, 246, 0.2);
    transition: all 0.3s ease;
}

.subcategory-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(100, 181, 246, 0.3);
}

.subcategory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.section-title {
    margin: 0 0 15px 0;
    font-size: 1rem;
    font-weight: 600;
    color: #2e7d32;
    display: flex;
    align-items: center;
    gap: 8px;
}

.subcategory-section .section-title {
    color: #1565c0;
    font-size: 0.95rem;
    margin: 0;
}

.subcategory-count {
    background: linear-gradient(135deg, #64b5f6 0%, #42a5f5 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(100, 181, 246, 0.3);
}

.courses-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Enhanced Course Item Styling */
.course-item {
    background: white;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #64b5f6;
}

.course-info {
    flex: 1;
}

.course-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    font-size: 0.95rem;
}

.course-category {
    font-size: 0.8rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 4px;
}

.course-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-end;
}

.enrollment-badge, .warning-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 3px;
}

.enrollment-badge {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.warning-badge {
    background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
    color: #e65100;
    border: 1px solid #ffb74d;
}
</style>

<div class="assign-container">
    <div class="assign-header">
        <h1>Assign Courses to School</h1>
        <p>Manage course assignments for educational institutions</p>
    </div>

    <!-- School Selection -->
    <div class="school-selection">
        <h3>Select School</h3>
        <div class="school-dropdown">
            <select class="school-select" id="schoolSelect">
                <option value="">Choose a school...</option>
                <?php foreach ($schools as $school): ?>
                    <option value="<?php echo $school->id; ?>"><?php echo htmlspecialchars($school->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Assignment Interface -->
    <div class="assignment-interface" id="assignmentInterface" style="display: none;">
        <!-- School Courses Panel -->
        <div class="course-panel">
            <div class="panel-header">
                <h3 class="panel-title">School Courses</h3>
                <div class="course-count" id="schoolCourseCount">0</div>
            </div>
            <div class="search-container">
                <i class="fa fa-search search-icon"></i>
                <input type="text" class="search-input" id="schoolSearch" placeholder="Search school courses...">
            </div>
            <div class="course-list" id="schoolCourseList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading courses...
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="action-btn add" id="addBtn" disabled>
                <i class="fa fa-arrow-left"></i>
                Add
            </button>
            <button class="action-btn remove" id="removeBtn" disabled>
                Remove
                <i class="fa fa-arrow-right"></i>
            </button>
        </div>

        <!-- Potential Courses Panel -->
        <div class="course-panel">
            <div class="panel-header">
                <h3 class="panel-title">Potential Courses</h3>
                <div class="course-count" id="potentialCourseCount">0</div>
            </div>
            <div class="search-container">
                <i class="fa fa-search search-icon"></i>
                <input type="text" class="search-input" id="potentialSearch" placeholder="Search potential courses...">
            </div>
            <div class="course-list" id="potentialCourseList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading courses...
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Section -->
    <div class="warning-section" id="warningSection" style="display: none;">
        <div class="warning-header">
            <div class="warning-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h3 class="warning-title">Important Warning</h3>
        </div>
        <div class="warning-content">
            <p class="warning-text">
                <strong>WARNING:</strong> If "(existing enrollments)" is shown you must tick the box beneath to allow add or remove. 
                If you do this, all users will be unenrolled and ALL THEIR DATA (for that course) IS LOST. This cannot be undone.
            </p>
        </div>
        <div class="confirmation-section">
            <input type="checkbox" class="confirmation-checkbox" id="confirmUnenroll">
            <label for="confirmUnenroll" class="confirmation-label">OK to unenroll users</label>
        </div>
    </div>
</div>

<script>
// Global variables
let selectedSchool = null;
let schoolCourses = [];
let potentialCourses = [];
let selectedSchoolCourses = [];
let selectedPotentialCourses = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

function initializeEventListeners() {
    // School selection
    document.getElementById('schoolSelect').addEventListener('change', handleSchoolChange);
    
    // Search functionality
    document.getElementById('schoolSearch').addEventListener('input', (e) => filterCourses('school', e.target.value));
    document.getElementById('potentialSearch').addEventListener('input', (e) => filterCourses('potential', e.target.value));
    
    // Action buttons
    document.getElementById('addBtn').addEventListener('click', addSelectedCourses);
    document.getElementById('removeBtn').addEventListener('click', removeSelectedCourses);
    
    // Confirmation checkbox
    document.getElementById('confirmUnenroll').addEventListener('change', updateActionButtons);
}

function handleSchoolChange(event) {
    const schoolId = event.target.value;
    if (schoolId) {
        selectedSchool = schoolId;
        document.getElementById('assignmentInterface').style.display = 'grid';
        loadSchoolCourses();
        loadPotentialCourses();
    } else {
        selectedSchool = null;
        document.getElementById('assignmentInterface').style.display = 'none';
        document.getElementById('warningSection').style.display = 'none';
    }
}

async function loadSchoolCourses() {
    try {
        showLoading('schoolCourseList');
        const response = await fetch(`?action=get_school_courses&school_id=${selectedSchool}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.courses) {
            schoolCourses = data.courses;
            renderCourses('schoolCourseList', schoolCourses, 'school');
            updateCourseCount('schoolCourseCount', schoolCourses.length);
        } else {
            schoolCourses = [];
            renderCourses('schoolCourseList', [], 'school');
            updateCourseCount('schoolCourseCount', 0);
        }
    } catch (error) {
        console.error('Error loading school courses:', error);
        showError('schoolCourseList', 'Failed to load school courses');
    }
}

async function loadPotentialCourses() {
    try {
        showLoading('potentialCourseList');
        const response = await fetch(`?action=get_potential_courses&school_id=${selectedSchool}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.courses) {
            potentialCourses = data.courses;
            renderCourses('potentialCourseList', potentialCourses, 'potential');
            updateCourseCount('potentialCourseCount', potentialCourses.length);
        } else {
            potentialCourses = [];
            renderCourses('potentialCourseList', [], 'potential');
            updateCourseCount('potentialCourseCount', 0);
        }
    } catch (error) {
        console.error('Error loading potential courses:', error);
        showError('potentialCourseList', 'Failed to load potential courses');
    }
}

function renderCourses(containerId, courses, type) {
    const container = document.getElementById(containerId);
    
    if (!container) {
        console.error('Container not found:', containerId);
        return;
    }
    
    if (!courses || courses.length === 0) {
        container.innerHTML = '<div class="loading">No courses found</div>';
        return;
    }
    
    // For potential courses, render hierarchical structure
    if (type === 'potential' && courses[0] && courses[0].parent_category) {
        container.innerHTML = courses.map(categoryGroup => {
            if (!categoryGroup) return '';
            
            let html = `
                <div class="category-group">
                    <div class="category-header" onclick="toggleCategory('${categoryGroup.parent_category.id}')">
                        <h4 class="category-title">
                            <i class="fa fa-folder"></i>
                            ${escapeHtml(categoryGroup.parent_category.name)}
                        </h4>
                        <span class="category-count">
                            ${(categoryGroup.direct_courses ? categoryGroup.direct_courses.length : 0) + 
                              Object.values(categoryGroup.subcategories || {}).reduce((total, sub) => total + (sub.courses ? sub.courses.length : 0), 0)} courses
                        </span>
                        <i class="fa fa-chevron-down toggle-icon" id="toggle-${categoryGroup.parent_category.id}"></i>
                    </div>
                    <div class="category-content" id="category-${categoryGroup.parent_category.id}" style="display: none;">
            `;
            
            // Direct courses under parent category
            if (categoryGroup.direct_courses && categoryGroup.direct_courses.length > 0) {
                html += `
                    <div class="direct-courses-section">
                        <h5 class="section-title">
                            <i class="fa fa-book"></i>
                            Direct Courses (${categoryGroup.direct_courses.length})
                        </h5>
                        <div class="courses-list">
                `;
                categoryGroup.direct_courses.forEach(course => {
                    html += `
                        <div class="course-item" data-course-id="${course.id}" data-type="${type}">
                            <div class="course-name">${escapeHtml(course.fullname || 'Unknown Course')}</div>
                            <div class="course-category">${escapeHtml(course.category_name || 'Uncategorized')}</div>
                            <div class="course-meta">
                                <span class="enrollment-badge">${course.idnumber || 'No ID'}</span>
                                ${course.id > 1 ? '<span class="warning-badge">Existing enrollments</span>' : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div></div>';
            }
            
            // Subcategories
            if (categoryGroup.subcategories && Object.keys(categoryGroup.subcategories).length > 0) {
                html += `
                    <div class="subcategories-container">
                        <h5 class="subcategories-header">
                            <i class="fa fa-sitemap"></i>
                            Subcategories (${Object.keys(categoryGroup.subcategories).length})
                        </h5>
                `;
                
                Object.values(categoryGroup.subcategories).forEach(subcategory => {
                    if (subcategory.courses && subcategory.courses.length > 0) {
                        html += `
                            <div class="subcategory-section">
                                <div class="subcategory-header">
                                    <h6 class="section-title">
                                        <i class="fa fa-folder-open"></i>
                                        ${escapeHtml(subcategory.name)}
                                    </h6>
                                    <span class="subcategory-count">${subcategory.courses.length} courses</span>
                                </div>
                                <div class="courses-list">
                        `;
                        subcategory.courses.forEach(course => {
                            html += `
                                <div class="course-item" data-course-id="${course.id}" data-type="${type}">
                                    <div class="course-info">
                                        <div class="course-name">${escapeHtml(course.fullname || 'Unknown Course')}</div>
                                        <div class="course-category">
                                            <i class="fa fa-tag"></i>
                                            ${escapeHtml(course.category_name || 'Uncategorized')}
                                        </div>
                                    </div>
                                    <div class="course-meta">
                                        <span class="enrollment-badge">
                                            <i class="fa fa-hashtag"></i>
                                            ${course.idnumber || 'No ID'}
                                        </span>
                                        ${course.id > 1 ? '<span class="warning-badge"><i class="fa fa-users"></i> Existing enrollments</span>' : ''}
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div></div>';
                    }
                });
                
                html += '</div>'; // Close subcategories-container
            }
            
            html += '</div></div>';
            return html;
        }).join('');
    } else {
        // For school courses, render simple list
    container.innerHTML = courses.map(course => {
        if (!course) return '';
        return `
            <div class="course-item" data-course-id="${course.id || ''}" data-type="${type}">
                <div class="course-name">${escapeHtml(course.fullname || 'Unknown Course')}</div>
                <div class="course-category">${escapeHtml(course.category_name || 'Uncategorized')}</div>
                <div class="course-meta">
                    <span class="enrollment-badge">${course.idnumber || 'No ID'}</span>
                    ${course.id > 1 ? '<span class="warning-badge">Existing enrollments</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
    }
    
    // Add click listeners
    container.querySelectorAll('.course-item').forEach(item => {
        item.addEventListener('click', () => toggleCourseSelection(item, type));
    });
}

function toggleCourseSelection(item, type) {
    const courseId = item.dataset.courseId;
    
    if (type === 'school') {
        if (selectedSchoolCourses.includes(courseId)) {
            selectedSchoolCourses = selectedSchoolCourses.filter(id => id !== courseId);
            item.classList.remove('selected');
        } else {
            selectedSchoolCourses.push(courseId);
            item.classList.add('selected');
        }
    } else {
        if (selectedPotentialCourses.includes(courseId)) {
            selectedPotentialCourses = selectedPotentialCourses.filter(id => id !== courseId);
            item.classList.remove('selected');
        } else {
            selectedPotentialCourses.push(courseId);
            item.classList.add('selected');
        }
    }
    
    updateActionButtons();
}

function updateActionButtons() {
    const addBtn = document.getElementById('addBtn');
    const removeBtn = document.getElementById('removeBtn');
    const confirmCheckbox = document.getElementById('confirmUnenroll');
    
    // Update add button
    addBtn.disabled = selectedPotentialCourses.length === 0;
    
    // Update remove button
    removeBtn.disabled = selectedSchoolCourses.length === 0;
    
    // Show warning if needed
    const hasEnrollments = selectedSchoolCourses.some(courseId => {
        const course = schoolCourses.find(c => c.id == courseId);
        return course && course.id > 1;
    });
    
    if (hasEnrollments) {
        document.getElementById('warningSection').style.display = 'block';
        removeBtn.disabled = !confirmCheckbox.checked;
    } else {
        document.getElementById('warningSection').style.display = 'none';
    }
}

async function addSelectedCourses() {
    if (selectedPotentialCourses.length === 0) return;
    
    try {
        for (const courseId of selectedPotentialCourses) {
            const formData = new FormData();
            formData.append('school_id', selectedSchool);
            formData.append('course_id', courseId);
            
            const response = await fetch('?action=assign_course', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message);
            }
        }
        
        // Refresh both lists
        await loadSchoolCourses();
        await loadPotentialCourses();
        
        // Clear selections
        selectedPotentialCourses = [];
        clearSelections('potential');
        
        showMessage('Courses assigned successfully!', 'success');
    } catch (error) {
        console.error('Error assigning courses:', error);
        showMessage('Failed to assign courses: ' + error.message, 'error');
    }
}

async function removeSelectedCourses() {
    if (selectedSchoolCourses.length === 0) return;
    
    try {
        for (const courseId of selectedSchoolCourses) {
            const formData = new FormData();
            formData.append('school_id', selectedSchool);
            formData.append('course_id', courseId);
            
            const response = await fetch('?action=unassign_course', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message);
            }
        }
        
        // Refresh both lists
        await loadSchoolCourses();
        await loadPotentialCourses();
        
        // Clear selections
        selectedSchoolCourses = [];
        clearSelections('school');
        
        showMessage('Courses unassigned successfully!', 'success');
    } catch (error) {
        console.error('Error unassigning courses:', error);
        showMessage('Failed to unassign courses: ' + error.message, 'error');
    }
}

function clearSelections(type) {
    const container = type === 'school' ? 'schoolCourseList' : 'potentialCourseList';
    document.getElementById(container).querySelectorAll('.course-item.selected').forEach(item => {
        item.classList.remove('selected');
    });
}

function filterCourses(type, searchTerm) {
    const container = type === 'school' ? 'schoolCourseList' : 'potentialCourseList';
    const courses = type === 'school' ? schoolCourses : potentialCourses;
    
    const filteredCourses = courses.filter(course => 
        course.fullname.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (course.category_name && course.category_name.toLowerCase().includes(searchTerm.toLowerCase()))
    );
    
    renderCourses(container, filteredCourses, type);
}

function updateCourseCount(elementId, count) {
    document.getElementById(elementId).textContent = count;
}

function toggleCategory(categoryId) {
    const content = document.getElementById(`category-${categoryId}`);
    const toggle = document.getElementById(`toggle-${categoryId}`);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        toggle.style.transform = 'rotate(0deg)';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading(containerId) {
    document.getElementById(containerId).innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            Loading courses...
        </div>
    `;
}

function showError(containerId, message) {
    document.getElementById(containerId).innerHTML = `
        <div class="loading">
            <i class="fa fa-exclamation-triangle" style="color: #dc3545; margin-right: 10px;"></i>
            ${message}
        </div>
    `;
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
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

