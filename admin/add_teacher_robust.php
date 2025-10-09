<?php
require_once('../../../config.php');

// Check if user is logged in and has proper permissions
require_login();
$context = context_system::instance();
require_capability('moodle/user:create', $context);

// Get company information for the logged-in user
$user_id = $USER->id;
$company_info = $DB->get_record_sql(
    "SELECT c.* FROM {company} c 
     JOIN {company_users} cu ON c.id = cu.companyid 
     WHERE cu.userid = ? AND cu.managertype = 1",
    [$user_id]
);

if (!$company_info) {
    print_error('Company not found', 'error');
}

$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_teacher'])) {
    // Validate required fields
    $required_fields = ['username', 'password', 'firstname', 'lastname', 'email'];
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
            // Method 1: Try using Moodle's user_create_user function first
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
            
            // Log the attempt
            error_log('Attempting to create user with Moodle function: ' . $user_data->username);
            
            $user_id = user_create_user($user_data);
            
            if (!$user_id) {
                // Method 2: If user_create_user fails, try direct database insertion with minimal fields
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
                    error_log('Teacher role assigned to user: ' . $user_id);
                } else {
                    error_log('Warning: Teacher role not found');
                }
                
                // Add user to company
                if ($DB->get_manager()->table_exists('company_users')) {
                    $company_user = new stdClass();
                    $company_user->userid = $user_id;
                    $company_user->companyid = $company_info->id;
                    $company_user->managertype = 0; // Regular user, not manager
                    $company_user->timecreated = time();
                    $company_user->timemodified = time();
                    
                    $DB->insert_record('company_users', $company_user);
                    error_log('User added to company: ' . $company_info->id);
                }
                
                // Redirect back with success message
                redirect(new moodle_url('/theme/remui_kids/school_manager_dashboard.php'), 
                    'Teacher "' . $user_data->firstname . ' ' . $user_data->lastname . '" has been successfully added!', 
                    null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $errors[] = 'Failed to create user account. Please try again or contact administrator.';
            }
            
        } catch (Exception $e) {
            // Enhanced error logging
            error_log('Teacher creation error: ' . $e->getMessage());
            error_log('Error file: ' . $e->getFile() . ' Line: ' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $error_message = 'Error creating teacher: ' . $e->getMessage();
            
            // Provide more helpful error messages
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_message .= ' - Username or email already exists.';
            } elseif (strpos($e->getMessage(), 'Data too long') !== false) {
                $error_message .= ' - One or more fields contain data that is too long.';
            } elseif (strpos($e->getMessage(), 'Incorrect string value') !== false) {
                $error_message .= ' - Invalid characters in one or more fields.';
            } elseif (strpos($e->getMessage(), 'Unknown column') !== false) {
                $error_message .= ' - Database schema issue detected.';
            }
            
            $errors[] = $error_message;
        }
    }
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/add_teacher_robust.php');
$PAGE->set_title('Add New Teacher');
$PAGE->set_heading('Add New Teacher');

echo $OUTPUT->header();
?>

<div class="school-manager-main-content">
    <div class="add-teacher-container">
        <div class="compact-header">
            <h1 class="page-title">Add New Teacher (Robust Version)</h1>
            <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager_dashboard.php" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div class="compact-form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="compact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <small>Must be unique and contain only letters, numbers, and underscores</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Last Name *</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <small>Must be a valid email address</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <select id="country" name="country">
                            <option value="">Select Country</option>
                            <option value="US" <?php echo (isset($_POST['country']) && $_POST['country'] === 'US') ? 'selected' : ''; ?>>United States</option>
                            <option value="UK" <?php echo (isset($_POST['country']) && $_POST['country'] === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                            <option value="CA" <?php echo (isset($_POST['country']) && $_POST['country'] === 'CA') ? 'selected' : ''; ?>>Canada</option>
                            <option value="AU" <?php echo (isset($_POST['country']) && $_POST['country'] === 'AU') ? 'selected' : ''; ?>>Australia</option>
                            <option value="SA" <?php echo (isset($_POST['country']) && $_POST['country'] === 'SA') ? 'selected' : ''; ?>>Saudi Arabia</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Notes</label>
                    <textarea id="description" name="description" rows="3" placeholder="Additional notes about this teacher"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
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

<style>
.school-manager-main-content {
    padding: 0;
    margin: 0;
    min-height: 100vh;
    background-color: #f8f9fa;
}

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
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    border-radius: 4px;
    font-size: 0.9rem;
    transition: background-color 0.2s;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

.compact-form-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.compact-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.compact-form .form-group {
    display: flex;
    flex-direction: column;
}

.compact-form label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #495057;
}

.compact-form input,
.compact-form select,
.compact-form textarea {
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.compact-form input:focus,
.compact-form select:focus,
.compact-form textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.compact-form small {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.compact-form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}
</style>

<?php
echo $OUTPUT->footer();
?>

