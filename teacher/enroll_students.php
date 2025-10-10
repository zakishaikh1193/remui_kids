<?php
// Teacher's Student Enrollment Dashboard - Modern UI for enrolling students
require_once('../../../config.php');

// Require login and proper access.
require_login();

// Load necessary libraries after config
require_once($CFG->dirroot . '/course/lib.php');

// Check if user is teacher
$isteacher = false;
$teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher','manager')");
$roleids = array_keys($teacherroles);

if (!empty($roleids)) {
    $userroles = $DB->get_records_sql(
        "SELECT DISTINCT r.shortname 
         FROM {role} r 
         JOIN {role_assignments} ra ON r.id = ra.roleid 
         WHERE ra.userid = ? AND r.shortname IN ('editingteacher','teacher','manager')",
        [$USER->id]
    );
    
    if (!empty($userroles)) {
        $isteacher = true;
    }
}

if (is_siteadmin()) {
    $isteacher = true;
}

if (!$isteacher) {
    throw new moodle_exception('nopermissions', 'error', '', 'You must be a teacher to access this page');
}

// Set up the page.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/teacher/enroll_students.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title('Enroll Students - Teacher Dashboard');
$PAGE->set_heading('');

// Add breadcrumb.
$PAGE->navbar->add('Enroll Students');

