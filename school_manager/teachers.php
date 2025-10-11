<?php
/**
 * School Manager - Teachers Management
 * View and manage all teachers in the school/department
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Require login
require_login();
$context = context_system::instance();

// Check if user is a department manager (school manager)
global $USER, $DB;

// Get user's company assignment
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager teachers page - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/teachers.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Teachers Management');
$PAGE->set_heading('Teachers Management');
$PAGE->add_body_class('school-manager-teachers-page');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_teachers':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'firstname';
            $order = $_GET['order'] ?? 'ASC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Get teachers in this department
                // Teachers are users with editing teacher or teacher role
                $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.username, u.email, u.lastaccess,
                               u.timecreated,
                               r.shortname as role_type,
                               COUNT(DISTINCT ctx.instanceid) as courses_teaching
                        FROM {company_users} cu
                        JOIN {user} u ON cu.userid = u.id
                        JOIN {role_assignments} ra ON u.id = ra.userid
                        JOIN {role} r ON ra.roleid = r.id
                        LEFT JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = ?
                        WHERE cu.departmentid = ? 
                        AND r.shortname IN ('editingteacher', 'teacher')
                        AND u.deleted = 0 
                        AND cu.suspended = 0";
                
                $params = [CONTEXT_COURSE, $company_user->departmentid];
                
                // Add search filter
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " GROUP BY u.id, r.shortname ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $teachers = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT u.id)
                              FROM {company_users} cu
                              JOIN {user} u ON cu.userid = u.id
                              JOIN {role_assignments} ra ON u.id = ra.userid
                              JOIN {role} r ON ra.roleid = r.id
                              WHERE cu.departmentid = ? 
                              AND r.shortname IN ('editingteacher', 'teacher')
                              AND u.deleted = 0 
                              AND cu.suspended = 0";
                $count_params = [$company_user->departmentid];
                
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                // For each teacher, get student count
                foreach ($teachers as $teacher) {
                    $student_count = $DB->count_records_sql(
                        "SELECT COUNT(DISTINCT ue.userid)
                         FROM {user_enrolments} ue
                         JOIN {enrol} e ON ue.enrolid = e.id
                         JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = ?
                         JOIN {role_assignments} ra ON ra.contextid = ctx.id
                         WHERE ra.userid = ?",
                        [CONTEXT_COURSE, $teacher->id]
                    );
                    $teacher->student_count = $student_count;
                }
                
                echo json_encode([
                    'status' => 'success',
                    'teachers' => array_values($teachers),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_teacher_details':
            $teacher_id = $_GET['teacher_id'] ?? 0;
            
            try {
                // Verify teacher belongs to this department
                $teacher = $DB->get_record_sql(
                    "SELECT u.*, cu.departmentid
                     FROM {user} u
                     JOIN {company_users} cu ON u.id = cu.userid
                     WHERE u.id = ? AND cu.departmentid = ?",
                    [$teacher_id, $company_user->departmentid]
                );
                
                if (!$teacher) {
                    throw new Exception('Teacher not found in your department');
                }
                
                // Get courses they're teaching
                $courses = $DB->get_records_sql(
                    "SELECT DISTINCT c.id, c.fullname, c.shortname,
                            COUNT(DISTINCT ue.userid) as enrolled_students
                     FROM {role_assignments} ra
                     JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = ?
                     JOIN {course} c ON ctx.instanceid = c.id
                     LEFT JOIN {enrol} e ON e.courseid = c.id
                     LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                     WHERE ra.userid = ?
                     GROUP BY c.id
                     ORDER BY c.fullname",
                    [CONTEXT_COURSE, $teacher_id]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'teacher' => $teacher,
                    'courses' => array_values($courses)
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

<div class="teachers-management-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Teachers Management</h1>
            <p class="page-subtitle">Manage all teachers in <?php echo format_string($department->name); ?></p>
        </div>
        <div class="header-actions">
            <button class="btn-action" onclick="window.location.href='<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/assign_teachers.php'">
                <i class="fa fa-user-plus"></i> Assign Teachers
            </button>
            <button class="btn-action btn-secondary" onclick="exportTeachers()">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <span class="search-icon"><i class="fa fa-search"></i></span>
            <input type="text" class="search-input" placeholder="Search teachers..." id="teacherSearch">
        </div>
        <div class="view-controls">
            <select class="per-page-select" id="perPageSelect">
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <!-- Teachers Grid -->
    <div class="teachers-grid" id="teachersGrid">
        <div class="loading-state">
            <i class="fa fa-spinner fa-spin"></i> Loading teachers...
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls" id="paginationControls">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<style>
    .teachers-management-page {
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

    .per-page-select {
        padding: 0.75rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.95rem;
        cursor: pointer;
    }

    /* Teachers Grid */
    .teachers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .loading-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .teacher-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .teacher-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        border-color: #667eea;
    }

    .teacher-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .teacher-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .teacher-info {
        flex: 1;
    }

    .teacher-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.25rem 0;
    }

    .teacher-role {
        font-size: 0.85rem;
        color: #6c757d;
        margin: 0;
    }

    .teacher-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .stat-item {
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

    .teacher-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
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

    .teacher-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-teacher-action {
        flex: 1;
        padding: 0.625rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-teacher-action:hover {
        background: #5568d3;
    }

    .btn-teacher-action.secondary {
        background: #6c757d;
    }

    .btn-teacher-action.secondary:hover {
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

        .teachers-grid {
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
let currentSort = 'firstname';
let currentOrder = 'ASC';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadTeachers();
    
    // Search functionality
    document.getElementById('teacherSearch').addEventListener('input', function(e) {
        currentSearch = e.target.value;
        currentPage = 1;
        loadTeachers();
    });
    
    // Per page select
    document.getElementById('perPageSelect').addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        loadTeachers();
    });
});

