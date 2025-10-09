<?php
require_once('../../../config.php');
require_login();

global $USER, $DB, $CFG, $OUTPUT;

// Check if user is company manager
$companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
if (!$companymanagerrole) {
    redirect(new moodle_url('/theme/remui_kids/school_manager_dashboard.php'), 'Company manager role not found!', null, \core\output\notification::NOTIFY_ERROR);
}

$context = context_system::instance();
$is_company_manager = user_has_role_assignment($USER->id, $companymanagerrole->id, $context->id);

if (!$is_company_manager) {
    redirect(new moodle_url('/theme/remui_kids/school_manager_dashboard.php'), 'You must be a company manager to access this page!', null, \core\output\notification::NOTIFY_ERROR);
}

// Get company info
$company_info = null;
if ($DB->get_manager()->table_exists('company') && $DB->get_manager()->table_exists('company_users')) {
    $company_info = $DB->get_record_sql(
        "SELECT c.*, u.firstname, u.lastname, u.email
         FROM {company} c
         JOIN {company_users} cu ON c.id = cu.companyid
         JOIN {user} u ON cu.userid = u.id
         WHERE cu.userid = ? AND cu.managertype = 1",
        [$USER->id]
    );
}

if (!$company_info) {
    redirect(new moodle_url('/theme/remui_kids/school_manager_dashboard.php'), 'No company information found!', null, \core\output\notification::NOTIFY_ERROR);
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/add_teacher.php');
$PAGE->set_title('Add Teacher - ' . $company_info->name);
$PAGE->set_heading('Add Teacher');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_teacher'])) {
    
    // Validate required fields
    $required_fields = ['username', 'firstname', 'lastname', 'email'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required.';
        }
    }
    
    // Validate email format
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Check if username already exists
    if (!empty($_POST['username']) && $DB->record_exists('user', ['username' => $_POST['username']])) {
        $errors[] = 'Username already exists. Please choose a different username.';
    }
    
    // Check if email already exists
    if (!empty($_POST['email']) && $DB->record_exists('user', ['email' => $_POST['email']])) {
        $errors[] = 'Email already exists. Please use a different email address.';
    }
    
    // Validate password
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if (empty($errors)) {
        try {
            // Use Moodle's built-in user creation function for better reliability
            $user_data = new stdClass();
            $user_data->username = trim($_POST['username']);
            $user_data->password = $_POST['password']; // Will be hashed by create_user
            $user_data->firstname = trim($_POST['firstname']);
            $user_data->lastname = trim($_POST['lastname']);
            $user_data->email = trim($_POST['email']);
            $user_data->city = !empty($_POST['city']) ? trim($_POST['city']) : '';
            $user_data->country = !empty($_POST['country']) ? $_POST['country'] : '';
            $user_data->phone1 = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
            $user_data->description = !empty($_POST['description']) ? trim($_POST['description']) : '';
            $user_data->auth = 'manual';
            $user_data->confirmed = 1;
            $user_data->mnethostid = $CFG->mnet_localhost_id;
            
            // Debug: Log the user data being created
            error_log('Creating user with Moodle function: ' . $user_data->username);
            
            // Use Moodle's user creation function
            $user_id = user_create_user($user_data);
            
            if (!$user_id) {
                // Fallback: Try direct insertion with minimal required fields
                error_log('user_create_user failed, trying direct insertion');
                
                $minimal_user = new stdClass();
                $minimal_user->username = trim($_POST['username']);
                $minimal_user->password = hash_internal_user_password($_POST['password']);
                $minimal_user->firstname = trim($_POST['firstname']);
                $minimal_user->lastname = trim($_POST['lastname']);
                $minimal_user->email = trim($_POST['email']);
                $minimal_user->auth = 'manual';
                $minimal_user->confirmed = 1;
                $minimal_user->mnethostid = $CFG->mnet_localhost_id;
                $minimal_user->timecreated = time();
                $minimal_user->timemodified = time();
                $minimal_user->firstaccess = 0;
                $minimal_user->lastaccess = 0;
                $minimal_user->suspended = 0;
                $minimal_user->deleted = 0;
                
                $user_id = $DB->insert_record('user', $minimal_user);
                
                if ($user_id) {
                    // Update with additional fields
                    $update_user = new stdClass();
                    $update_user->id = $user_id;
                    $update_user->city = !empty($_POST['city']) ? trim($_POST['city']) : '';
                    $update_user->country = !empty($_POST['country']) ? $_POST['country'] : '';
                    $update_user->phone1 = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
                    $update_user->description = !empty($_POST['description']) ? trim($_POST['description']) : '';
                    
                    $DB->update_record('user', $update_user);
                    error_log('User created via direct insertion: ' . $user_id);
                }
            }
            
            if ($user_id) {
                // Assign teacher role
                $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
                if ($teacher_role) {
                    role_assign($teacher_role->id, $user_id, $context->id);
                }
                
                // Add user to company
                $company_user = new stdClass();
                $company_user->userid = $user_id;
                $company_user->companyid = $company_info->id;
                $company_user->managertype = 0; // Regular user, not manager
                $company_user->timecreated = time();
                $company_user->timemodified = time();
                
                $DB->insert_record('company_users', $company_user);
                
                // Store grade information if provided
                if (!empty($_POST['grade'])) {
                    // Try to find or create a custom field for grade
                    $grade_field = $DB->get_record('user_info_field', ['shortname' => 'grade']);
                    if (!$grade_field) {
                        // Create grade field if it doesn't exist
                        $grade_field = new stdClass();
                        $grade_field->shortname = 'grade';
                        $grade_field->name = 'Grade';
                        $grade_field->datatype = 'text';
                        $grade_field->description = 'Teacher Grade Level';
                        $grade_field->required = 0;
                        $grade_field->locked = 0;
                        $grade_field->visible = 1;
                        $grade_field->forceunique = 0;
                        $grade_field->signup = 0;
                        $grade_field->defaultdata = '';
                        $grade_field->param1 = '';
                        $grade_field->param2 = '';
                        $grade_field->param3 = '';
                        $grade_field->param4 = '';
                        $grade_field->param5 = '';
                        
                        $grade_field_id = $DB->insert_record('user_info_field', $grade_field);
                        $grade_field->id = $grade_field_id;
                    }
                    
                    // Add grade data for user
                    $grade_data = new stdClass();
                    $grade_data->userid = $user_id;
                    $grade_data->fieldid = $grade_field->id;
                    $grade_data->data = $_POST['grade'];
                    
                    $DB->insert_record('user_info_data', $grade_data);
                }
                
                // Redirect back with success message
                redirect(new moodle_url('/theme/remui_kids/school_manager_dashboard.php'), 
                    'Teacher "' . $new_user->firstname . ' ' . $new_user->lastname . '" has been successfully added!', 
                    null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $errors[] = 'Failed to create user account.';
            }
            
        } catch (Exception $e) {
            // Log the full error for debugging
            error_log('Error creating teacher: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $errors[] = 'Error creating teacher: ' . $e->getMessage();
            
            // Add more specific error information
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = 'Username or email already exists. Please choose different values.';
            } elseif (strpos($e->getMessage(), 'Data too long') !== false) {
                $errors[] = 'One or more fields contain data that is too long.';
            } elseif (strpos($e->getMessage(), 'Incorrect string value') !== false) {
                $errors[] = 'Invalid characters in one or more fields.';
            }
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
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .compact-header h1.page-title {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    .form-section {
        margin-bottom: 40px;
        padding: 30px;
        background: #dcfce7;
        border-radius: 15px;
        border-left: 4px solid #166534;
        animation: slideInLeft 0.8s ease-out;
    }

    .back-btn:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
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

    .compact-form .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .compact-form .form-group {
        margin-bottom: 0;
    }

    .compact-form .form-group.full-width {
        grid-column: 1 / -1;
    }

    .compact-form .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
        font-size: 0.875rem;
    }

    .compact-form .form-label.required::after {
        content: ' *';
        color: #dc3545;
    }

    .compact-form .form-input,
    .compact-form .form-select,
    .compact-form .form-textarea {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #dcfce7;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .compact-form .form-input:focus,
    .compact-form .form-select:focus,
    .compact-form .form-textarea:focus {
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

    .compact-form-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-start;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
    }

    .compact-form .btn {
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
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    .compact-form .btn-primary {
        background: #007cba;
        color: white;
    }
    
    .compact-form .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }

    .compact-form .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .compact-form .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }

    .alert {
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    .alert h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
    }

    .alert ul {
        margin: 0;
        padding-left: 1.25rem;
    }

    .alert li {
        margin-bottom: 0.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .compact-header {
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-start;
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
</style>

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
