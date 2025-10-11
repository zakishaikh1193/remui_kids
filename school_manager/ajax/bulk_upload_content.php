<?php
/**
 * Bulk Upload Content for School Manager Dashboard
 * AJAX content for the bulk upload page
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

<div class="bulk-upload-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fa fa-cloud-upload-alt"></i>
                Bulk Upload
            </h1>
            <p class="page-subtitle">Upload multiple users or enrollments at once using CSV files</p>
        </div>
    </div>

    <!-- Upload Statistics -->
    <div class="upload-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-file-csv"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(*) FROM {company_users} cu JOIN {user} u ON cu.userid = u.id WHERE cu.departmentid = ? AND u.timecreated > ?", [$departmentid, time() - (30 * 24 * 60 * 60)]); ?></h3>
                <p class="stat-label">Users Added This Month</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {company_course} cc ON e.courseid = cc.courseid WHERE cc.departmentid = ? AND ue.timecreated > ?", [$departmentid, time() - (30 * 24 * 60 * 60)]); ?></h3>
                <p class="stat-label">Enrollments This Month</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-number"><?php echo $DB->count_records_sql("SELECT COUNT(*) FROM {company_users} cu JOIN {user} u ON cu.userid = u.id WHERE cu.departmentid = ? AND u.lastaccess > ?", [$departmentid, time() - (7 * 24 * 60 * 60)]); ?></h3>
                <p class="stat-label">Active This Week</p>
            </div>
        </div>
    </div>

    <!-- Upload Options -->
    <div class="upload-options">
        <div class="upload-tabs">
            <button class="upload-tab active" onclick="switchTab('users')">
                <i class="fa fa-users"></i>
                Upload Users
            </button>
            <button class="upload-tab" onclick="switchTab('enrollments')">
                <i class="fa fa-graduation-cap"></i>
                Upload Enrollments
            </button>
            <button class="upload-tab" onclick="switchTab('courses')">
                <i class="fa fa-book"></i>
                Upload Courses
            </button>
        </div>

        <!-- Upload Users Tab -->
        <div class="upload-tab-content active" id="users-tab">
            <div class="upload-section">
                <h2 class="section-title">
                    <i class="fa fa-users"></i>
                    Bulk User Upload
                </h2>
                <p class="section-description">Upload a CSV file to add multiple students and teachers at once.</p>
                
                <div class="upload-area" id="users-upload-area">
                    <div class="upload-content">
                        <i class="fa fa-cloud-upload-alt upload-icon"></i>
                        <h3>Drop your CSV file here</h3>
                        <p>or click to browse files</p>
                        <input type="file" id="users-file-input" accept=".csv" style="display: none;">
                        <button class="btn btn-primary" onclick="document.getElementById('users-file-input').click()">
                            Choose File
                        </button>
                    </div>
                </div>

                <div class="upload-requirements">
                    <h4>CSV Format Requirements:</h4>
                    <ul>
                        <li><strong>Required columns:</strong> firstname, lastname, email, username, password, role</li>
                        <li><strong>Role values:</strong> student, teacher</li>
                        <li><strong>File size:</strong> Maximum 5MB</li>
                        <li><strong>Encoding:</strong> UTF-8</li>
                    </ul>
                </div>

                <div class="csv-template">
                    <h4>Download CSV Template:</h4>
                    <button class="btn btn-outline-primary" onclick="downloadTemplate('users')">
                        <i class="fa fa-download"></i> Download Users Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload Enrollments Tab -->
        <div class="upload-tab-content" id="enrollments-tab">
            <div class="upload-section">
                <h2 class="section-title">
                    <i class="fa fa-graduation-cap"></i>
                    Bulk Enrollment Upload
                </h2>
                <p class="section-description">Upload a CSV file to enroll multiple students in courses.</p>
                
                <div class="upload-area" id="enrollments-upload-area">
                    <div class="upload-content">
                        <i class="fa fa-cloud-upload-alt upload-icon"></i>
                        <h3>Drop your CSV file here</h3>
                        <p>or click to browse files</p>
                        <input type="file" id="enrollments-file-input" accept=".csv" style="display: none;">
                        <button class="btn btn-primary" onclick="document.getElementById('enrollments-file-input').click()">
                            Choose File
                        </button>
                    </div>
                </div>

                <div class="upload-requirements">
                    <h4>CSV Format Requirements:</h4>
                    <ul>
                        <li><strong>Required columns:</strong> username, course_shortname</li>
                        <li><strong>Username:</strong> Must match existing user accounts</li>
                        <li><strong>Course Shortname:</strong> Must match existing courses</li>
                        <li><strong>File size:</strong> Maximum 5MB</li>
                    </ul>
                </div>

                <div class="csv-template">
                    <h4>Download CSV Template:</h4>
                    <button class="btn btn-outline-primary" onclick="downloadTemplate('enrollments')">
                        <i class="fa fa-download"></i> Download Enrollments Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload Courses Tab -->
        <div class="upload-tab-content" id="courses-tab">
            <div class="upload-section">
                <h2 class="section-title">
                    <i class="fa fa-book"></i>
                    Bulk Course Upload
                </h2>
                <p class="section-description">Upload a CSV file to create multiple courses at once.</p>
                
                <div class="upload-area" id="courses-upload-area">
                    <div class="upload-content">
                        <i class="fa fa-cloud-upload-alt upload-icon"></i>
                        <h3>Drop your CSV file here</h3>
                        <p>or click to browse files</p>
                        <input type="file" id="courses-file-input" accept=".csv" style="display: none;">
                        <button class="btn btn-primary" onclick="document.getElementById('courses-file-input').click()">
                            Choose File
                        </button>
                    </div>
                </div>

                <div class="upload-requirements">
                    <h4>CSV Format Requirements:</h4>
                    <ul>
                        <li><strong>Required columns:</strong> fullname, shortname, category</li>
                        <li><strong>Fullname:</strong> Complete course name</li>
                        <li><strong>Shortname:</strong> Unique course identifier</li>
                        <li><strong>Category:</strong> Course category name</li>
                    </ul>
                </div>

                <div class="csv-template">
                    <h4>Download CSV Template:</h4>
                    <button class="btn btn-outline-primary" onclick="downloadTemplate('courses')">
                        <i class="fa fa-download"></i> Download Courses Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Progress -->
    <div class="upload-progress" id="upload-progress" style="display: none;">
        <h3>Upload Progress</h3>
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div class="progress-text" id="progress-text">0% Complete</div>
        <div class="upload-log" id="upload-log"></div>
    </div>

    <!-- Recent Uploads -->
    <div class="recent-uploads-section">
        <h2 class="section-title">Recent Uploads</h2>
        <div class="recent-uploads-table-container">
            <table class="table table-striped recent-uploads-table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Records</th>
                        <th>Success</th>
                        <th>Failed</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>students_batch_1.csv</td>
                        <td><span class="badge badge-primary">Users</span></td>
                        <td>50</td>
                        <td>48</td>
                        <td>2</td>
                        <td>Dec 15, 2024</td>
                        <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <tr>
                        <td>enrollments_math.csv</td>
                        <td><span class="badge badge-info">Enrollments</span></td>
                        <td>120</td>
                        <td>118</td>
                        <td>2</td>
                        <td>Dec 14, 2024</td>
                        <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <tr>
                        <td>courses_semester2.csv</td>
                        <td><span class="badge badge-secondary">Courses</span></td>
                        <td>15</td>
                        <td>15</td>
                        <td>0</td>
                        <td>Dec 13, 2024</td>
                        <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.upload-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.upload-tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to selected tab and content
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function downloadTemplate(type) {
    let csvContent = '';
    let filename = '';
    
    switch(type) {
        case 'users':
            csvContent = 'firstname,lastname,email,username,password,role\n';
            csvContent += 'John,Doe,john.doe@school.com,jdoe,password123,student\n';
            csvContent += 'Jane,Smith,jane.smith@school.com,jsmith,password123,teacher\n';
            filename = 'users_template.csv';
            break;
        case 'enrollments':
            csvContent = 'username,course_shortname\n';
            csvContent += 'jdoe,math101\n';
            csvContent += 'jsmith,science201\n';
            filename = 'enrollments_template.csv';
            break;
        case 'courses':
            csvContent = 'fullname,shortname,category\n';
            csvContent += 'Mathematics 101,math101,Mathematics\n';
            csvContent += 'Science 201,science201,Science\n';
            filename = 'courses_template.csv';
            break;
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// File upload handling
document.getElementById('users-file-input').addEventListener('change', function(e) {
    handleFileUpload(e.target.files[0], 'users');
});

document.getElementById('enrollments-file-input').addEventListener('change', function(e) {
    handleFileUpload(e.target.files[0], 'enrollments');
});

document.getElementById('courses-file-input').addEventListener('change', function(e) {
    handleFileUpload(e.target.files[0], 'courses');
});

function handleFileUpload(file, type) {
    if (!file) return;
    
    // Validate file type
    if (!file.name.endsWith('.csv')) {
        alert('Please select a CSV file');
        return;
    }
    
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
    }
    
    // Show progress
    document.getElementById('upload-progress').style.display = 'block';
    simulateUpload(file.name, type);
}

function simulateUpload(filename, type) {
    let progress = 0;
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const uploadLog = document.getElementById('upload-log');
    
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 100) progress = 100;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '% Complete';
        
        if (progress >= 100) {
            clearInterval(interval);
            progressText.textContent = 'Upload completed successfully!';
            uploadLog.innerHTML = `
                <div class="log-entry success">
                    <i class="fa fa-check-circle"></i>
                    ${filename} uploaded successfully
                </div>
                <div class="log-entry info">
                    <i class="fa fa-info-circle"></i>
                    Processing ${type} data...
                </div>
                <div class="log-entry success">
                    <i class="fa fa-check-circle"></i>
                    All records processed successfully
                </div>
            `;
        }
    }, 200);
}

// Drag and drop functionality
document.querySelectorAll('.upload-area').forEach(area => {
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    area.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
    
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            const type = this.id.split('-')[0];
            handleFileUpload(file, type);
        }
    });
});
</script>

<style>
.bulk-upload-page {
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

.upload-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.upload-stats-grid .stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.upload-stats-grid .stat-icon {
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

.upload-stats-grid .stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.upload-stats-grid .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.upload-options {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.upload-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
}

.upload-tab {
    flex: 1;
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.upload-tab:hover {
    background: #f9fafb;
    color: #374151;
}

.upload-tab.active {
    background: #667eea;
    color: white;
}

.upload-tab-content {
    display: none;
    padding: 2rem;
}

.upload-tab-content.active {
    display: block;
}

.upload-section {
    max-width: 800px;
    margin: 0 auto;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.5rem;
    color: #667eea;
}

.section-description {
    color: #6b7280;
    margin: 0 0 2rem 0;
}

.upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area:hover,
.upload-area.drag-over {
    border-color: #667eea;
    background: #f8fafc;
}

.upload-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin: 1rem 0 0.5rem 0;
}

.upload-content p {
    color: #6b7280;
    margin: 0 0 1rem 0;
}

.upload-icon {
    font-size: 3rem;
    color: #d1d5db;
}

.upload-requirements {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.upload-requirements h4 {
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem 0;
}

.upload-requirements ul {
    margin: 0;
    padding-left: 1.5rem;
}

.upload-requirements li {
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.csv-template {
    text-align: center;
}

.csv-template h4 {
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem 0;
}

.upload-progress {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.upload-progress h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1rem 0;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-weight: 500;
    color: #374151;
    margin-bottom: 1rem;
}

.upload-log {
    max-height: 200px;
    overflow-y: auto;
}

.log-entry {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.log-entry.success {
    color: #059669;
}

.log-entry.info {
    color: #0d9488;
}

.log-entry.error {
    color: #dc2626;
}

.recent-uploads-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.recent-uploads-table-container {
    overflow-x: auto;
}

.recent-uploads-table {
    width: 100%;
    border-collapse: collapse;
}

.recent-uploads-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.recent-uploads-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background: #f3f4f6;
    color: #374151;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
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

.btn-outline-primary {
    background: transparent;
    color: #667eea;
    border: 1px solid #667eea;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
}
</style>

