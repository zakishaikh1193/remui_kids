<?php
/**
 * School Manager - Courses Management
 * View and manage all courses in the school/department
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/courselib.php');

// Require login
require_login();
$context = context_system::instance();

// Check if user is a department manager (school manager)
global $USER, $DB;

// Get user's company assignment
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager courses page - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/courses.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Courses Management');
$PAGE->set_heading('Courses Management');
$PAGE->add_body_class('school-manager-courses-page');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_courses':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'fullname';
            $order = $_GET['order'] ?? 'ASC';
            $filter = $_GET['filter'] ?? 'all';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Get courses assigned to this department
                $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.visible, c.timecreated,
                               c.startdate, c.enddate,
                               COUNT(DISTINCT ue.userid) as enrolled_students,
                               COUNT(DISTINCT cc.userid) as completed_students,
                               COUNT(DISTINCT ra.userid) as teachers_count
                        FROM {company_course} ccourse
                        JOIN {course} c ON ccourse.courseid = c.id
                        LEFT JOIN {enrol} e ON e.courseid = c.id
                        LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                        LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted IS NOT NULL
                        LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = ?
                        LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id
                        LEFT JOIN {role} r ON ra.roleid = r.id AND r.shortname IN ('editingteacher', 'teacher')
                        WHERE ccourse.departmentid = ?";
                
                $params = [CONTEXT_COURSE, $company_user->departmentid];
                
                // Add search filter
                if (!empty($search)) {
                    $sql .= " AND (c.fullname LIKE ? OR c.shortname LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param]);
                }
                
                // Add visibility filter
                if ($filter === 'visible') {
                    $sql .= " AND c.visible = 1";
                } elseif ($filter === 'hidden') {
                    $sql .= " AND c.visible = 0";
                }
                
                $sql .= " GROUP BY c.id ORDER BY c.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $courses = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT c.id)
                              FROM {company_course} ccourse
                              JOIN {course} c ON ccourse.courseid = c.id
                              WHERE ccourse.departmentid = ?";
                $count_params = [$company_user->departmentid];
                
                if (!empty($search)) {
                    $count_sql .= " AND (c.fullname LIKE ? OR c.shortname LIKE ?)";
                    $count_params = array_merge($count_params, [$search_param, $search_param]);
                }
                
                if ($filter === 'visible') {
                    $count_sql .= " AND c.visible = 1";
                } elseif ($filter === 'hidden') {
                    $count_sql .= " AND c.visible = 0";
                }
                
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'courses' => array_values($courses),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_course_details':
            $course_id = $_GET['course_id'] ?? 0;
            
            try {
                // Verify course belongs to this department
                $course_dept = $DB->get_record('company_course', [
                    'courseid' => $course_id,
                    'departmentid' => $company_user->departmentid
                ]);
                
                if (!$course_dept) {
                    throw new Exception('Course not found in your department');
                }
                
                $course = $DB->get_record('course', ['id' => $course_id]);
                
                // Get enrolled students
                $students = $DB->get_records_sql(
                    "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, ue.timecreated as enrollment_date
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON ue.enrolid = e.id
                     JOIN {user} u ON ue.userid = u.id
                     WHERE e.courseid = ?
                     ORDER BY u.firstname, u.lastname",
                    [$course_id]
                );
                
                // Get assigned teachers
                $teachers = $DB->get_records_sql(
                    "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                     FROM {role_assignments} ra
                     JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = ?
                     JOIN {user} u ON ra.userid = u.id
                     JOIN {role} r ON ra.roleid = r.id
                     WHERE ctx.instanceid = ? AND r.shortname IN ('editingteacher', 'teacher')
                     ORDER BY u.firstname, u.lastname",
                    [CONTEXT_COURSE, $course_id]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'course' => $course,
                    'students' => array_values($students),
                    'teachers' => array_values($teachers)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }
}

echo $OUTPUT->header();

// Include sidebar
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
?>

<div class="courses-management-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Courses Management</h1>
            <p class="page-subtitle">Manage all courses in <?php echo format_string($department->name); ?></p>
        </div>
        <div class="header-actions">
            <button class="btn-action" onclick="window.location.href='<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/course_assignments.php'">
                <i class="fa fa-plus"></i> Assign Courses
            </button>
            <button class="btn-action btn-secondary" onclick="exportCourses()">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <span class="search-icon"><i class="fa fa-search"></i></span>
            <input type="text" class="search-input" placeholder="Search courses..." id="courseSearch">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Courses</button>
            <button class="filter-btn" data-filter="visible">Visible</button>
            <button class="filter-btn" data-filter="hidden">Hidden</button>
        </div>
        <div class="view-controls">
            <select class="per-page-select" id="perPageSelect">
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <!-- Courses Grid -->
    <div class="courses-grid" id="coursesGrid">
        <div class="loading-state">
            <i class="fa fa-spinner fa-spin"></i> Loading courses...
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls" id="paginationControls">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<style>
    .courses-management-page {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
    }

    .page-subtitle {
        font-size: 1rem;
        color: #6c757d;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-action {
        padding: 0.75rem 1.5rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* Controls Section */
    .controls-section {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 250px;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .filter-btn {
        padding: 0.75rem 1.25rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background: #f8f9fa;
    }

    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .per-page-select {
        padding: 0.75rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.95rem;
        cursor: pointer;
    }

    /* Courses Grid */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .loading-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .course-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .course-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        border-color: #667eea;
    }

    .course-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.5rem;
        color: white;
        position: relative;
    }

    .course-visibility-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.2);
    }

    .course-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: white;
    }

    .course-shortname {
        font-size: 0.85rem;
        margin: 0;
        opacity: 0.9;
    }

    .course-body {
        padding: 1.5rem;
    }

    .course-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .course-stat {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
        margin: 0 0 0.25rem 0;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .course-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
    }

    .meta-icon {
        color: #667eea;
        width: 16px;
    }

    .course-progress {
        margin-bottom: 1.5rem;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
    }

    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .course-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-course-action {
        flex: 1;
        padding: 0.75rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-course-action:hover {
        background: #5568d3;
        text-decoration: none;
        color: white;
    }

    .btn-course-action.secondary {
        background: #6c757d;
    }

    .btn-course-action.secondary:hover {
        background: #5a6268;
    }

    /* Pagination */
    .pagination-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .pagination-info {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .pagination-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pagination-btn:hover:not(:disabled) {
        background: #f8f9fa;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .courses-grid {
            grid-template-columns: 1fr;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<script>
let currentPage = 1;
let perPage = 20;
let currentSort = 'fullname';
let currentOrder = 'ASC';
let currentFilter = 'all';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
    
    // Search functionality
    document.getElementById('courseSearch').addEventListener('input', function(e) {
        currentSearch = e.target.value;
        currentPage = 1;
        loadCourses();
    });
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadCourses();
        });
    });
    
    // Per page select
    document.getElementById('perPageSelect').addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        loadCourses();
    });
});

