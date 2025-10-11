<?php
/**
 * School Manager - Students Management
 * View and manage all students in the school/department
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
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager students page - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/students.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Students Management');
$PAGE->set_heading('Students Management');
$PAGE->add_body_class('school-manager-students-page');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_students':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'firstname';
            $order = $_GET['order'] ?? 'ASC';
            $filter = $_GET['filter'] ?? 'all';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Base SQL for students in this department
                $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.lastaccess,
                               u.timecreated,
                               COUNT(DISTINCT ue.id) as enrolled_courses,
                               COUNT(DISTINCT cc.id) as completed_courses
                        FROM {company_users} cu
                        JOIN {user} u ON cu.userid = u.id
                        LEFT JOIN {user_enrolments} ue ON u.id = ue.userid
                        LEFT JOIN {enrol} e ON ue.enrolid = e.id
                        LEFT JOIN {course_completions} cc ON u.id = cc.userid AND cc.timecompleted IS NOT NULL
                        WHERE cu.departmentid = ? 
                        AND cu.managertype = 0 
                        AND u.deleted = 0 
                        AND cu.suspended = 0";
                
                $params = [$company_user->departmentid];
                
                // Add search filter
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                // Add activity filter
                if ($filter === 'active') {
                    $sql .= " AND u.lastaccess > ?";
                    $params[] = time() - (30 * 24 * 60 * 60);
                } elseif ($filter === 'inactive') {
                    $sql .= " AND (u.lastaccess < ? OR u.lastaccess IS NULL)";
                    $params[] = time() - (30 * 24 * 60 * 60);
                }
                
                $sql .= " GROUP BY u.id ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $students = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT u.id) 
                              FROM {company_users} cu
                              JOIN {user} u ON cu.userid = u.id
                              WHERE cu.departmentid = ? 
                              AND cu.managertype = 0 
                              AND u.deleted = 0 
                              AND cu.suspended = 0";
                $count_params = [$company_user->departmentid];
                
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                if ($filter === 'active') {
                    $count_sql .= " AND u.lastaccess > ?";
                    $count_params[] = time() - (30 * 24 * 60 * 60);
                } elseif ($filter === 'inactive') {
                    $count_sql .= " AND (u.lastaccess < ? OR u.lastaccess IS NULL)";
                    $count_params[] = time() - (30 * 24 * 60 * 60);
                }
                
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'students' => array_values($students),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_student_details':
            $student_id = $_GET['student_id'] ?? 0;
            
            try {
                // Verify student belongs to this department
                $student_dept = $DB->get_record_sql(
                    "SELECT cu.*, u.firstname, u.lastname, u.email, u.lastaccess
                     FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     WHERE cu.userid = ? AND cu.departmentid = ?",
                    [$student_id, $company_user->departmentid]
                );
                
                if (!$student_dept) {
                    throw new Exception('Student not found in your department');
                }
                
                // Get enrolled courses
                $courses = $DB->get_records_sql(
                    "SELECT c.id, c.fullname, c.shortname, ue.timecreated as enrollment_date,
                            cc.timecompleted
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON ue.enrolid = e.id
                     JOIN {course} c ON e.courseid = c.id
                     LEFT JOIN {course_completions} cc ON c.id = cc.course AND cc.userid = ue.userid
                     WHERE ue.userid = ?
                     ORDER BY ue.timecreated DESC",
                    [$student_id]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'student' => $student_dept,
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

<div class="students-management-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Students Management</h1>
            <p class="page-subtitle">Manage all students in <?php echo format_string($department->name); ?></p>
        </div>
        <div class="header-actions">
            <button class="btn-action" onclick="window.location.href='<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/student_enrollment.php'">
                <i class="fa fa-user-plus"></i> Enroll Students
            </button>
            <button class="btn-action btn-secondary" onclick="exportStudents()">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <span class="search-icon"><i class="fa fa-search"></i></span>
            <input type="text" class="search-input" placeholder="Search students..." id="studentSearch">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Students</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="inactive">Inactive</button>
        </div>
        <div class="view-controls">
            <select class="per-page-select" id="perPageSelect">
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <!-- Students Table -->
    <div class="students-table-wrapper">
        <table class="students-table" id="studentsTable">
            <thead>
                <tr>
                    <th class="sortable" data-sort="firstname">
                        Full Name <i class="fa fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="email">
                        Email <i class="fa fa-sort"></i>
                    </th>
                    <th class="sortable" data-sort="lastaccess">
                        Last Access <i class="fa fa-sort"></i>
                    </th>
                    <th>Enrolled Courses</th>
                    <th>Completed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <tr>
                    <td colspan="6" class="table-loading">
                        <i class="fa fa-spinner fa-spin"></i> Loading students...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls" id="paginationControls">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<style>
    .students-management-page {
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
        margin-bottom: 1.5rem;
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

    /* Students Table */
    .students-table-wrapper {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
    }

    .students-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .students-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .students-table th.sortable {
        cursor: pointer;
        user-select: none;
    }

    .students-table th.sortable:hover {
        background: #e9ecef;
    }

    .students-table td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .students-table tbody tr:hover {
        background: #f8f9fa;
    }

    .table-loading {
        text-align: center;
        padding: 3rem !important;
        color: #6c757d;
    }

    .student-name {
        font-weight: 600;
        color: #2c3e50;
    }

    .student-email {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .last-access-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .badge-active {
        background: #d4edda;
        color: #155724;
    }

    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .course-count {
        font-weight: 600;
        color: #667eea;
    }

    .btn-view-details {
        padding: 0.5rem 1rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .btn-view-details:hover {
        background: #5568d3;
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

        .students-table-wrapper {
            overflow-x: auto;
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
let currentFilter = 'all';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    
    // Search functionality
    document.getElementById('studentSearch').addEventListener('input', function(e) {
        currentSearch = e.target.value;
        currentPage = 1;
        loadStudents();
    });
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadStudents();
        });
    });
    
    // Per page select
    document.getElementById('perPageSelect').addEventListener('change', function() {
        perPage = parseInt(this.value);
        currentPage = 1;
        loadStudents();
    });
    
    // Sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sort = this.dataset.sort;
            if (currentSort === sort) {
                currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = sort;
                currentOrder = 'ASC';
            }
            loadStudents();
        });
    });
});

