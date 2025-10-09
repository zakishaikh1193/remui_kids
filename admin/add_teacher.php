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

// School Manager Sidebar Navigation
$sidebarcontext = [
    'company_name' => $company_info ? $company_info->name : 'School Dashboard',
    'company_logo_url' => $company_info && isset($company_info->logo_filename) 
        ? $CFG->wwwroot . '/theme/remui_kids/get_company_logo.php?id=' . $company_info->id 
        : null,
    'has_logo' => $company_info && isset($company_info->logo_filename),
    'user_info' => [
        'fullname' => fullname($USER),
        'email' => $USER->email,
        'id' => $USER->id
    ],
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ],
    'teachers_active' => true,
    'sesskey' => sesskey()
];

echo $OUTPUT->render_from_template('theme_remui_kids/school_manager_sidebar', $sidebarcontext);

?>

<!-- Main Content Area -->
<div class="school-manager-main-content">
    <div class="add-teacher-container">
        
        <!-- Compact Header -->
        <div class="compact-header">
            <h1 class="page-title">Add New Teacher</h1>
            <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager_dashboard.php" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- Compact Form Container -->
        <div class="compact-form-container">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <h4>Please fix the following errors:</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="compact-form">
                <div class="form-row">
                    
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="username" class="form-label required">Username</label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               placeholder="Enter username" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label required">Password</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter password" required>
                    </div>

                    <div class="form-group">
                        <label for="firstname" class="form-label required">First Name</label>
                        <input type="text" id="firstname" name="firstname" class="form-input" 
                               value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" 
                               placeholder="Enter first name" required>
                    </div>

                    <div class="form-group">
                        <label for="lastname" class="form-label required">Last Name</label>
                        <input type="text" id="lastname" name="lastname" class="form-input" 
                               value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" 
                               placeholder="Enter last name" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               placeholder="Enter email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               placeholder="Enter phone">
                    </div>

                    <div class="form-group">
                        <label for="city" class="form-label">City</label>
                        <input type="text" id="city" name="city" class="form-input" 
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" 
                               placeholder="Enter city">
                    </div>

                    <div class="form-group">
                        <label for="country" class="form-label">Country</label>
                        <select id="country" name="country" class="form-select">
                            <option value="">Select Country</option>
                            <option value="SA" <?php echo (isset($_POST['country']) && $_POST['country'] == 'SA') ? 'selected' : ''; ?>>Saudi Arabia</option>
                            <option value="AE" <?php echo (isset($_POST['country']) && $_POST['country'] == 'AE') ? 'selected' : ''; ?>>UAE</option>
                            <option value="QA" <?php echo (isset($_POST['country']) && $_POST['country'] == 'QA') ? 'selected' : ''; ?>>Qatar</option>
                            <option value="KW" <?php echo (isset($_POST['country']) && $_POST['country'] == 'KW') ? 'selected' : ''; ?>>Kuwait</option>
                            <option value="OM" <?php echo (isset($_POST['country']) && $_POST['country'] == 'OM') ? 'selected' : ''; ?>>Oman</option>
                            <option value="BH" <?php echo (isset($_POST['country']) && $_POST['country'] == 'BH') ? 'selected' : ''; ?>>Bahrain</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="grade" class="form-label">Grade Level</label>
                        <select id="grade" name="grade" class="form-select">
                            <option value="">Select Grade</option>
                            <option value="Grade 1" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 1') ? 'selected' : ''; ?>>Grade 1</option>
                            <option value="Grade 2" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 2') ? 'selected' : ''; ?>>Grade 2</option>
                            <option value="Grade 3" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 3') ? 'selected' : ''; ?>>Grade 3</option>
                            <option value="Grade 4" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 4') ? 'selected' : ''; ?>>Grade 4</option>
                            <option value="Grade 5" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 5') ? 'selected' : ''; ?>>Grade 5</option>
                            <option value="Grade 6" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 6') ? 'selected' : ''; ?>>Grade 6</option>
                            <option value="Grade 7" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 7') ? 'selected' : ''; ?>>Grade 7</option>
                            <option value="Grade 8" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 8') ? 'selected' : ''; ?>>Grade 8</option>
                            <option value="Grade 9" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 9') ? 'selected' : ''; ?>>Grade 9</option>
                            <option value="Grade 10" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'Grade 10') ? 'selected' : ''; ?>>Grade 10</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Notes</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3" 
                                  placeholder="Additional notes about this teacher"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                </div>

                <!-- Compact Form Actions -->
                <div class="compact-form-actions">
                    <button type="submit" name="submit_teacher" class="btn btn-primary">
                        <i class="fa fa-user-plus"></i> Add Teacher
                    </button>
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager_dashboard.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Compact Styles -->
<style>
    .add-teacher-container {
        padding: 1rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .compact-header {
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

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 0.375rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
    }

    .compact-form-container {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
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
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .compact-form .form-input:focus,
    .compact-form .form-select:focus,
    .compact-form .form-textarea:focus {
        outline: none;
        border-color: #007cba;
        box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
    }

    .compact-form .form-textarea {
        resize: vertical;
        min-height: 80px;
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
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
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

        .compact-form .form-row {
            grid-template-columns: 1fr;
        }

        .compact-form-actions {
            flex-direction: column;
        }
    }
</style>

<?php
echo $OUTPUT->footer();
?>