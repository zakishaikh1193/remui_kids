<?php
/**
 * Add Users Content for School Manager Dashboard
 * AJAX content for the add users page
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

<div class="add-users-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fa fa-user-plus"></i>
                Add Users
            </h1>
            <p class="page-subtitle">Add new students and teachers to your school</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddUserModal()">
                <i class="fa fa-plus"></i> Add User
            </button>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="user-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT cu.userid) FROM {company_users} cu JOIN {user} u ON cu.userid = u.id WHERE cu.departmentid = ? AND cu.managertype = 0 AND u.deleted = 0", [$departmentid]); ?></h3>
                <p class="stat-label">Total Students</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT cu.userid) FROM {company_users} cu JOIN {user} u ON cu.userid = u.id JOIN {role_assignments} ra ON u.id = ra.userid JOIN {role} r ON ra.roleid = r.id WHERE cu.departmentid = ? AND r.shortname IN ('editingteacher', 'teacher') AND u.deleted = 0", [$departmentid]); ?></h3>
                <p class="stat-label">Total Teachers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-user-check"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT cu.userid) FROM {company_users} cu JOIN {user} u ON cu.userid = u.id WHERE cu.departmentid = ? AND u.lastaccess > ?", [$departmentid, time() - (30 * 24 * 60 * 60)]); ?></h3>
                <p class="stat-label">Active Users</p>
            </div>
        </div>
    </div>

    <!-- Add User Forms -->
    <div class="add-user-forms">
        <div class="forms-container">
            <!-- Quick Add Student -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fa fa-graduation-cap"></i>
                    Quick Add Student
                </h2>
                <form id="quickAddStudentForm" class="user-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select class="form-control" name="grade">
                                <option value="">Select Grade</option>
                                <option value="1">Grade 1</option>
                                <option value="2">Grade 2</option>
                                <option value="3">Grade 3</option>
                                <option value="4">Grade 4</option>
                                <option value="5">Grade 5</option>
                                <option value="6">Grade 6</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add Student
                    </button>
                </form>
            </div>

            <!-- Quick Add Teacher -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fa fa-chalkboard-teacher"></i>
                    Quick Add Teacher
                </h2>
                <form id="quickAddTeacherForm" class="user-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Specialization</label>
                            <select class="form-control" name="subject">
                                <option value="">Select Subject</option>
                                <option value="math">Mathematics</option>
                                <option value="science">Science</option>
                                <option value="english">English</option>
                                <option value="history">History</option>
                                <option value="geography">Geography</option>
                                <option value="art">Art</option>
                                <option value="music">Music</option>
                                <option value="pe">Physical Education</option>
                                <option value="computer">Computer Science</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-plus"></i> Add Teacher
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="recent-users-section">
        <h2 class="section-title">Recent Users Added</h2>
        <div class="recent-users-table-container">
            <table class="table table-striped recent-users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Added Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_users = $DB->get_records_sql("
                        SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.timecreated,
                               CASE WHEN r.shortname IN ('editingteacher', 'teacher') THEN 'Teacher' ELSE 'Student' END as role
                        FROM {company_users} cu
                        JOIN {user} u ON cu.userid = u.id
                        LEFT JOIN {role_assignments} ra ON u.id = ra.userid
                        LEFT JOIN {role} r ON ra.roleid = r.id
                        WHERE cu.departmentid = ? AND u.deleted = 0
                        ORDER BY u.timecreated DESC
                        LIMIT 10
                    ", [$departmentid]);
                    
                    foreach ($recent_users as $user) {
                        $status = $user->timecreated > (time() - (7 * 24 * 60 * 60)) ? 'New' : 'Active';
                        $status_class = $status == 'New' ? 'success' : 'info';
                        
                        echo "<tr>";
                        echo "<td>{$user->firstname} {$user->lastname}</td>";
                        echo "<td>{$user->username}</td>";
                        echo "<td>{$user->email}</td>";
                        echo "<td><span class='badge badge-" . (strtolower($user->role) == 'teacher' ? 'primary' : 'secondary') . "'>{$user->role}</span></td>";
                        echo "<td>" . date('M d, Y', $user->timecreated) . "</td>";
                        echo "<td><span class='badge badge-{$status_class}'>{$status}</span></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showAddUserModal() {
    // This would show a more detailed modal for adding users
    alert('Advanced user addition modal would open here');
}

// Form submission handlers
document.getElementById('quickAddStudentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Simulate adding student
    alert('Student would be added with the provided information');
    this.reset();
});

document.getElementById('quickAddTeacherForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Simulate adding teacher
    alert('Teacher would be added with the provided information');
    this.reset();
});
</script>

<style>
.add-users-page {
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

.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.user-stats-grid .stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.user-stats-grid .stat-icon {
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

.user-stats-grid .stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.user-stats-grid .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.add-user-forms {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.forms-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.form-section {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
}

.form-section .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1.5rem 0;
    display: flex;
    align-items: center;
}

.form-section .section-title i {
    margin-right: 0.5rem;
    color: #667eea;
}

.user-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.recent-users-section {
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

.recent-users-table-container {
    overflow-x: auto;
}

.recent-users-table {
    width: 100%;
    border-collapse: collapse;
}

.recent-users-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.recent-users-table td {
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

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background: #f3f4f6;
    color: #374151;
}

@media (max-width: 768px) {
    .forms-container {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