function loadStudents() {
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="table-loading"><i class="fa fa-spinner fa-spin"></i> Loading students...</td></tr>';
    
    const params = new URLSearchParams({
        action: 'get_students',
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
                renderStudentsTable(data.students);
                renderPagination(data.total_count, data.page, data.total_pages);
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="table-loading">Error loading students</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="table-loading">Error loading students</td></tr>';
        });
}

function renderStudentsTable(students) {
    const tbody = document.getElementById('studentsTableBody');
    
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-loading">No students found</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach(student => {
        const lastAccess = student.lastaccess ? new Date(student.lastaccess * 1000).toLocaleDateString() : 'Never';
        const isActive = student.lastaccess && (student.lastaccess > (Date.now() / 1000 - 30 * 24 * 60 * 60));
        const badgeClass = isActive ? 'badge-active' : 'badge-inactive';
        const badgeText = isActive ? 'Active' : 'Inactive';
        
        html += `
            <tr>
                <td class="student-name">${student.firstname} ${student.lastname}</td>
                <td class="student-email">${student.email}</td>
                <td>
                    <span class="last-access-badge ${badgeClass}">${badgeText}</span><br>
                    <small>${lastAccess}</small>
                </td>
                <td class="course-count">${student.enrolled_courses || 0}</td>
                <td class="course-count">${student.completed_courses || 0}</td>
                <td>
                    <button class="btn-view-details" onclick="viewStudentDetails(${student.id})">
                        <i class="fa fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function renderPagination(totalCount, currentPage, totalPages) {
    const container = document.getElementById('paginationControls');
    
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, totalCount);
    
    let html = `
        <div class="pagination-info">
            Showing ${start} to ${end} of ${totalCount} students
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
    loadStudents();
}

function viewStudentDetails(studentId) {
    // This will be implemented in a future update
    alert('Student details view will be implemented soon. Student ID: ' + studentId);
}

function exportStudents() {
    alert('Export functionality will be implemented soon.');
}
</script>

<?php
echo '</div>'; // Close school-manager-main-content
echo $OUTPUT->footer();
?>