// Handle enrollment/unenrollment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_sesskey(); // Security check
    
    $course_id = required_param('course_id', PARAM_INT);
    $user_id = required_param('user_id', PARAM_INT);
    $action = required_param('action', PARAM_ALPHA);
    
    try {
        if ($action === 'enroll') {
            // Get manual enrolment instance
            $manual_instance = $DB->get_record('enrol', [
                'courseid' => $course_id,
                'enrol' => 'manual',
                'status' => 0 // Active
            ], '*', IGNORE_MULTIPLE);
            
            if (!$manual_instance) {
                // Create manual enrolment instance if it doesn't exist
                $manual_plugin = enrol_get_plugin('manual');
                if ($manual_plugin) {
                    $manual_instance_id = $manual_plugin->add_instance($DB->get_record('course', ['id' => $course_id]));
                    $manual_instance = $DB->get_record('enrol', ['id' => $manual_instance_id]);
                }
            }
            
            if ($manual_instance) {
                // Get student role
                $student_role = $DB->get_record('role', ['shortname' => 'student']);
                
                // Check if already enrolled
                $existing = $DB->get_record('user_enrolments', [
                    'enrolid' => $manual_instance->id,
                    'userid' => $user_id
                ]);
                
                if (!$existing) {
                    // Enroll student
                    $manual_plugin = enrol_get_plugin('manual');
                    $manual_plugin->enrol_user($manual_instance, $user_id, $student_role->id);
                    $success_message = "Student enrolled successfully!";
                } else {
                    $error_message = "Student is already enrolled in this course.";
                }
            } else {
                $error_message = "Could not find or create manual enrollment instance.";
            }
            
        } elseif ($action === 'unenroll') {
            // Get manual enrolment instance
            $manual_instance = $DB->get_record('enrol', [
                'courseid' => $course_id,
                'enrol' => 'manual'
            ], '*', IGNORE_MULTIPLE);
            
            if ($manual_instance) {
                // Unenroll student
                $manual_plugin = enrol_get_plugin('manual');
                $manual_plugin->unenrol_user($manual_instance, $user_id);
                $success_message = "Student unenrolled successfully!";
            } else {
                $error_message = "Could not find manual enrollment instance.";
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    redirect(new moodle_url('/theme/remui_kids/teacher/enroll_students.php'), 
             isset($success_message) ? $success_message : (isset($error_message) ? $error_message : ''),
             null,
             isset($success_message) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR
    );
}

// Get teacher's courses
$teacher_courses = $DB->get_records_sql(
    "SELECT DISTINCT c.*
     FROM {course} c
     JOIN {context} ctx ON c.id = ctx.instanceid
     JOIN {role_assignments} ra ON ctx.id = ra.contextid
     JOIN {role} r ON ra.roleid = r.id
     WHERE ra.userid = ? AND r.shortname IN ('editingteacher','teacher','manager') 
     AND c.id > 1 AND c.visible = 1
     ORDER BY c.fullname ASC",
    [$USER->id]
);

// Get all students
$all_students = $DB->get_records_sql(
    "SELECT DISTINCT u.*
     FROM {user} u
     WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1
     ORDER BY u.firstname, u.lastname ASC"
);

echo $OUTPUT->header();

// Include Font Awesome CSS via HTML link tag
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';

// Add custom CSS including sidebar styles
echo '<style>
/* Neutralize the default main container */
#region-main,
[role="main"] {
    background: transparent !important;
    box-shadow: none !important;
    border: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Teacher Sidebar Styles */
.teacher-dashboard-wrapper {
    min-height: 100vh;
    background: #f8fafc;
    position: relative;
}

.sidebar-toggle {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: #4361ee;
    color: white;
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: #3f37c9;
    transform: scale(1.05);
}

.teacher-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    background: white;
    border-right: 1px solid #e9ecef;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
}

.teacher-sidebar.sidebar-open {
    transform: translateX(0);
}

.teacher-sidebar .sidebar-content {
    padding: 5rem 0 2rem 0;
}

.teacher-sidebar .sidebar-section {
    margin-bottom: 1.5rem;
}

.teacher-sidebar .sidebar-category {
    font-size: 0.7rem;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.8rem;
    padding: 0 1.5rem;
    margin-top: 0;
}

.teacher-sidebar .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.teacher-sidebar .sidebar-item {
    margin-bottom: 0.2rem;
}

.teacher-sidebar .sidebar-link {
    display: flex;
    align-items: center;
    padding: 0.7rem 1.5rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.teacher-sidebar .sidebar-link:hover {
    background-color: #f8f9fa;
    color: #4361ee;
    text-decoration: none;
    border-left-color: #4361ee;
}

.teacher-sidebar .sidebar-item.active .sidebar-link {
    background-color: #eef1ff;
    color: #4361ee;
    border-left-color: #4361ee;
}

.teacher-sidebar .sidebar-icon {
    width: 18px;
    height: 18px;
    margin-right: 0.7rem;
    text-align: center;
}

.teacher-sidebar .sidebar-text {
    font-weight: 500;
    font-size: 0.9rem;
}

.teacher-main-content {
    margin-left: 0;
    padding: 20px;
    transition: margin-left 0.3s ease;
    width: 100%;
    max-width: none;
    min-height: 100vh;
}

@media (min-width: 769px) {
    .sidebar-toggle {
        display: none;
    }
    
    .teacher-sidebar {
        transform: translateX(0);
    }
    
    .teacher-main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
    }
}

    /* Advanced Enrollment Dashboard Styles */
    .enrollment-dashboard {
        max-width: 1600px;
        margin: 0 auto;
        padding: 32px;
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        color: white;
        box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-header::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .header-content {
        position: relative;
        z-index: 2;
    }
    
    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 8px 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .dashboard-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0 0 24px 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 24px;
    }
    
    .stat-card {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.3);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        background: rgba(255,255,255,0.25);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 8px;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .courses-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 28px;
        margin-bottom: 32px;
    }
    
    .course-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e5e7eb;
        position: relative;
    }
    
    .course-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        border-color: #667eea;
    }
    
    .course-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 36px 32px;
        position: relative;
        overflow: hidden;
    }
    
    .course-header::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .course-header::after {
        content: "";
        position: absolute;
        bottom: -30%;
        left: -5%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .course-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 8px 0;
        position: relative;
        z-index: 2;
        letter-spacing: -0.02em;
    }
    
    .course-code {
        font-size: 1rem;
        opacity: 0.85;
        margin: 0 0 20px 0;
        position: relative;
        z-index: 2;
        font-weight: 500;
    }
    
    .course-stats {
        display: flex;
        gap: 20px;
        position: relative;
        z-index: 2;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    
    .course-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        background: rgba(255,255,255,0.3);
        padding: 10px 18px;
        border-radius: 14px;
        backdrop-filter: blur(15px);
        font-weight: 700;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .course-content {
        padding: 32px;
    }
    
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .section-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.02em;
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
    }
    
    .student-count {
        background: #f1f5f9;
        color: #475569;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 700;
        border: 1px solid #e2e8f0;
    }
    
    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    .student-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: visible;
        min-height: 80px;
    }
    
    .student-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 16px 0 0 16px;
    }
    
    .student-card:hover {
        border-color: #667eea;
        transform: translateX(4px);
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.12);
    }
    
    .student-card:hover::before {
        opacity: 1;
    }
    
    .student-info {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1;
        min-width: 0;
    }
    
    .student-avatar {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        position: relative;
        flex-shrink: 0;
    }
    
    .student-avatar::after {
        content: "";
        position: absolute;
        inset: -3px;
        border-radius: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .student-card:hover .student-avatar {
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    }
    
    .student-card:hover .student-avatar::after {
        opacity: 0.3;
    }
    
    .student-details {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
    }
    
    .student-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 1.05rem;
        margin-bottom: 4px;
        letter-spacing: -0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .student-email {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 400;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
        align-items: center;
    }
    
    .btn {
        padding: 10px 18px;
        border: none;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        min-width: 80px;
        justify-content: center;
    }
    
    .btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
        transition: left 0.4s ease;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn-enroll {
        background: #10b981;
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    }
    
    .btn-enroll:hover {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    
    .btn-enroll:active {
        transform: translateY(0);
    }
    
    .btn-unenroll {
        background: #ef4444;
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }
    
    .btn-unenroll:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }
    
    .btn-unenroll:active {
        transform: translateY(0);
    }
    
    .search-section {
        background: white;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 32px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e5e7eb;
    }
    
    .search-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .search-input {
        flex: 1;
        padding: 14px 20px 14px 48px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.25s ease;
        background: #f9fafb;
        position: relative;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.08);
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 10px 18px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 0.875rem;
        color: #475569;
    }
    
    .filter-btn:hover {
        border-color: #667eea;
        background: #f8f9fa;
        color: #667eea;
    }
    
    .filter-btn.active {
        border-color: #667eea;
        background: #667eea;
        color: white;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
    }
    
    .no-courses {
        text-align: center;
        padding: 80px 40px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    .no-courses-icon {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 24px;
    }
    
    .no-courses h3 {
        font-size: 1.5rem;
        color: #475569;
        margin-bottom: 12px;
    }
    
    .no-courses p {
        color: #64748b;
        font-size: 1.1rem;
    }
    
    .message {
        padding: 20px 24px;
        border-radius: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .success-message {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border: 1px solid #6ee7b7;
    }
    
    .error-message {
        background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%);
        color: #991b1b;
        border: 1px solid #f87171;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .bulk-actions {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .bulk-actions-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    
    .bulk-actions-title {
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .bulk-actions-buttons {
        display: flex;
        gap: 12px;
    }
    
    .btn-bulk {
        padding: 10px 20px;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-bulk-enroll {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-bulk-unenroll {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .more-students-banner {
        margin-top: 24px;
        padding: 20px;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 16px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .more-students-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    
    .more-students-icon {
        color: #667eea;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    
    .more-students-text {
        color: #475569;
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    @media (max-width: 768px) {
        .enrollment-dashboard {
            padding: 16px;
        }
        
        .dashboard-title {
            font-size: 2rem;
        }
        
        .courses-grid {
            grid-template-columns: 1fr;
        }
        
        .students-grid {
            grid-template-columns: 1fr;
        }
        
        .course-stats {
            flex-direction: column;
            gap: 8px;
        }
        
        .student-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
        }
        
        .student-info {
            width: 100%;
        }
        
        .action-buttons {
            width: 100%;
            justify-content: flex-end;
        }
        
        .btn {
            min-width: 100px;
            padding: 12px 20px;
        }
    }
    
    @media (max-width: 480px) {
        .students-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .student-card {
            padding: 16px;
        }
        
        .btn {
            min-width: 90px;
            padding: 10px 16px;
            font-size: 0.8rem;
        }
    }
</style>';

// Start teacher dashboard wrapper with sidebar
echo '<div class="teacher-dashboard-wrapper">';

// Mobile sidebar toggle button
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '<i class="fa fa-bars"></i>';
echo '</button>';

// Teacher Sidebar Navigation
echo '<div class="teacher-sidebar">';
echo '<div class="sidebar-content">';

// DASHBOARD Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">DASHBOARD</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/my/" class="sidebar-link"><i class="fa fa-th-large sidebar-icon"></i><span class="sidebar-text">Teacher Dashboard</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/teacher_courses.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-graduation-cap sidebar-icon"></i><span class="sidebar-text">Gradebook</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '</ul>';
echo '</div>';

// COURSES Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">COURSES</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">All Courses</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/edit.php" class="sidebar-link"><i class="fa fa-plus sidebar-icon"></i><span class="sidebar-text">Create Course</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php?categoryid=0" class="sidebar-link"><i class="fa fa-folder sidebar-icon"></i><span class="sidebar-text">Course Categories</span></a></li>';
echo '</ul>';
echo '</div>';

// STUDENTS Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">STUDENTS</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '<li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/enroll_students.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '</ul>';
echo '</div>';

// ASSESSMENTS Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php" class="sidebar-link"><i class="fa fa-sitemap sidebar-icon"></i><span class="sidebar-text">Competencies</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-star sidebar-icon"></i><span class="sidebar-text">Grading</span></a></li>';
echo '</ul>';
echo '</div>';

// QUESTIONS Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">QUESTIONS</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/pages/questions_unified.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Questions Management</span></a></li>';
echo '</ul>';
echo '</div>';

// REPORTS Section
echo '<div class="sidebar-section">';
echo '<h3 class="sidebar-category">REPORTS</h3>';
echo '<ul class="sidebar-menu">';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/log/index.php" class="sidebar-link"><i class="fa fa-chart-bar sidebar-icon"></i><span class="sidebar-text">Activity Logs</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/outline/index.php" class="sidebar-link"><i class="fa fa-file-alt sidebar-icon"></i><span class="sidebar-text">Course Reports</span></a></li>';
echo '<li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Tracking</span></a></li>';
echo '</ul>';
echo '</div>';

echo '</div>'; // sidebar-content
echo '</div>'; // teacher-sidebar

// Main Content Area
echo '<div class="teacher-main-content">';
echo '<div class="enrollment-dashboard">';

// Success/Error Messages
if (isset($success_message)) {
    echo '<div class="success-message">';
    echo '<i class="fas fa-check-circle"></i> ' . $success_message;
    echo '</div>';
}

if (isset($error_message)) {
    echo '<div class="error-message">';
    echo '<i class="fas fa-exclamation-triangle"></i> ' . $error_message;
    echo '</div>';
}

// Calculate overall statistics with error handling
$total_courses = count($teacher_courses);
$total_students = count($all_students);
$total_enrollments = 0;
$active_courses = 0;

try {
    foreach ($teacher_courses as $course) {
        try {
            $enrolled_count = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id)
                 FROM {user} u
                 JOIN {user_enrolments} ue ON u.id = ue.userid
                 JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = ? AND u.deleted = 0 AND u.suspended = 0",
                [$course->id]
            );
            $total_enrollments += $enrolled_count;
            if ($enrolled_count > 0) {
                $active_courses++;
            }
        } catch (Exception $e) {
            error_log('Error counting enrollments for course ' . $course->id . ': ' . $e->getMessage());
            // Continue with next course
            continue;
        }
    }
} catch (Exception $e) {
    error_log('Error calculating statistics: ' . $e->getMessage());
}

// Advanced Dashboard Header
echo '<div class="dashboard-header">';
echo '<div class="header-content">';
echo '<h1 class="dashboard-title">Student Enrollment Dashboard</h1>';
echo '<p class="dashboard-subtitle">Manage student enrollments across all your courses with advanced tools and insights</p>';

// Statistics Grid
echo '<div class="stats-grid">';
echo '<div class="stat-card">';
echo '<div class="stat-number">' . $total_courses . '</div>';
echo '<div class="stat-label">Total Courses</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number">' . $total_students . '</div>';
echo '<div class="stat-label">Available Students</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number">' . $total_enrollments . '</div>';
echo '<div class="stat-label">Total Enrollments</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number">' . $active_courses . '</div>';
echo '<div class="stat-label">Active Courses</div>';
echo '</div>';
echo '</div>'; // stats-grid
echo '</div>'; // header-content
echo '</div>'; // dashboard-header

// Search and Filter Section
echo '<div class="search-section">';
echo '<div class="search-header" style="position: relative;">';
echo '<i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.1rem; z-index: 10;"></i>';
echo '<input type="text" class="search-input" placeholder="Search students by name or email..." id="studentSearch">';
echo '</div>';
echo '<div class="filter-buttons">';
echo '<button class="filter-btn active" data-filter="all"><i class="fas fa-th-large" style="margin-right: 6px;"></i>All Students</button>';
echo '<button class="filter-btn" data-filter="enrolled"><i class="fas fa-user-check" style="margin-right: 6px;"></i>Enrolled</button>';
echo '<button class="filter-btn" data-filter="available"><i class="fas fa-user-plus" style="margin-right: 6px;"></i>Available</button>';
echo '<button class="filter-btn" data-filter="recent"><i class="fas fa-clock" style="margin-right: 6px;"></i>Recently Added</button>';
echo '</div>';
echo '</div>'; // search-section

if (!empty($teacher_courses)) {
    echo '<div class="courses-grid">';
    
    foreach ($teacher_courses as $course) {
        // Get enrolled students for this course
        $enrolled_students = $DB->get_records_sql(
            "SELECT DISTINCT u.*
             FROM {user} u
             JOIN {user_enrolments} ue ON u.id = ue.userid
             JOIN {enrol} e ON ue.enrolid = e.id
             WHERE e.courseid = ? AND u.deleted = 0 AND u.suspended = 0
             ORDER BY u.firstname, u.lastname ASC",
            [$course->id]
        );
        
        // Get course completion stats with error handling
        try {
            $completion_stats = $DB->get_record_sql(
                "SELECT 
                    COUNT(DISTINCT u.id) as total_students,
                    SUM(CASE WHEN cc.timecompleted IS NOT NULL THEN 1 ELSE 0 END) as completed_students
                 FROM {user} u
                 JOIN {user_enrolments} ue ON u.id = ue.userid
                 JOIN {enrol} e ON ue.enrolid = e.id
                 LEFT JOIN {course_completions} cc ON u.id = cc.userid AND cc.course = ?
                 WHERE e.courseid = ? AND u.deleted = 0 AND u.suspended = 0",
                [$course->id, $course->id]
            );
            
            if (!$completion_stats || !isset($completion_stats->total_students)) {
                $completion_stats = new stdClass();
                $completion_stats->total_students = count($enrolled_students);
                $completion_stats->completed_students = 0;
            }
            
            $completion_percentage = $completion_stats->total_students > 0 
                ? round(($completion_stats->completed_students / $completion_stats->total_students) * 100) 
                : 0;
        } catch (Exception $e) {
            // Fallback if completion tracking is not available
            $completion_stats = new stdClass();
            $completion_stats->total_students = count($enrolled_students);
            $completion_stats->completed_students = 0;
            $completion_percentage = 0;
            error_log('Enrollment page completion stats error: ' . $e->getMessage());
        }
        
        echo '<div class="course-card" data-course-id="' . $course->id . '">';
        echo '<div class="course-header">';
        echo '<h2 class="course-title">' . htmlspecialchars($course->fullname) . '</h2>';
        echo '<p class="course-code">' . htmlspecialchars($course->shortname) . '</p>';
        echo '<div class="course-stats">';
        echo '<div class="course-stat"><i class="fas fa-users"></i> ' . count($enrolled_students) . ' Enrolled</div>';
        echo '<div class="course-stat"><i class="fas fa-chart-line"></i> ' . $completion_percentage . '% Complete</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="course-content">';
        
        // Enrolled Students Section
        echo '<div class="enrolled-students">';
        echo '<div class="section-header">';
        echo '<div class="section-title">';
        echo '<div class="section-icon"><i class="fas fa-users"></i></div>';
        echo 'Enrolled Students (' . count($enrolled_students) . ')';
        echo '</div>';
        echo '</div>';
        
        if (!empty($enrolled_students)) {
            echo '<div class="students-grid">';
            foreach ($enrolled_students as $student) {
                echo '<div class="student-card enrolled-student" data-student-id="' . $student->id . '">';
                echo '<div class="student-info">';
                echo '<div class="student-avatar">' . strtoupper(substr($student->firstname, 0, 1)) . '</div>';
                echo '<div class="student-details">';
                echo '<div class="student-name">' . fullname($student) . '</div>';
                echo '<div class="student-email">' . htmlspecialchars($student->email) . '</div>';
                echo '</div>';
                echo '</div>';
                echo '<div class="action-buttons">';
                echo '<form method="post" style="display: inline;">';
                echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
                echo '<input type="hidden" name="action" value="unenroll">';
                echo '<input type="hidden" name="course_id" value="' . $course->id . '">';
                echo '<input type="hidden" name="user_id" value="' . $student->id . '">';
                echo '<button type="submit" class="btn btn-unenroll" onclick="return confirm(\'Are you sure you want to unenroll this student?\')">';
                echo '<i class="fas fa-user-minus"></i> Unenroll';
                echo '</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div style="text-align: center; padding: 40px; color: #64748b; font-style: italic;">';
            echo '<i class="fas fa-users" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i><br>';
            echo 'No students enrolled in this course yet.';
            echo '</div>';
        }
        echo '</div>';
        
        // Available Students Section
        echo '<div class="available-students">';
        echo '<div class="section-header">';
        echo '<div class="section-title">';
        echo '<div class="section-icon"><i class="fas fa-user-plus"></i></div>';
        echo 'Available Students';
        echo '</div>';
        echo '</div>';
        
        // Get students not enrolled in this course
        $enrolled_user_ids = array_keys($enrolled_students);
        $available_students = array_filter($all_students, function($student) use ($enrolled_user_ids) {
            return !in_array($student->id, $enrolled_user_ids);
        });
        
        if (!empty($available_students)) {
            echo '<div class="students-grid">';
            foreach (array_slice($available_students, 0, 12) as $student) { // Show first 12 available students
                echo '<div class="student-card available-student" data-student-id="' . $student->id . '">';
                echo '<div class="student-info">';
                echo '<div class="student-avatar">' . strtoupper(substr($student->firstname, 0, 1)) . '</div>';
                echo '<div class="student-details">';
                echo '<div class="student-name">' . fullname($student) . '</div>';
                echo '<div class="student-email">' . htmlspecialchars($student->email) . '</div>';
                echo '</div>';
                echo '</div>';
                echo '<div class="action-buttons">';
                echo '<form method="post" style="display: inline;">';
                echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
                echo '<input type="hidden" name="action" value="enroll">';
                echo '<input type="hidden" name="course_id" value="' . $course->id . '">';
                echo '<input type="hidden" name="user_id" value="' . $student->id . '">';
                echo '<button type="submit" class="btn btn-enroll">';
                echo '<i class="fas fa-user-plus"></i> Enroll';
                echo '</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            
            if (count($available_students) > 12) {
                echo '<div class="more-students-banner">';
                echo '<div class="more-students-content">';
                echo '<i class="fas fa-info-circle more-students-icon"></i>';
                echo '<span class="more-students-text">... and ' . (count($available_students) - 12) . ' more students available for enrollment.</span>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div style="text-align: center; padding: 40px; color: #64748b; font-style: italic;">';
            echo '<i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 12px; color: #10b981;"></i><br>';
            echo 'All students are already enrolled in this course.';
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>'; // course-content
        echo '</div>'; // course-card
    }
    
    echo '</div>'; // courses-grid
} else {
    echo '<div class="no-courses">';
    echo '<div class="no-courses-icon"><i class="fas fa-graduation-cap"></i></div>';
    echo '<h3>No Courses Found</h3>';
    echo '<p>You are not assigned as a teacher to any courses. Contact your administrator to get course access.</p>';
    echo '</div>';
}

echo '</div>'; // enrollment-dashboard
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper

// Advanced JavaScript for Enrollment Dashboard
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("Enrollment Dashboard initialized");
    
    // Sidebar functionality
    function toggleTeacherSidebar() {
        const sidebar = document.querySelector(".teacher-sidebar");
        sidebar.classList.toggle("sidebar-open");
    }
    
    // Make function globally available
    window.toggleTeacherSidebar = toggleTeacherSidebar;
    
    // Search functionality
    const searchInput = document.getElementById("studentSearch");
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const searchTerm = this.value.toLowerCase();
            const studentCards = document.querySelectorAll(".student-card");
            
            studentCards.forEach(card => {
                const studentName = card.querySelector(".student-name").textContent.toLowerCase();
                const studentEmail = card.querySelector(".student-email").textContent.toLowerCase();
                
                if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
                    card.style.display = "flex";
                    card.style.animation = "fadeIn 0.3s ease";
                } else {
                    card.style.display = "none";
                }
            });
            
            // Update course visibility based on visible students
            updateCourseVisibility();
        });
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll(".filter-btn");
    filterButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            // Update active button
            filterButtons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            
            const filter = this.dataset.filter;
            const studentCards = document.querySelectorAll(".student-card");
            
            studentCards.forEach(card => {
                switch(filter) {
                    case "all":
                        card.style.display = "flex";
                        break;
                    case "enrolled":
                        if (card.classList.contains("enrolled-student")) {
                            card.style.display = "flex";
                        } else {
                            card.style.display = "none";
                        }
                        break;
                    case "available":
                        if (card.classList.contains("available-student")) {
                            card.style.display = "flex";
                        } else {
                            card.style.display = "none";
                        }
                        break;
                    case "recent":
                        // Show all for now, could implement date-based filtering
                        card.style.display = "flex";
                        break;
                }
            });
            
            updateCourseVisibility();
        });
    });
    
    // Update course visibility based on visible students
    function updateCourseVisibility() {
        const courseCards = document.querySelectorAll(".course-card");
        
        courseCards.forEach(courseCard => {
            const visibleStudents = courseCard.querySelectorAll(".student-card[style*=\'flex\'], .student-card:not([style*=\'none\'])");
            const enrolledSection = courseCard.querySelector(".enrolled-students");
            const availableSection = courseCard.querySelector(".available-students");
            
            if (visibleStudents.length === 0) {
                // Hide sections with no visible students
                if (enrolledSection) {
                    const enrolledStudents = enrolledSection.querySelectorAll(".student-card");
                    const hasVisibleEnrolled = Array.from(enrolledStudents).some(card => 
                        card.style.display !== "none" && card.style.display !== ""
                    );
                    enrolledSection.style.display = hasVisibleEnrolled ? "block" : "none";
                }
                
                if (availableSection) {
                    const availableStudents = availableSection.querySelectorAll(".student-card");
                    const hasVisibleAvailable = Array.from(availableStudents).some(card => 
                        card.style.display !== "none" && card.style.display !== ""
                    );
                    availableSection.style.display = hasVisibleAvailable ? "block" : "none";
                }
            } else {
                // Show all sections
                if (enrolledSection) enrolledSection.style.display = "block";
                if (availableSection) availableSection.style.display = "block";
            }
        });
    }
    
    // Enhanced button interactions
    const actionButtons = document.querySelectorAll(".btn");
    actionButtons.forEach(btn => {
        btn.addEventListener("click", function(e) {
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = \'<div class="loading-spinner"></div> Processing...\';
            this.disabled = true;
            
            // Re-enable after a delay (form submission will handle the redirect)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
    
    // Course card hover effects
    const courseCards = document.querySelectorAll(".course-card");
    courseCards.forEach(card => {
        card.addEventListener("mouseenter", function() {
            this.style.transform = "translateY(-8px)";
        });
        
        card.addEventListener("mouseleave", function() {
            this.style.transform = "translateY(0)";
        });
    });
    
    // Student card interactions
    const studentCards = document.querySelectorAll(".student-card");
    studentCards.forEach(card => {
        card.addEventListener("mouseenter", function() {
            this.style.transform = "translateY(-2px)";
            this.style.boxShadow = "0 8px 25px rgba(102, 126, 234, 0.15)";
        });
        
        card.addEventListener("mouseleave", function() {
            this.style.transform = "translateY(0)";
            this.style.boxShadow = "none";
        });
    });
    
    // Statistics animation
    const statNumbers = document.querySelectorAll(".stat-number");
    statNumbers.forEach(stat => {
        const finalNumber = parseInt(stat.textContent);
        let currentNumber = 0;
        const increment = finalNumber / 50;
        
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                stat.textContent = finalNumber;
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(currentNumber);
            }
        }, 30);
    });
    
    // Auto-refresh functionality (optional)
    let refreshInterval;
    function startAutoRefresh() {
        refreshInterval = setInterval(() => {
            // Check for new enrollments (could implement AJAX here)
            console.log("Auto-refresh check");
        }, 30000); // 30 seconds
    }
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Cleanup on page unload
    window.addEventListener("beforeunload", function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
    
    // Responsive behavior
    function handleResize() {
        const sidebar = document.querySelector(".teacher-sidebar");
        if (!sidebar) return;
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove("sidebar-open");
        }
    }
    
    window.addEventListener("resize", handleResize);
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function(event) {
        const sidebar = document.querySelector(".teacher-sidebar");
        const toggleButton = document.querySelector(".sidebar-toggle");
        
        if (window.innerWidth <= 768 && 
            sidebar && toggleButton &&
            !sidebar.contains(event.target) && 
            !toggleButton.contains(event.target) && 
            sidebar.classList.contains("sidebar-open")) {
            sidebar.classList.remove("sidebar-open");
        }
    });
    
    // Add CSS animations
    const style = document.createElement("style");
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .student-card {
            animation: fadeIn 0.3s ease;
        }
        
        .course-card {
            animation: fadeIn 0.5s ease;
        }
        
        .stat-card {
            animation: fadeIn 0.6s ease;
        }
    `;
    document.head.appendChild(style);
    
    console.log("All enrollment dashboard features initialized successfully");
});

// Global functions for external access
window.enrollmentDashboard = {
    search: function(term) {
        const searchInput = document.getElementById("studentSearch");
        if (searchInput) {
            searchInput.value = term;
            searchInput.dispatchEvent(new Event("input"));
        }
    },
    
    filter: function(filterType) {
        const filterBtn = document.querySelector(`[data-filter="${filterType}"]`);
        if (filterBtn) {
            filterBtn.click();
        }
    },
    
    refresh: function() {
        location.reload();
    }
};
</script>';

echo $OUTPUT->footer();
?>