function loadTeachers() {
    const grid = document.getElementById('teachersGrid');
    grid.innerHTML = '<div class="loading-state"><i class="fa fa-spinner fa-spin"></i> Loading teachers...</div>';
    
    const params = new URLSearchParams({
        action: 'get_teachers',
        page: currentPage,
        per_page: perPage,
        sort: currentSort,
        order: currentOrder,
        search: currentSearch
    });
    
    fetch('?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderTeachersGrid(data.teachers);
                renderPagination(data.total_count, data.page, data.total_pages);
            } else {
                grid.innerHTML = '<div class="loading-state">Error loading teachers</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            grid.innerHTML = '<div class="loading-state">Error loading teachers</div>';
        });
}

function renderTeachersGrid(teachers) {
    const grid = document.getElementById('teachersGrid');
    
    if (teachers.length === 0) {
        grid.innerHTML = '<div class="loading-state">No teachers found</div>';
        return;
    }
    
    let html = '';
    teachers.forEach(teacher => {
        const initials = teacher.firstname.charAt(0) + teacher.lastname.charAt(0);
        const lastAccess = teacher.lastaccess ? new Date(teacher.lastaccess * 1000).toLocaleDateString() : 'Never';
        const roleText = teacher.role_type === 'editingteacher' ? 'Editing Teacher' : 'Teacher';
        
        html += `
            <div class="teacher-card">
                <div class="teacher-header">
                    <div class="teacher-avatar">${initials.toUpperCase()}</div>
                    <div class="teacher-info">
                        <h3 class="teacher-name">${teacher.firstname} ${teacher.lastname}</h3>
                        <p class="teacher-role">${roleText}</p>
                    </div>
                </div>
                
                <div class="teacher-stats">
                    <div class="stat-item">
                        <div class="stat-value">${teacher.courses_teaching || 0}</div>
                        <div class="stat-label">Courses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${teacher.student_count || 0}</div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                
                <div class="teacher-meta">
                    <div class="meta-item">
                        <i class="fa fa-envelope meta-icon"></i>
                        <span>${teacher.email}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fa fa-clock meta-icon"></i>
                        <span>Last access: ${lastAccess}</span>
                    </div>
                </div>
                
                <div class="teacher-actions">
                    <button class="btn-teacher-action" onclick="viewTeacherDetails(${teacher.id})">
                        <i class="fa fa-eye"></i> View Details
                    </button>
                    <button class="btn-teacher-action secondary" onclick="viewTeacherCourses(${teacher.id})">
                        <i class="fa fa-book"></i> Courses
                    </button>
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
            Showing ${start} to ${end} of ${totalCount} teachers
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
    loadTeachers();
}

function viewTeacherDetails(teacherId) {
    alert('Teacher details view will be implemented soon. Teacher ID: ' + teacherId);
}

function viewTeacherCourses(teacherId) {
    alert('Teacher courses view will be implemented soon. Teacher ID: ' + teacherId);
}

function exportTeachers() {
    alert('Export functionality will be implemented soon.');
}
</script>

<?php
echo '</div>'; // Close school-manager-main-content
echo $OUTPUT->footer();
?>


