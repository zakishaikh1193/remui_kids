<?php
/**
 * Enrollments Content for School Manager Dashboard
 * AJAX content for the enrollments page
 */

// Get current user context
global $USER, $DB, $CFG;

// Security check - ensure user is logged in
if (!isloggedin()) {
    echo '<div class="alert alert-danger">Access denied. Please log in.</div>';
    exit;
}

// Get school manager's department
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);
if (!$company_user || ($company_user->managertype != 1 && $company_user->managertype != 2)) {
    echo '<div class="alert alert-danger">Access denied. School manager access required.</div>';
    exit;
}

$departmentid = $company_user->departmentid;
?>

<div class="enrollments-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fa fa-user-plus"></i>
                Student Enrollments
            </h1>
            <p class="page-subtitle">Manage student enrollments and course assignments</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showEnrollStudentModal()">
                <i class="fa fa-plus"></i> Enroll Student
            </button>
        </div>
    </div>

    <!-- Enrollment Statistics -->
    <div class="enrollment-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {company_course} cc ON e.courseid = cc.courseid WHERE cc.departmentid = ?", [$departmentid]); ?></h3>
                <p class="stat-label">Total Enrollments</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-book"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT cc.courseid) FROM {company_course} cc WHERE cc.departmentid = ?", [$departmentid]); ?></h3>
                <p class="stat-label">Available Courses</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT cc.id) FROM {course_completions} cc JOIN {company_users} cu ON cc.userid = cu.userid WHERE cu.departmentid = ? AND cc.timecompleted IS NOT NULL", [$departmentid]); ?></h3>
                <p class="stat-label">Completed Courses</p>
            </div>
        </div>
    </div>

    <!-- Enrollment Management -->
    <div class="enrollment-management">
        <div class="management-section">
            <h2 class="section-title">Enrollment Management</h2>
            
            <!-- Search and Filter -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <input type="text" id="enrollment-search" placeholder="Search students or courses..." class="form-control">
                    <i class="fa fa-search"></i>
                </div>
                <div class="filter-options">
                    <select id="course-filter" class="form-control">
                        <option value="">All Courses</option>
                        <?php
                        $courses = $DB->get_records_sql("SELECT DISTINCT c.id, c.fullname FROM {course} c JOIN {company_course} cc ON c.id = cc.courseid WHERE cc.departmentid = ? AND c.id > 1", [$departmentid]);
                        foreach ($courses as $course) {
                            echo "<option value='{$course->id}'>{$course->fullname}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Enrollment Table -->
            <div class="enrollment-table-container">
                <table class="table table-striped enrollment-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="enrollment-table-body">
                        <?php
                        $enrollments = $DB->get_records_sql("
                            SELECT u.id, u.firstname, u.lastname, c.fullname as coursename, 
                                   ue.timecreated, ue.status, cc.timecompleted
                            FROM {user_enrolments} ue
                            JOIN {user} u ON ue.userid = u.id
                            JOIN {enrol} e ON ue.enrolid = e.id
                            JOIN {course} c ON e.courseid = c.id
                            JOIN {company_course} cc2 ON c.id = cc2.courseid
                            LEFT JOIN {course_completions} cc ON u.id = cc.userid AND c.id = cc.courseid
                            WHERE cc2.departmentid = ? AND u.deleted = 0
                            ORDER BY ue.timecreated DESC
                            LIMIT 20
                        ", [$departmentid]);
                        
                        foreach ($enrollments as $enrollment) {
                            $status = $enrollment->status == 0 ? 'Active' : 'Suspended';
                            $progress = $enrollment->timecompleted ? 'Completed' : 'In Progress';
                            $status_class = $enrollment->status == 0 ? 'success' : 'warning';
                            $progress_class = $enrollment->timecompleted ? 'success' : 'info';
                            
                            echo "<tr>";
                            echo "<td>{$enrollment->firstname} {$enrollment->lastname}</td>";
                            echo "<td>{$enrollment->coursename}</td>";
                            echo "<td>" . date('M d, Y', $enrollment->timecreated) . "</td>";
                            echo "<td><span class='badge badge-{$status_class}'>{$status}</span></td>";
                            echo "<td><span class='badge badge-{$progress_class}'>{$progress}</span></td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-outline-primary' onclick='viewEnrollment({$enrollment->id})'>
                                        <i class='fa fa-eye'></i>
                                    </button>
                                    <button class='btn btn-sm btn-outline-warning' onclick='editEnrollment({$enrollment->id})'>
                                        <i class='fa fa-edit'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Enrollment Modal -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enroll Student</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="enrollStudentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Select Student</label>
                                <select class="form-control" id="student-select" required>
                                    <option value="">Choose a student...</option>
                                    <?php
                                    $students = $DB->get_records_sql("
                                        SELECT u.id, u.firstname, u.lastname 
                                        FROM {company_users} cu 
                                        JOIN {user} u ON cu.userid = u.id 
                                        WHERE cu.departmentid = ? AND cu.managertype = 0 AND u.deleted = 0
                                        ORDER BY u.firstname, u.lastname
                                    ", [$departmentid]);
                                    
                                    foreach ($students as $student) {
                                        echo "<option value='{$student->id}'>{$student->firstname} {$student->lastname}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Select Course</label>
                                <select class="form-control" id="course-select" required>
                                    <option value="">Choose a course...</option>
                                    <?php
                                    foreach ($courses as $course) {
                                        echo "<option value='{$course->id}'>{$course->fullname}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processEnrollment()">Enroll Student</button>
            </div>
        </div>
    </div>
</div>

<script>
function showEnrollStudentModal() {
    $('#enrollStudentModal').modal('show');
}

function processEnrollment() {
    const studentId = document.getElementById('student-select').value;
    const courseId = document.getElementById('course-select').value;
    
    if (!studentId || !courseId) {
        alert('Please select both student and course');
        return;
    }
    
    // Here you would typically make an AJAX call to enroll the student
    alert('Enrollment functionality would be implemented here');
    $('#enrollStudentModal').modal('hide');
}

function viewEnrollment(enrollmentId) {
    alert('View enrollment details for ID: ' + enrollmentId);
}

function editEnrollment(enrollmentId) {
    alert('Edit enrollment for ID: ' + enrollmentId);
}

// Search functionality
document.getElementById('enrollment-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#enrollment-table-body tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filter functionality
document.getElementById('course-filter').addEventListener('change', function() {
    const selectedCourse = this.value;
    const rows = document.querySelectorAll('#enrollment-table-body tr');
    
    rows.forEach(row => {
        if (!selectedCourse) {
            row.style.display = '';
        } else {
            const courseCell = row.cells[1];
            const courseId = courseCell.textContent.trim();
            row.style.display = courseId.includes(selectedCourse) ? '' : 'none';
        }
    });
});
</script>

<style>
.enrollments-page {
    padding: 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.page-subtitle {
    color: #6b7280;
    margin: 0.5rem 0 0 0;
}

.enrollment-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.enrollment-stats-grid .stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.enrollment-stats-grid .stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.25rem;
}

.enrollment-stats-grid .stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.enrollment-stats-grid .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.management-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1.5rem 0;
}

.search-filter-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.search-box {
    position: relative;
    flex: 1;
}

.search-box input {
    padding-left: 2.5rem;
}

.search-box i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.filter-options {
    min-width: 200px;
}

.enrollment-table-container {
    overflow-x: auto;
}

.enrollment-table {
    width: 100%;
    border-collapse: collapse;
}

.enrollment-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.enrollment-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.btn-outline-primary {
    border-color: #3b82f6;
    color: #3b82f6;
}

.btn-outline-primary:hover {
    background: #3b82f6;
    color: white;
}

.btn-outline-warning {
    border-color: #f59e0b;
    color: #f59e0b;
}

.btn-outline-warning:hover {
    background: #f59e0b;
    color: white;
}
</style>

