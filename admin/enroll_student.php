<?php
/**
 * Enroll Student Page - Dedicated page for enrolling new students
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE, $USER;

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/enroll_student.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Enroll New Student');
$PAGE->set_heading('Enroll New Student');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $enrollment_method = s($_POST['enrollment_method']);
    $enrollment_duration = intval($_POST['enrollment_duration']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if ($student_id && $course_id) {
        // Get the enrollment instance for this course
        $enrol_instance = $DB->get_record('enrol', [
            'courseid' => $course_id,
            'enrol' => 'manual',
            'status' => 0
        ]);
        
        if (!$enrol_instance) {
            $success_message = "No manual enrollment method found for this course.";
            $message_type = "error";
        } else {
            // Check if enrollment already exists
            $existing = $DB->get_record('user_enrolments', [
                'userid' => $student_id,
                'enrolid' => $enrol_instance->id
            ]);
            
            if ($existing) {
                $success_message = "Student is already enrolled in this course.";
                $message_type = "warning";
            } else {
                // Create new enrollment
                $enrollment = new stdClass();
                $enrollment->userid = $student_id;
                $enrollment->enrolid = $enrol_instance->id;
                $enrollment->status = 0; // Active
                $enrollment->timestart = strtotime($start_date);
                $enrollment->timeend = !empty($end_date) ? strtotime($end_date) : 0;
                $enrollment->modifierid = $USER->id;
                $enrollment->timecreated = time();
                $enrollment->timemodified = time();
                
                if ($DB->insert_record('user_enrolments', $enrollment)) {
                    $success_message = "Student enrolled successfully!";
                    $message_type = "success";
                } else {
                    $success_message = "Failed to enroll student. Please try again.";
                    $message_type = "error";
                }
            }
        }
    } else {
        $success_message = "Please select both student and course.";
        $message_type = "error";
    }
}

echo $OUTPUT->header();

// Add custom CSS for the enrollment page
echo "<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .enrollment-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        animation: fadeInUp 0.8s ease-out;
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
    
    .page-header {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
        margin-bottom: 30px;
        animation: slideInDown 1s ease-out 0.2s both;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .header-background {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        text-align: center;
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
    
    .breadcrumb {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }
    
    .breadcrumb a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb a:hover {
        color: white;
    }
    
    .page-title {
        font-size: 3rem;
        font-weight: 800;
        color: white;
        margin: 0;
        text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 1;
        animation: titleGlow 2s ease-in-out infinite alternate;
    }
    
    @keyframes titleGlow {
        from { text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); }
        to { text-shadow: 0 4px 20px rgba(255, 255, 255, 0.5); }
    }
    
    .page-subtitle {
        font-size: 1.3rem;
        color: rgba(255, 255, 255, 0.9);
        margin: 10px 0 0 0;
        position: relative;
        z-index: 1;
    }
    
    .enrollment-form-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
        animation: slideInUp 1s ease-out 0.4s both;
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
    
    .form-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .form-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #28a745 0%, #20c997 50%, #28a745 100%);
        background-size: 200% 100%;
        animation: shimmer 2s ease-in-out infinite;
    }
    
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    
    .form-title {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .form-subtitle {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.9);
        margin: 10px 0 0 0;
    }
    
    .form-content {
        padding: 40px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
        animation: fadeInUp 0.8s ease-out;
    }
    
    .form-row.single {
        grid-template-columns: 1fr;
    }
    
    .form-group {
        position: relative;
        animation: slideInLeft 0.8s ease-out;
    }
    
    .form-group:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    .form-group:nth-child(3) {
        animation-delay: 0.2s;
    }
    
    .form-group:nth-child(4) {
        animation-delay: 0.3s;
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .form-label {
        display: block;
        margin-bottom: 12px;
        font-weight: 600;
        color: #374151;
        font-size: 1rem;
        position: relative;
    }
    
    .form-label::after {
        content: '*';
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-label.optional::after {
        display: none;
    }
    
    .form-input, .form-select {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;
        box-sizing: border-box;
        position: relative;
    }
    
    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }
    
    .form-input:hover, .form-select:hover {
        border-color: #d1d5db;
        transform: translateY(-1px);
    }
    
    .form-input::placeholder {
        color: #9ca3af;
        transition: color 0.3s ease;
    }
    
    .form-input:focus::placeholder {
        color: #d1d5db;
    }
    
    .form-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 40px;
        animation: fadeInUp 0.8s ease-out 0.6s both;
    }
    
    .btn {
        padding: 15px 40px;
        border: none;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
    }
    
    .btn-secondary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(107, 114, 128, 0.4);
    }
    
    .btn:active {
        transform: translateY(-1px);
    }
    
    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        font-weight: 500;
        animation: slideInDown 0.5s ease-out;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border: 2px solid #10b981;
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        border: 2px solid #f59e0b;
    }
    
    .alert-error {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
        border: 2px solid #ef4444;
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
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .floating-circle:nth-child(2) {
        width: 120px;
        height: 120px;
        top: 60%;
        right: 15%;
        animation-delay: 2s;
    }
    
    .floating-circle:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .enrollment-container {
            padding: 10px;
        }
    }
</style>";

// Floating background elements
echo "<div class='floating-elements'>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "</div>";

echo "<div class='enrollment-container'>";

// Page Header
echo "<div class='page-header'>";
echo "<div class='header-background'>";
echo "<div class='breadcrumb'>";
echo "<a href='{$CFG->wwwroot}/my/'>Dashboard</a> / ";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/'>Administration</a> / ";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/enrollments.php'>Enrollments</a> / ";
echo "<span class='breadcrumb-item'>Enroll New Student</span>";
echo "</div>";
echo "<h1 class='page-title'>Enroll New Student</h1>";
echo "<p class='page-subtitle'>Add a new student to a course with advanced options</p>";
echo "</div>";
echo "</div>";

// Show success/error message
if (isset($success_message)) {
    echo "<div class='alert alert-{$message_type}'>";
    echo "<i class='fa fa-" . ($message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'times-circle')) . "'></i> ";
    echo $success_message;
    echo "</div>";
}

// Enrollment Form
echo "<div class='enrollment-form-container'>";
echo "<div class='form-header'>";
echo "<h2 class='form-title'>Student Enrollment Form</h2>";
echo "<p class='form-subtitle'>Fill in the details below to enroll a student in a course</p>";
echo "</div>";

echo "<div class='form-content'>";
echo "<form method='POST' action=''>";

// Student Selection
echo "<div class='form-row'>";
echo "<div class='form-group'>";
echo "<label class='form-label' for='student_id'>Select Student</label>";
echo "<select class='form-select' id='student_id' name='student_id' required>";
echo "<option value=''>Choose a student...</option>";

// Get all students
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

foreach ($students as $student) {
    echo "<option value='{$student->id}'>" . fullname($student) . " ({$student->email})</option>";
}

echo "</select>";
echo "</div>";

// Course Selection
echo "<div class='form-group'>";
echo "<label class='form-label' for='course_id'>Select Course</label>";
echo "<select class='form-select' id='course_id' name='course_id' required>";
echo "<option value=''>Choose a course...</option>";

// Get all visible courses
$courses = $DB->get_records_select('course', 'id > 1 AND visible = 1', null, 'fullname ASC');
foreach ($courses as $course) {
    echo "<option value='{$course->id}'>{$course->fullname}</option>";
}

echo "</select>";
echo "</div>";
echo "</div>";

// Enrollment Method and Duration
echo "<div class='form-row'>";
echo "<div class='form-group'>";
echo "<label class='form-label' for='enrollment_method'>Enrollment Method</label>";
echo "<select class='form-select' id='enrollment_method' name='enrollment_method' required>";
echo "<option value='manual'>Manual Enrollment</option>";
echo "<option value='self'>Self Enrollment</option>";
echo "<option value='cohort'>Cohort Enrollment</option>";
echo "<option value='guest'>Guest Access</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label class='form-label optional' for='enrollment_duration'>Duration (Days)</label>";
echo "<input type='number' class='form-input' id='enrollment_duration' name='enrollment_duration' placeholder='Leave empty for unlimited' min='1' max='3650'>";
echo "</div>";
echo "</div>";

// Start and End Date
echo "<div class='form-row'>";
echo "<div class='form-group'>";
echo "<label class='form-label' for='start_date'>Start Date</label>";
echo "<input type='date' class='form-input' id='start_date' name='start_date' value='" . date('Y-m-d') . "' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label class='form-label optional' for='end_date'>End Date</label>";
echo "<input type='date' class='form-input' id='end_date' name='end_date'>";
echo "</div>";
echo "</div>";

// Form Actions
echo "<div class='form-actions'>";
echo "<button type='submit' name='enroll_student' class='btn btn-primary'>";
echo "<i class='fa fa-plus'></i> Enroll Student";
echo "</button>";
echo "<a href='enrollments.php' class='btn btn-secondary'>";
echo "<i class='fa fa-arrow-left'></i> Back to Enrollments";
echo "</a>";
echo "</div>";

echo "</form>";
echo "</div>";
echo "</div>";

echo "</div>"; // End enrollment-container

// Add JavaScript for form enhancements
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate end date based on duration
    const durationInput = document.getElementById('enrollment_duration');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    function calculateEndDate() {
        if (durationInput.value && startDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const duration = parseInt(durationInput.value);
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + duration);
            endDateInput.value = endDate.toISOString().split('T')[0];
        }
    }
    
    durationInput.addEventListener('input', calculateEndDate);
    startDateInput.addEventListener('change', calculateEndDate);
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const studentSelect = document.getElementById('student_id');
        const courseSelect = document.getElementById('course_id');
        
        if (!studentSelect.value || !courseSelect.value) {
            e.preventDefault();
            alert('Please select both student and course.');
            return false;
        }
    });
    
    // Add loading state to submit button
    form.addEventListener('submit', function() {
        const submitBtn = document.querySelector('button[type=\"submit\"]');
        submitBtn.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Enrolling...';
        submitBtn.disabled = true;
    });
});
</script>";

echo $OUTPUT->footer();
?>
