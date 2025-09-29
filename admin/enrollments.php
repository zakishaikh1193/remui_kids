<?php
/**
 * Enrolments Management Page - Display and manage all course enrolments
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/enrollments.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Enrolments Management');
$PAGE->set_heading('Enrolments Management');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Handle enrolment status toggle AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'toggle_enrollment_status') {
    header('Content-Type: application/json');
    
    $enrollment_id = intval($_POST['enrollment_id']);
    if ($enrollment_id) {
        $enrollment = $DB->get_record('user_enrolments', ['id' => $enrollment_id]);
        if ($enrollment) {
            $enrollment->status = $enrollment->status ? 0 : 1;
            if ($DB->update_record('user_enrolments', $enrollment)) {
                $status = $enrollment->status ? 'suspended' : 'activated';
                echo json_encode(['status' => 'success', 'message' => "Enrolment $status successfully"]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update enrollment status']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Enrolment not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid enrollment ID']);
    }
    exit;
}

// Handle GET AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_students':
            // Get all students (users with trainee role)
            $students = $DB->get_records_sql("
                SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
                FROM {user} u 
                JOIN {role_assignments} ra ON u.id = ra.userid 
                JOIN {role} r ON ra.roleid = r.id 
                WHERE r.shortname = 'trainee' 
                AND u.deleted = 0 
                AND u.suspended = 0
                ORDER BY u.firstname, u.lastname
            ");
            
            echo json_encode([
                'status' => 'success',
                'students' => array_values($students)
            ]);
            exit;
            
        case 'get_courses':
            // Get all visible courses (excluding site course)
            $courses = $DB->get_records_select('course', 'id > 1 AND visible = 1', null, 'fullname ASC');
            
            echo json_encode([
                'status' => 'success',
                'courses' => array_values($courses)
            ]);
            exit;
    }
}

// Handle POST AJAX requests for enrollment
if (isset($_POST['action']) && $_POST['action'] === 'enroll_student') {
    header('Content-Type: application/json');
    
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $enrollment_method = s($_POST['enrollment_method']);
    
    if ($student_id && $course_id) {
        // Get the enrollment instance for this course
        $enrol_instance = $DB->get_record('enrol', [
            'courseid' => $course_id,
            'enrol' => 'manual',
            'status' => 0
        ]);
        
        if (!$enrol_instance) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No manual enrollment method found for this course'
            ]);
        } else {
            // Check if enrollment already exists
            $existing = $DB->get_record('user_enrolments', [
                'userid' => $student_id,
                'enrolid' => $enrol_instance->id
            ]);
            
            if ($existing) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Student is already enrolled in this course'
                ]);
            } else {
                // Create new enrollment
                $enrollment = new stdClass();
                $enrollment->userid = $student_id;
                $enrollment->enrolid = $enrol_instance->id;
                $enrollment->status = 0; // Active
                $enrollment->timestart = time();
                $enrollment->timeend = 0;
                $enrollment->modifierid = $USER->id;
                $enrollment->timecreated = time();
                $enrollment->timemodified = time();
                
                if ($DB->insert_record('user_enrolments', $enrollment)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Student enrolled successfully'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to enroll student'
                    ]);
                }
            }
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid student or course ID'
        ]);
    }
    exit;
}

echo $OUTPUT->header();

// Add custom CSS for the enrollments page
echo "<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .enrollments-container {
        max-width: 1400px;
        margin: 0 auto;
        animation: slideInUp 0.8s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
        margin-bottom: 30px;
        position: relative;
    }
    
    .header-background {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 200px;
        position: relative;
        overflow: hidden;
    }
    
    .header-background::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .page-content {
        padding: 40px;
        position: relative;
    }
    
    .breadcrumb {
        background: rgba(255, 255, 255, 0.1);
        padding: 15px 30px;
        border-radius: 12px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
    }
    
    .breadcrumb a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb a:hover {
        color: white;
    }
    
    .breadcrumb-item {
        color: rgba(255, 255, 255, 0.9);
    }
    
    .page-title-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .title-content {
        flex: 1;
        min-width: 300px;
    }
    
    .title-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: fadeInUp 1s ease-out 0.3s both;
    }
    
    .btn-enroll {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: fadeInUp 1s ease-out 0.7s both;
        text-decoration: none;
    }
    
    .btn-enroll:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
        background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
    }
    
    .btn-enroll:active {
        transform: translateY(-1px);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 0.95rem;
    }
    
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group select:hover {
        border-color: #d1d5db;
    }
    
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
    
    .page-subtitle {
        font-size: 1.2rem;
        color: #4a5568;
        margin-bottom: 30px;
        font-weight: 500;
        animation: fadeInUp 1s ease-out 0.4s both;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
        animation: fadeInUp 1s ease-out 0.5s both;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 20px;
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 1rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filters-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        margin-bottom: 30px;
        animation: fadeInUp 1s ease-out 0.6s both;
    }
    
    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        font-size: 0.9rem;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-input {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .filter-select {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #f7fafc;
        color: #4a5568;
        border: 2px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
        background: #edf2f7;
        transform: translateY(-2px);
    }
    
    .enrollments-table-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        animation: fadeInUp 1s ease-out 0.7s both;
        overflow: hidden;
    }
    
    .enrollments-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .enrollments-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .enrollments-table td {
        padding: 20px 16px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .enrollments-table tr:hover {
        background: #f8f9fa;
    }
    
    .enrollment-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .enrollment-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }
    
    .enrollment-details h4 {
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 5px 0;
        font-size: 1rem;
    }
    
    .enrollment-details p {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-suspended {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-completed {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 0.8rem;
        border-radius: 8px;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(229, 62, 62, 0.3);
    }
    
    .btn-info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(66, 153, 225, 0.3);
    }
    
    .no-enrollments {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .no-enrollments i {
        font-size: 48px;
        margin-bottom: 20px;
        color: #dee2e6;
    }
    
    .floating-elements {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }
    
    .floating-circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
    }
    
    .floating-circle:nth-child(1) {
        width: 100px;
        height: 100px;
        top: 10%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .floating-circle:nth-child(2) {
        width: 80px;
        height: 80px;
        top: 60%;
        right: 10%;
        animation-delay: 2s;
    }
    
    .floating-circle:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
    }
    
    .floating-circle:nth-child(4) {
        width: 120px;
        height: 120px;
        top: 30%;
        right: 30%;
        animation-delay: 1s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .confirmation-modal {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        overflow-y: auto;
        padding: 20px;
        box-sizing: border-box;
        opacity: 1;
        visibility: visible;
    }
    .modal-content {
        background: #ffffff;
        margin: 50px auto;
        padding: 0;
        border: 3px solid #dc3545;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        min-height: 200px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        background-size: 200% 100%;
        animation: shimmer 2s ease-in-out infinite;
    }
    @keyframes modalFadeIn {
        0% { 
            opacity: 0;
            backdrop-filter: blur(0px);
        }
        100% { 
            opacity: 1;
            backdrop-filter: blur(10px);
        }
    }
    @keyframes modalSlideIn {
        0% {
            opacity: 0;
            transform: translateY(-100px) scale(0.8) rotateX(20deg);
        }
        50% {
            opacity: 0.8;
            transform: translateY(10px) scale(1.02) rotateX(-5deg);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1) rotateX(0deg);
        }
    }
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .modal-body {
        padding: 30px;
        text-align: center;
        position: relative;
    }
    .modal-message {
        font-size: 1.1rem;
        color: #333;
        margin-bottom: 0;
        line-height: 1.6;
        font-weight: 500;
        position: relative;
    }
    .modal-message::before {
        content: '⚠️';
        display: block;
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
    .modal-footer {
        padding: 0 30px 30px;
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    .modal-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }
    .modal-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s;
    }
    .modal-btn:hover::before {
        left: 100%;
    }
    .modal-btn-secondary {
        background: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }
    .modal-btn-secondary:hover {
        background: #e9ecef;
        color: #495057;
        transform: translateY(-2px);
    }
    .modal-btn-danger {
        background: #dc3545;
        color: white;
        border: 1px solid #dc3545;
    }
    .modal-btn-danger:hover {
        background: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
    }
    .modal-btn-success {
        background: #28a745;
        color: white;
        border: 1px solid #28a745;
    }
    .modal-btn-success:hover {
        background: #218838;
        border-color: #1e7e34;
        transform: translateY(-2px);
    }
    @keyframes bodySlideIn {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes messageFadeIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    @keyframes footerSlideUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .filters-row {
            grid-template-columns: 1fr;
        }
        
        .enrollments-table {
            font-size: 0.9rem;
        }
        
        .enrollments-table th,
        .enrollments-table td {
            padding: 12px 8px;
        }
    }
</style>";

// Floating background elements
echo "<div class='floating-elements'>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "</div>";

echo "<div class='enrollments-container'>";

// Page Header
echo "<div class='page-header'>";
echo "<div class='header-background'>";
echo "<div class='breadcrumb'>";
echo "<a href='{$CFG->wwwroot}/my/'>Dashboard</a> / ";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/'>Administration</a> / ";
echo "<span class='breadcrumb-item'>Enrolments Management</span>";
echo "</div>";
echo "</div>";

echo "<div class='page-content'>";
echo "<div class='page-title-section'>";
echo "<div class='title-content'>";
echo "<h1 class='page-title'>Enrollments Management</h1>";
echo "<p class='page-subtitle'>Manage and view all course enrollments in your system</p>";
echo "</div>";
echo "<div class='title-actions'>";
echo "<a href='enroll_student.php' class='btn btn-primary btn-enroll'>";
echo "<i class='fa fa-plus'></i> Enroll Student";
echo "</a>";
echo "</div>";
echo "</div>";

try {
    // Get enrollment statistics
    $total_enrollments = $DB->count_records('user_enrolments');
    $active_enrollments = $DB->count_records('user_enrolments', ['status' => 0]);
    $suspended_enrollments = $DB->count_records('user_enrolments', ['status' => 1]);
    $total_courses = $DB->count_records_select('course', 'id > 1 AND visible = 1');
    
    // Statistics Grid
    echo "<div class='stats-grid'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-icon'><i class='fa fa-users'></i></div>";
    echo "<div class='stat-number'>$total_enrollments</div>";
    echo "<div class='stat-label'>Total Enrollments</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-icon'><i class='fa fa-check-circle'></i></div>";
    echo "<div class='stat-number'>$active_enrollments</div>";
    echo "<div class='stat-label'>Active Enrollments</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-icon'><i class='fa fa-pause-circle'></i></div>";
    echo "<div class='stat-number'>$suspended_enrollments</div>";
    echo "<div class='stat-label'>Suspended Enrollments</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-icon'><i class='fa fa-book'></i></div>";
    echo "<div class='stat-number'>$total_courses</div>";
    echo "<div class='stat-label'>Available Courses</div>";
    echo "</div>";
    echo "</div>";
    
    // Filters Section
    echo "<div class='filters-section'>";
    echo "<h3 style='margin-bottom: 20px; color: #2d3748; font-weight: 600;'>Filter Enrollments</h3>";
    echo "<div class='filters-row'>";
    
    echo "<div class='filter-group'>";
    echo "<label class='filter-label'>Search Student</label>";
    echo "<input type='text' class='filter-input' placeholder='Search by name or email...' id='student-search'>";
    echo "</div>";
    
    echo "<div class='filter-group'>";
    echo "<label class='filter-label'>Course</label>";
    echo "<select class='filter-select' id='course-filter'>";
    echo "<option value='all'>All Courses</option>";
    $courses = $DB->get_records_select('course', 'id > 1 AND visible = 1', null, 'fullname ASC');
    foreach ($courses as $course) {
        echo "<option value='{$course->id}'>" . s($course->fullname) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='filter-group'>";
    echo "<label class='filter-label'>Status</label>";
    echo "<select class='filter-select' id='status-filter'>";
    echo "<option value='all'>All Status</option>";
    echo "<option value='active'>Active</option>";
    echo "<option value='suspended'>Suspended</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<div class='filter-group'>";
    echo "<button class='btn btn-primary' onclick='applyFilters()'>";
    echo "<i class='fa fa-search'></i> Apply Filters";
    echo "</button>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Get enrollments with user and course details
    $enrollments = $DB->get_records_sql(
        "SELECT 
            ue.id as enrollment_id,
            ue.userid,
            ue.enrolid,
            ue.status,
            ue.timestart,
            ue.timeend,
            ue.timecreated,
            ue.timemodified,
            u.firstname,
            u.lastname,
            u.email,
            u.username,
            c.id as courseid,
            c.fullname as course_name,
            c.shortname as course_shortname,
            e.enrol as enrollment_method
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         JOIN {enrol} e ON ue.enrolid = e.id
         JOIN {course} c ON e.courseid = c.id
         WHERE u.deleted = 0 AND c.id > 1 AND c.visible = 1
         ORDER BY ue.timecreated DESC
         LIMIT 100"
    );
    
    // Enrollments Table
    echo "<div class='enrollments-table-container'>";
    echo "<h3 style='margin-bottom: 20px; color: #2d3748; font-weight: 600;'>Recent Enrollments</h3>";
    
    if (count($enrollments) > 0) {
        echo "<table class='enrollments-table' id='enrollments-table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Student</th>";
        echo "<th>Course</th>";
        echo "<th>Enrollment Method</th>";
        echo "<th>Status</th>";
        echo "<th>Enrolled Date</th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($enrollments as $enrollment) {
            $status_class = $enrollment->status ? 'status-suspended' : 'status-active';
            $status_text = $enrollment->status ? 'Suspended' : 'Active';
            
            $enrolled_date = date('M j, Y g:i A', $enrollment->timecreated);
            
            // Get first letter of first name for avatar
            $avatar_letter = strtoupper(substr($enrollment->firstname, 0, 1));
            
            echo "<tr>";
            echo "<td>";
            echo "<div class='enrollment-info'>";
            echo "<div class='enrollment-avatar'>$avatar_letter</div>";
            echo "<div class='enrollment-details'>";
            echo "<h4>{$enrollment->firstname} {$enrollment->lastname}</h4>";
            echo "<p>{$enrollment->email}</p>";
            echo "</div>";
            echo "</div>";
            echo "</td>";
            echo "<td>";
            echo "<div class='enrollment-details'>";
            echo "<h4>" . s($enrollment->course_name) . "</h4>";
            echo "<p>" . s($enrollment->course_shortname) . "</p>";
            echo "</div>";
            echo "</td>";
            echo "<td>" . ucfirst($enrollment->enrollment_method) . "</td>";
            echo "<td><span class='status-badge $status_class'>$status_text</span></td>";
            echo "<td>$enrolled_date</td>";
            echo "<td>";
            echo "<div class='action-buttons'>";
            if (!$enrollment->status) {
                echo "<button class='btn btn-sm btn-danger' title='Suspend Enrollment' onclick='toggleEnrollmentStatus({$enrollment->enrollment_id}, true)'>";
                echo "<i class='fa fa-pause'></i>";
                echo "</button>";
            } else {
                echo "<button class='btn btn-sm btn-success' title='Activate Enrollment' onclick='toggleEnrollmentStatus({$enrollment->enrollment_id}, false)'>";
                echo "<i class='fa fa-play'></i>";
                echo "</button>";
            }
            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
    } else {
        // No enrollments found
        echo "<div class='no-enrollments'>";
        echo "<i class='fa fa-graduation-cap'></i>";
        echo "<h3>No Enrollments Found</h3>";
        echo "<p>There are no enrollments to display at the moment.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Confirmation Modal
    echo "<div id='confirmationModal' class='confirmation-modal'>";
    echo "<div class='modal-content'>";
    echo "<div class='modal-body'>";
    echo "<p class='modal-message' id='modalMessage'>Are you sure you want to perform this action?</p>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<button class='modal-btn modal-btn-secondary' onclick='closeConfirmationModal()'>";
    echo "<i class='fa fa-times'></i> Cancel";
    echo "</button>";
    echo "<button class='modal-btn modal-btn-danger' id='confirmBtn' onclick='confirmAction()'>";
    echo "<i class='fa fa-check'></i> Confirm";
    echo "</button>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>"; // End page-content
echo "</div>"; // End page-header
echo "</div>"; // End enrollments-container

// Confirmation Modal
echo "<div id='confirmationModal' class='confirmation-modal'>";
echo "<div class='modal-content'>";
echo "<div class='modal-body'>";
echo "<p class='modal-message' id='modalMessage'>Are you sure you want to perform this action?</p>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button class='modal-btn modal-btn-secondary' onclick='closeConfirmationModal()'>";
echo "<i class='fa fa-times'></i> Cancel";
echo "</button>";
echo "<button class='modal-btn modal-btn-danger' id='confirmBtn' onclick='confirmAction()'>";
echo "<i class='fa fa-check'></i> Confirm";
echo "</button>";
echo "</div>";
echo "</div>";
echo "</div>";

// Enrollment Modal
echo "<div id='enrollModal' class='confirmation-modal'>";
echo "<div class='modal-content'>";
echo "<div class='modal-body'>";
echo "<h3 style='margin-bottom: 20px; color: #2d3748;'>Enroll New Student</h3>";
echo "<form id='enrollForm'>";
echo "<div class='form-group'>";
echo "<label for='studentSelect'>Select Student:</label>";
echo "<select id='studentSelect' name='student_id' required>";
echo "<option value=''>Choose a student...</option>";
echo "</select>";
echo "</div>";
echo "<div class='form-group'>";
echo "<label for='courseSelect'>Select Course:</label>";
echo "<select id='courseSelect' name='course_id' required>";
echo "<option value=''>Choose a course...</option>";
echo "</select>";
echo "</div>";
echo "<div class='form-group'>";
echo "<label for='enrollmentMethod'>Enrollment Method:</label>";
echo "<select id='enrollmentMethod' name='enrollment_method' required>";
echo "<option value='manual'>Manual</option>";
echo "<option value='self'>Self Enrollment</option>";
echo "<option value='cohort'>Cohort</option>";
echo "</select>";
echo "</div>";
echo "</form>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button class='modal-btn modal-btn-secondary' onclick='closeEnrollModal()'>";
echo "<i class='fa fa-times'></i> Cancel";
echo "</button>";
echo "<button class='modal-btn modal-btn-success' onclick='processEnrollment()'>";
echo "<i class='fa fa-plus'></i> Enroll Student";
echo "</button>";
echo "</div>";
echo "</div>";
echo "</div>";

// Add JavaScript for filtering functionality
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSearch = document.getElementById('student-search');
    const courseFilter = document.getElementById('course-filter');
    const statusFilter = document.getElementById('status-filter');
    const table = document.getElementById('enrollments-table');
    
    if (table) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        function filterTable() {
            const searchTerm = studentSearch.value.toLowerCase();
            const courseValue = courseFilter.value;
            const statusValue = statusFilter.value;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const studentName = row.cells[0].textContent.toLowerCase();
                const courseName = row.cells[1].textContent.toLowerCase();
                const status = row.cells[3].textContent.toLowerCase();
                
                const matchesSearch = studentName.includes(searchTerm);
                const matchesCourse = courseValue === 'all' || row.cells[1].textContent.includes(courseFilter.options[courseFilter.selectedIndex].text);
                const matchesStatus = statusValue === 'all' || 
                                    (statusValue === 'active' && status.includes('active')) ||
                                    (statusValue === 'suspended' && status.includes('suspended'));
                
                row.style.display = (matchesSearch && matchesCourse && matchesStatus) ? '' : 'none';
            }
        }
        
        studentSearch.addEventListener('input', filterTable);
        courseFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    }
});

function applyFilters() {
    // Trigger filter function
    const event = new Event('input');
    document.getElementById('student-search').dispatchEvent(event);
}

// Global variables for modal
let currentEnrollmentId = null;
let currentAction = null;

function toggleEnrollmentStatus(enrollmentId, suspend) {
    currentEnrollmentId = enrollmentId;
    currentAction = suspend ? 'suspend' : 'activate';
    
    // Show modal immediately
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        // Update modal content based on action
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        
        if (suspend) {
            modalMessage.innerHTML = 'Are you sure you want to suspend this enrollment?<br>The student will lose access to the course until reactivated.';
            confirmBtn.className = 'modal-btn modal-btn-danger';
            confirmBtn.innerHTML = '<i class=\"fa fa-pause\"></i> Suspend';
        } else {
            modalMessage.innerHTML = 'Are you sure you want to activate this enrollment?<br>The student will regain access to the course.';
            confirmBtn.className = 'modal-btn modal-btn-success';
            confirmBtn.innerHTML = '<i class=\"fa fa-play\"></i> Activate';
        }
        
        // Show modal immediately
        modal.style.display = 'block';
        
        // Add click outside to close
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeConfirmationModal();
            }
        };
    } else {
        // Fallback to simple confirm dialog
        const message = suspend ? 
            'Are you sure you want to suspend this enrollment? The student will lose access to the course until reactivated.' :
            'Are you sure you want to activate this enrollment? The student will regain access to the course.';
        
        if (confirm(message)) {
            confirmAction();
        }
    }
}

function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentEnrollmentId = null;
    currentAction = null;
}

function showEnrollModal() {
    const modal = document.getElementById('enrollModal');
    if (modal) {
        // Load students and courses
        loadStudents();
        loadCourses();
        modal.style.display = 'block';
        
        // Add click outside to close
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeEnrollModal();
            }
        };
    }
}

function closeEnrollModal() {
    const modal = document.getElementById('enrollModal');
    if (modal) {
        modal.style.display = 'none';
        // Reset form
        document.getElementById('enrollForm').reset();
    }
}

function loadStudents() {
    const studentSelect = document.getElementById('studentSelect');
    if (!studentSelect) return;
    
    // Clear existing options except the first one
    studentSelect.innerHTML = '<option value=\"\">Choose a student...</option>';
    
    // Fetch students via AJAX
    fetch('?action=get_students')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                data.students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.firstname + ' ' + student.lastname + ' (' + student.email + ')';
                    studentSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
        });
}

function loadCourses() {
    const courseSelect = document.getElementById('courseSelect');
    if (!courseSelect) return;
    
    // Clear existing options except the first one
    courseSelect.innerHTML = '<option value=\"\">Choose a course...</option>';
    
    // Fetch courses via AJAX
    fetch('?action=get_courses')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                data.courses.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.fullname;
                    courseSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

function processEnrollment() {
    const form = document.getElementById('enrollForm');
    const formData = new FormData(form);
    
    // Validate form
    const studentId = formData.get('student_id');
    const courseId = formData.get('course_id');
    const enrollmentMethod = formData.get('enrollment_method');
    
    if (!studentId || !courseId || !enrollmentMethod) {
        alert('Please fill in all fields');
        return;
    }
    
    // Add action
    formData.append('action', 'enroll_student');
    
    // Show loading state
    const enrollBtn = document.querySelector('#enrollModal .modal-btn-success');
    const originalText = enrollBtn.innerHTML;
    enrollBtn.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Enrolling...';
    enrollBtn.disabled = true;
    
    // Submit enrollment
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            enrollBtn.innerHTML = '<i class=\"fa fa-check\"></i> Enrolled!';
            enrollBtn.className = 'modal-btn modal-btn-success';
            setTimeout(() => {
                closeEnrollModal();
                location.reload();
            }, 1500);
        } else {
            enrollBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
            enrollBtn.className = 'modal-btn modal-btn-danger';
            alert('Error: ' + data.message);
            setTimeout(() => {
                enrollBtn.innerHTML = originalText;
                enrollBtn.disabled = false;
                enrollBtn.className = 'modal-btn modal-btn-success';
            }, 2000);
        }
    })
    .catch(error => {
        enrollBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
        enrollBtn.className = 'modal-btn modal-btn-danger';
        alert('Error enrolling student');
        setTimeout(() => {
            enrollBtn.innerHTML = originalText;
            enrollBtn.disabled = false;
            enrollBtn.className = 'modal-btn modal-btn-success';
        }, 2000);
    });
}

function confirmAction() {
    if (!currentEnrollmentId) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_enrollment_status');
    formData.append('enrollment_id', currentEnrollmentId);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Processing...';
    confirmBtn.disabled = true;
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message briefly
            confirmBtn.innerHTML = '<i class=\"fa fa-check\"></i> Success!';
            confirmBtn.className = 'modal-btn modal-btn-success';
            
            setTimeout(() => {
                closeConfirmationModal();
                location.reload();
            }, 1500);
        } else {
            // Show error
            confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
            confirmBtn.className = 'modal-btn modal-btn-danger';
            setTimeout(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }, 2000);
        }
    })
    .catch(error => {
        confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
        confirmBtn.className = 'modal-btn modal-btn-danger';
        setTimeout(() => {
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }, 2000);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target === modal) {
        closeConfirmationModal();
    }
}
</script>";

echo $OUTPUT->footer();
?>
