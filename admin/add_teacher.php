<?php
/**
 * Add Teacher Page - Beautiful animated page for adding new teachers
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/add_teacher.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Add New Teacher');
$PAGE->set_heading('Add New Teacher');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Handle form submission
if ($_POST) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $error_message = '';
    
    if (!$firstname || !$lastname || !$email || !$username || !$password) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($DB->record_exists('user', ['username' => $username])) {
        $error_message = "Username already exists. Please choose a different username.";
    } elseif ($DB->record_exists('user', ['email' => $email])) {
        $error_message = "Email already exists. Please use a different email address.";
    } else {
        // Create new user
        $user = new stdClass();
        $user->username = $username;
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $user->timecreated = time();
        $user->timemodified = time();
        
        $userid = $DB->insert_record('user', $user);
        
        if ($userid) {
            // Assign teachers role
            $teacherrole = $DB->get_record('role', ['shortname' => 'teachers']);
            if ($teacherrole) {
                $context = context_system::instance();
                role_assign($teacherrole->id, $userid, $context->id);
            }
            $success_message = "Teacher created successfully! Redirecting to teachers list...";
            // Redirect after 2 seconds
            echo "<script>setTimeout(function(){ window.location.href = 'teachers_list.php'; }, 2000);</script>";
        } else {
            $error_message = "Failed to create teacher. Please try again.";
        }
    }
}

echo $OUTPUT->header();

// Add custom CSS with pastel green theme and sidebar
echo "<style>";
echo "
    .add-container {
        max-width: 1200px;
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
    
    .add-header {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: black;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .add-header::before {
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
    
    .add-title {
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
    
    .add-subtitle {
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
    
    .teacher-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fce7f3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 20px auto;
        border: 3px solid rgba(255, 255, 255, 0.3);
        animation: bounce 2s infinite;
        position: relative;
        z-index: 1;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .add-form {
        padding: 40px;
        animation: fadeIn 1s ease-out 0.7s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .form-section {
        margin-bottom: 40px;
        padding: 30px;
        background: #dcfce7;
        border-radius: 15px;
        border-left: 4px solid #166534;
        animation: slideInLeft 0.8s ease-out;
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
    
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #166534;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .form-group {
        position: relative;
        margin-bottom: 25px;
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
        border: 2px solid #dcfce7;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #166534;
        box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.1);
        transform: translateY(-2px);
    }
    
    .form-control:hover {
        border-color: #bbf7d0;
    }
    
    .password-strength {
        margin-top: 5px;
        font-size: 0.85rem;
    }
    
    .strength-weak { color: #e53e3e; }
    .strength-medium { color: #ed8936; }
    .strength-strong { color: #48bb78; }
    
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
        background: linear-gradient(135deg, #166534 0%, #15803d 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(22, 101, 52, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(22, 101, 52, 0.4);
    }
    
    .btn-secondary {
        background: #dcfce7;
        color: #166534;
        border: 2px solid #bbf7d0;
    }
    
    .btn-secondary:hover {
        background: #bbf7d0;
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
        width: 100px;
        height: 100px;
        top: 15%;
        left: 15%;
        animation-delay: 0s;
    }
    
    .floating-circle:nth-child(2) {
        width: 80px;
        height: 80px;
        top: 70%;
        right: 15%;
        animation-delay: 2s;
    }
    
    .floating-circle:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 25%;
        left: 25%;
        animation-delay: 4s;
    }
    
    .floating-circle:nth-child(4) {
        width: 120px;
        height: 120px;
        top: 40%;
        right: 30%;
        animation-delay: 1s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .progress-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }
    
    .progress-step {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 10px;
        font-weight: bold;
        color: #a0aec0;
        transition: all 0.3s ease;
    }
    
    .progress-step.active {
        background: #48bb78;
        color: white;
        transform: scale(1.1);
    }
    
    .progress-step.completed {
        background: #38a169;
        color: white;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .add-title {
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
";
echo "</style>";
    
    // Floating background elements
    echo "<div class='floating-elements'>";
    echo "<div class='floating-circle'></div>";
    echo "<div class='floating-circle'></div>";
    echo "<div class='floating-circle'></div>";
    echo "<div class='floating-circle'></div>";
    echo "</div>";

    // Admin Sidebar Navigation (copied from courses.php)
    echo "<div class='admin-sidebar'>";
    echo "<div class='sidebar-content'>";
    echo "<!-- DASHBOARD Section -->";
    echo "<div class='sidebar-section'>";
    echo "<h3 class='sidebar-category'>DASHBOARD</h3>";
    echo "<ul class='sidebar-menu'>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/my/' class='sidebar-link'>";
    echo "<i class='fa fa-th-large sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Admin Dashboard</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/admin/search.php' class='sidebar-link'>";
    echo "<i class='fa fa-cog sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Site Administration</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-users sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Community</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/enrollments.php' class='sidebar-link'>";
    echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Enrollments</span>";
    echo "</a>";
    echo "</li>";
    echo "</ul>";
    echo "</div>";

    echo "<!-- TEACHERS Section -->";
    echo "<div class='sidebar-section'>";
    echo "<h3 class='sidebar-category'>TEACHERS</h3>";
    echo "<ul class='sidebar-menu'>";
    echo "<li class='sidebar-item active'>";
    echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/teachers_list.php' class='sidebar-link'>";
    echo "<i class='fa fa-users sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Teachers</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-medal sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Master Trainers</span>";
    echo "</a>";
    echo "</li>";
    echo "</ul>";
    echo "</div>";

    echo "<!-- COURSES & PROGRAMS Section -->";
    echo "<div class='sidebar-section'>";
    echo "<h3 class='sidebar-category'>COURSES & PROGRAMS</h3>";
    echo "<ul class='sidebar-menu'>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/courses.php' class='sidebar-link'>";
    echo "<i class='fa fa-book sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Courses & Programs</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Certifications</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-clipboard-list sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Assessments</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-school sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Schools</span>";
    echo "</a>";
    echo "</li>";
    echo "</ul>";
    echo "</div>";

    echo "<!-- INSIGHTS Section -->";
    echo "<div class='sidebar-section'>";
    echo "<h3 class='sidebar-category'>INSIGHTS</h3>";
    echo "<ul class='sidebar-menu'>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/local/edwiserreports/index.php' class='sidebar-link'>";
    echo "<i class='fa fa-chart-bar sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Analytics</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-chart-line sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Predictive Models</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-file-alt sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Reports</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-map sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Competencies Map</span>";
    echo "</a>";
    echo "</li>";
    echo "</ul>";
    echo "</div>";

    echo "<!-- SETTINGS Section -->";
    echo "<div class='sidebar-section'>";
    echo "<h3 class='sidebar-category'>SETTINGS</h3>";
    echo "<ul class='sidebar-menu'>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-cog sidebar-icon'></i>";
    echo "<span class='sidebar-text'>System Settings</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/users_management_dashboard.php' class='sidebar-link'>";
    echo "<i class='fa fa-user-friends sidebar-icon'></i>";
    echo "<span class='sidebar-text'>User Management</span>";
    echo "</a>";
    echo "</li>";
    echo "<li class='sidebar-item'>";
    echo "<a href='#' class='sidebar-link'>";
    echo "<i class='fa fa-users-cog sidebar-icon'></i>";
    echo "<span class='sidebar-text'>Cohort Navigation</span>";
    echo "</a>";
    echo "</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Main content area with sidebar
    echo "<div class='admin-main-content'>";

   

    echo "<div class='add-container'>";
    echo "<div class='add-header'>";
    echo "<div class='breadcrumb'>";
    echo "<a href='{$CFG->wwwroot}/my/'>Dashboard</a> / ";
    echo "<a href='teachers_list.php'>Teachers</a> / ";
    echo "<span class='breadcrumb-item'>Add New Teacher</span>";
    echo "</div>";

    echo "<div class='teacher-icon'>";
    echo "<i class='fa fa-user-plus'></i>";
    echo "</div>";

    echo "<h1 class='add-title'>Add New Teacher</h1>";
    echo "<p class='add-subtitle'>Create a new teacher account with full access</p>";
    echo "</div>";

    echo "<div class='add-form'>";

    // Progress indicator
    echo "<div class='progress-indicator'>";
    echo "<div class='progress-step active'>1</div>";
    echo "<div class='progress-step'>2</div>";
    echo "<div class='progress-step'>3</div>";
    echo "</div>";

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

    // Personal Information Section
    echo "<div class='form-section'>";
    echo "<h3 class='section-title'>";
    echo "<i class='fa fa-user'></i> Personal Information";
    echo "</h3>";
    echo "<div class='form-row'>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>First Name</label>";
    echo "<input type='text' class='form-control' name='firstname' value='" . (isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '') . "' required>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>Last Name</label>";
    echo "<input type='text' class='form-control' name='lastname' value='" . (isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '') . "' required>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Account Information Section
    echo "<div class='form-section'>";
    echo "<h3 class='section-title'>";
    echo "<i class='fa fa-key'></i> Account Information";
    echo "</h3>";
    echo "<div class='form-row'>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>Username</label>";
    echo "<input type='text' class='form-control' name='username' value='" . (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '') . "' required>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>Email Address</label>";
    echo "<input type='email' class='form-control' name='email' value='" . (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') . "' required>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Security Section
    echo "<div class='form-section'>";
    echo "<h3 class='section-title'>";
    echo "<i class='fa fa-shield-alt'></i> Security";
    echo "</h3>";
    echo "<div class='form-row'>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>Password</label>";
    echo "<input type='password' class='form-control' name='password' id='password' required>";
    echo "<div class='password-strength' id='password-strength'></div>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label class='form-label'>Confirm Password</label>";
    echo "<input type='password' class='form-control' name='confirm_password' required>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    echo "<div class='button-group'>";
    echo "<button type='submit' class='btn btn-primary'>";
    echo "<i class='fa fa-user-plus'></i> Create Teacher";
    echo "</button>";
    echo "<a href='teachers_list.php' class='btn btn-secondary'>";
    echo "<i class='fa fa-arrow-left'></i> Back to Teachers";
    echo "</a>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "</div>";

    // Password strength checker
    echo "<script>
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.textContent = '';
        return;
    }
    
    let strength = 0;
    let message = '';
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    if (strength < 2) {
        message = 'Weak password';
        strengthDiv.className = 'password-strength strength-weak';
    } else if (strength < 4) {
        message = 'Medium strength';
        strengthDiv.className = 'password-strength strength-medium';
    } else {
        message = 'Strong password';
        strengthDiv.className = 'password-strength strength-strong';
    }
    
    strengthDiv.textContent = message;
});
</script>";

    // Add JavaScript for sidebar toggle
    echo <<<JS
<script>
// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('sidebar-open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('sidebar-open');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
});
</script>
JS;

    // Close admin-main-content div
    echo "</div>";

    echo $OUTPUT->footer();
    ?>