function loadCourses() {
    const grid = document.getElementById('coursesGrid');
    grid.innerHTML = '<div class="loading-state"><i class="fa fa-spinner fa-spin"></i> Loading courses...</div>';
    
    const params = new URLSearchParams({
        action: 'get_courses',
        page: currentPage,
        per_page: perPage,
        sort: currentSort,
        order: currentOrder,
        filter: currentFilter,
        search: currentSearch
    });
    
    fetch('?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderCoursesGrid(data.courses);
                renderPagination(data.total_count, data.page, data.total_pages);
            } else {
                grid.innerHTML = '<div class="loading-state">Error loading courses</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            grid.innerHTML = '<div class="loading-state">Error loading courses</div>';
        });
}

function renderCoursesGrid(courses) {
    const grid = document.getElementById('coursesGrid');
    
    if (courses.length === 0) {
        grid.innerHTML = '<div class="loading-state">No courses found</div>';
        return;
    }
    
    let html = '';
    courses.forEach(course => {
        const visibilityBadge = course.visible == 1 ? 'Visible' : 'Hidden';
        const completionRate = course.enrolled_students > 0 
            ? Math.round((course.completed_students / course.enrolled_students) * 100) 
            : 0;
        
        html += `
            <div class="course-card">
                <div class="course-header">
                    <span class="course-visibility-badge">${visibilityBadge}</span>
                    <h3 class="course-name">${course.fullname}</h3>
                    <p class="course-shortname">${course.shortname}</p>
                </div>
                
                <div class="course-body">
                    <div class="course-stats">
                        <div class="course-stat">
                            <div class="stat-value">${course.enrolled_students || 0}</div>
                            <div class="stat-label">Students</div>
                        </div>
                        <div class="course-stat">
                            <div class="stat-value">${course.teachers_count || 0}</div>
                            <div class="stat-label">Teachers</div>
                        </div>
                        <div class="course-stat">
                            <div class="stat-value">${course.completed_students || 0}</div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    
                    <div class="course-progress">
                        <div class="progress-label">
                            <span>Completion Rate</span>
                            <span>${completionRate}%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: ${completionRate}%"></div>
                        </div>
                    </div>
                    
                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="fa fa-calendar meta-icon"></i>
                            <span>Created: ${new Date(course.timecreated * 1000).toLocaleDateString()}</span>
                        </div>
                    </div>
                    
                    <div class="course-actions">
                        <a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=${course.id}" class="btn-course-action">
                            <i class="fa fa-eye"></i> View Course
                        </a>
                        <button class="btn-course-action secondary" onclick="viewCourseDetails(${course.id})">
                            <i class="fa fa-info-circle"></i> Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    grid.innerHTML = html;
}

function renderPagination(totalCount, currentPage, totalPages) {
    const container = document.getElementById('paginationControls');
    
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, totalCount);
    
    let html = `
        <div class="pagination-info">
            Showing ${start} to ${end} of ${totalCount} courses
        </div>
        <div class="pagination-buttons">
    `;
    
    // Previous button
    html += `<button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
        <i class="fa fa-chevron-left"></i> Previous
    </button>`;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += '<span>...</span>';
        }
    }
    
    // Next button
    html += `<button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
        Next <i class="fa fa-chevron-right"></i>
    </button>`;
    
    html += '</div>';
    container.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadCourses();
}

function viewCourseDetails(courseId) {
    alert('Course details view will be implemented soon. Course ID: ' + courseId);
}

function exportCourses() {
    alert('Export functionality will be implemented soon.');
}
</script>

<?php
echo '</div>'; // Close school-manager-main-content
echo $OUTPUT->footer();
?>


