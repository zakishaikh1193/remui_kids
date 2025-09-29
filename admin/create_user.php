<?php
/**
 * Create User Page
 * Comprehensive user creation with validation and animations
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get current user
global $USER, $DB, $OUTPUT;

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'create_user':
            try {
                $raw_input = file_get_contents('php://input');
                error_log("Raw input: " . $raw_input); // Debug log
                
                $data = json_decode($raw_input, true);
                error_log("Decoded data: " . print_r($data, true)); // Debug log
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON data: " . json_last_error_msg());
                }
                
                // Validate required fields
                $required_fields = ['username', 'email', 'firstname', 'lastname', 'password'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        throw new Exception("Field {$field} is required");
                    }
                }
                
                // Check if username already exists
                if ($DB->record_exists('user', ['username' => $data['username']])) {
                    throw new Exception("Username '{$data['username']}' already exists. Please choose a different username.");
                }
                
                // Check if email already exists
                if ($DB->record_exists('user', ['email' => $data['email']])) {
                    throw new Exception("Email '{$data['email']}' already exists. Please use a different email address.");
                }
                
                // Create user object
                $user = new stdClass();
                $user->username = $data['username'];
                $user->email = $data['email'];
                $user->firstname = $data['firstname'];
                $user->lastname = $data['lastname'];
                $user->password = hash_internal_user_password($data['password']);
                $user->confirmed = 1;
                $user->mnethostid = 1;
                $user->timecreated = time();
                $user->timemodified = time();
                $user->lang = $data['lang'] ?? 'en';
                $user->timezone = $data['timezone'] ?? '99';
                $user->maildisplay = $data['maildisplay'] ?? 2;
                $user->mailformat = $data['mailformat'] ?? 1;
                $user->maildigest = $data['maildigest'] ?? 0;
                $user->autosubscribe = $data['autosubscribe'] ?? 1;
                $user->trackforums = $data['trackforums'] ?? 0;
                $user->deleted = 0;
                $user->suspended = 0;
                
                // Insert user
                error_log("Attempting to insert user: " . print_r($user, true)); // Debug log
                $userid = $DB->insert_record('user', $user);
                error_log("User ID returned: " . $userid); // Debug log
                
                if (!$userid) {
                    throw new Exception("Failed to create user");
                }
                
                // Assign role if specified
                if (!empty($data['role'])) {
                    $role = $DB->get_record('role', ['shortname' => $data['role']]);
                    if ($role) {
                        role_assign($role->id, $userid, $context->id);
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User created successfully',
                    'userid' => $userid,
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname']
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'check_username':
            $username = $_GET['username'] ?? '';
            $exists = $DB->record_exists('user', ['username' => $username]);
            echo json_encode(['available' => !$exists]);
            exit;
            
        case 'check_email':
            $email = $_GET['email'] ?? '';
            $exists = $DB->record_exists('user', ['email' => $email]);
            echo json_encode(['available' => !$exists]);
            exit;
    }
}

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/create_user.php');
$PAGE->set_title('Create New User');
$PAGE->set_heading('Create New User');

// Get available roles
$roles = $DB->get_records('role', [], 'name ASC');

// Prepare template data
$templatecontext = [
    'roles' => array_values($roles),
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_remui_kids/create_user', $templatecontext);
echo $OUTPUT->footer();
?>
