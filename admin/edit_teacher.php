<?php
/**
 * Edit Teacher Page - Beautiful animated page for editing teacher details
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

// Get teacher ID from URL
$teacher_id = optional_param('id', 0, PARAM_INT);

if (!$teacher_id) {
    header('Location: teachers_list.php');
    exit;
}

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/edit_teacher.php', ['id' => $teacher_id]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Edit Teacher');
$PAGE->set_heading('Edit Teacher');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Get teacher data
$teacher = $DB->get_record('user', ['id' => $teacher_id]);
if (!$teacher) {
    header('Location: teachers_list.php');
    exit;
}

// Handle form submission
if ($_POST) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    if ($firstname && $lastname && $email && $username) {
        $teacher->firstname = $firstname;
        $teacher->lastname = $lastname;
        $teacher->email = $email;
        $teacher->username = $username;
        $teacher->timemodified = time();
        
        if ($DB->update_record('user', $teacher)) {
            $success_message = "Teacher updated successfully!";
        } else {
            $error_message = "Failed to update teacher. Please try again.";
        }
    } else {
        $error_message = "All fields are required.";
    }
}

echo $OUTPUT->header();

// Add custom CSS with animations
echo "<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
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
    
    .edit-container {
        max-width: 800px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
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
    
    .edit-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .edit-header::before {
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
    
    .edit-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
        animation: fadeInDown 1s ease-out 0.3s both;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .edit-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        animation: fadeInUp 1s ease-out 0.5s both;
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
    
    .teacher-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        margin: 20px auto;
        border: 3px solid rgba(255, 255, 255, 0.3);
        animation: pulse 2s infinite;
        position: relative;
        z-index: 1;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .edit-form {
        padding: 40px;
        animation: fadeIn 1s ease-out 0.7s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .form-group {
        position: relative;
        margin-bottom: 30px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-control {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8fafc;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }
    
    .form-control:hover {
        border-color: #cbd5e0;
        background: white;
    }
    
    .button-group {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 40px;
    }
    
    .btn {
        padding: 15px 30px;
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
        min-width: 150px;
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
    
    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        font-weight: 500;
        animation: slideInDown 0.5s ease-out;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }
    
    .alert-error {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
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
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .edit-title {
            font-size: 2rem;
        }
        
        .button-group {
            flex-direction: column;
            align-items: center;
        }
        
        .btn {
            width: 100%;
            max-width: 300px;
        }
    }
</style>";

// Floating background elements
echo "<div class='floating-elements'>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "</div>";

echo "<div class='edit-container'>";
echo "<div class='edit-header'>";
echo "<div class='breadcrumb'>";
echo "<a href='{$CFG->wwwroot}/my/'>Dashboard</a> / ";
echo "<a href='teachers_list.php'>Teachers</a> / ";
echo "<span class='breadcrumb-item'>Edit Teacher</span>";
echo "</div>";

echo "<div class='teacher-avatar'>";
echo strtoupper(substr($teacher->firstname, 0, 1));
echo "</div>";

echo "<h1 class='edit-title'>Edit Teacher</h1>";
echo "<p class='edit-subtitle'>Update teacher information and details</p>";
echo "</div>";

echo "<div class='edit-form'>";

// Show success/error messages
if (isset($success_message)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='fa fa-check-circle'></i> $success_message";
    echo "</div>";
}

if (isset($error_message)) {
    echo "<div class='alert alert-error'>";
    echo "<i class='fa fa-exclamation-circle'></i> $error_message";
    echo "</div>";
}

echo "<form method='POST' action=''>";
echo "<div class='form-row'>";
echo "<div class='form-group'>";
echo "<label class='form-label'>First Name</label>";
echo "<input type='text' class='form-control' name='firstname' value='" . htmlspecialchars($teacher->firstname) . "' required>";
echo "</div>";
echo "<div class='form-group'>";
echo "<label class='form-label'>Last Name</label>";
echo "<input type='text' class='form-control' name='lastname' value='" . htmlspecialchars($teacher->lastname) . "' required>";
echo "</div>";
echo "</div>";

echo "<div class='form-row'>";
echo "<div class='form-group'>";
echo "<label class='form-label'>Username</label>";
echo "<input type='text' class='form-control' name='username' value='" . htmlspecialchars($teacher->username) . "' required>";
echo "</div>";
echo "<div class='form-group'>";
echo "<label class='form-label'>Email Address</label>";
echo "<input type='email' class='form-control' name='email' value='" . htmlspecialchars($teacher->email) . "' required>";
echo "</div>";
echo "</div>";

echo "<div class='button-group'>";
echo "<button type='submit' class='btn btn-primary'>";
echo "<i class='fa fa-save'></i> Update Teacher";
echo "</button>";
echo "<a href='teachers_list.php' class='btn btn-secondary'>";
echo "<i class='fa fa-arrow-left'></i> Back to Teachers";
echo "</a>";
echo "</div>";

echo "</form>";
echo "</div>";
echo "</div>";

echo $OUTPUT->footer();
?>
