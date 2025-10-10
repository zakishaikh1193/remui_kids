<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * School Manager Add Teacher Page
 *
 * @package    theme_remui_kids
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

// Check if user is logged in
require_login();

// Get current user and context
$user = $USER;
$context = context_system::instance();

// Check if user is a school manager
$isschoolmanager = false;

// Check for school manager role
$schoolmanagerroles = $DB->get_records_sql(
    "SELECT DISTINCT r.shortname 
     FROM {role} r 
     JOIN {role_assignments} ra ON r.id = ra.roleid 
     JOIN {context} ctx ON ra.contextid = ctx.id 
     WHERE ra.userid = ? 
     AND ctx.contextlevel = ? 
     AND r.shortname IN ('school_manager', 'manager', 'schooladmin', 'school_admin')",
    [$user->id, CONTEXT_SYSTEM]
);

if (!empty($schoolmanagerroles)) {
    $isschoolmanager = true;
}

// Also check for school manager capabilities
if (!$isschoolmanager && (has_capability('moodle/site:config', $context, $user) || 
                         has_capability('moodle/user:create', $context, $user))) {
    $isschoolmanager = true;
}

// Redirect if not a school manager
if (!$isschoolmanager) {
    redirect($CFG->wwwroot . '/my/', 'Access denied. You must be a school manager to access this page.');
}

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/add_teacher.php');
$PAGE->set_title('Add Teacher - School Manager');
$PAGE->set_heading('Add New Teacher');
$PAGE->set_pagelayout('admin');

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $firstname = required_param('firstname', PARAM_TEXT);
        $lastname = required_param('lastname', PARAM_TEXT);
        $email = required_param('email', PARAM_EMAIL);
        $username = required_param('username', PARAM_USERNAME);
        $password = required_param('password', PARAM_TEXT);
        $phone = optional_param('phone', '', PARAM_TEXT);
        $city = optional_param('city', '', PARAM_TEXT);
        $country = optional_param('country', '', PARAM_TEXT);
        $grade_level = optional_param('grade_level', '', PARAM_TEXT);
        $notes = optional_param('notes', '', PARAM_TEXT);
        
        // Validate required fields
        if (empty($firstname) || empty($lastname) || empty($email) || empty($username) || empty($password)) {
            throw new Exception('All required fields must be filled.');
        }
        
        // Check if username already exists
        if ($DB->record_exists('user', array('username' => $username))) {
            throw new Exception('Username already exists. Please choose a different username.');
        }
        
        // Check if email already exists
        if ($DB->record_exists('user', array('email' => $email))) {
            throw new Exception('Email already exists. Please use a different email address.');
        }
        
        // Validate password strength
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }
        
        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Username can only contain letters, numbers, and underscores.');
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Create new user data using Moodle's user creation function
        $newuser = new stdClass();
        $newuser->firstname = $firstname;
        $newuser->lastname = $lastname;
        $newuser->email = $email;
        $newuser->username = $username;
        $newuser->password = $password; // Will be hashed by create_user
        $newuser->phone1 = $phone;
        $newuser->city = $city;
        $newuser->country = $country ?: 'SA'; // Default to Saudi Arabia
        $newuser->institution = 'School';
        $newuser->description = $notes;
        $newuser->descriptionformat = 1;
        $newuser->lang = 'en';
        $newuser->timezone = 'Asia/Riyadh';
        $newuser->confirmed = 1;
        $newuser->mnethostid = $CFG->mnet_localhost_id;
        
        // Use Moodle's user creation function for better error handling
        try {
            // Test database connection first
            if (!$DB->is_connected()) {
                throw new Exception('Database connection failed.');
            }
            
            $userid = user_create_user($newuser, false, false);
        } catch (Exception $e) {
            throw new Exception('Error creating teacher: ' . $e->getMessage());
        }
        
        if (!$userid) {
            throw new Exception('Error creating teacher: Failed to create user account.');
        }
        
        // Get teacher role
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        if (!$teacherrole) {
            throw new Exception('Error creating teacher: Teacher role not found.');
        }
        
        // Assign teacher role to user using Moodle's role assignment function
        try {
            role_assign($teacherrole->id, $userid, $context->id, '', 0, '', true);
        } catch (Exception $e) {
            throw new Exception('Error creating teacher: Failed to assign teacher role - ' . $e->getMessage());
        }
        
        // If IOMAD is available, assign user to company
        if (class_exists('company') && $DB->get_manager()->table_exists('company_users')) {
            try {
                $company = company::by_userid($user->id);
                if ($company) {
                    // Check if user is already in company
                    if (!$DB->record_exists('company_users', ['userid' => $userid, 'companyid' => $company->id])) {
                        // Add user to company
                        $companyuser = new stdClass();
                        $companyuser->userid = $userid;
                        $companyuser->companyid = $company->id;
                        $companyuser->managertype = 0; // Regular user, not manager
                        $companyuser->suspended = 0;
                        $companyuser->departmentid = 0;
                        
                        $DB->insert_record('company_users', $companyuser);
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the process
                error_log("Failed to assign user to company: " . $e->getMessage());
            }
        }
        
        $success_message = "Teacher '{$firstname} {$lastname}' has been successfully added to the system.";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Prepare form data for repopulation
$form_data = array(
    'firstname' => optional_param('firstname', '', PARAM_TEXT),
    'lastname' => optional_param('lastname', '', PARAM_TEXT),
    'email' => optional_param('email', '', PARAM_EMAIL),
    'username' => optional_param('username', '', PARAM_USERNAME),
    'phone' => optional_param('phone', '', PARAM_TEXT),
    'city' => optional_param('city', '', PARAM_TEXT),
    'country' => optional_param('country', '', PARAM_TEXT),
    'grade_level' => optional_param('grade_level', '', PARAM_TEXT),
    'notes' => optional_param('notes', '', PARAM_TEXT)
);

// Add country selection helpers
$countries = array('SA', 'US', 'UK', 'CA', 'AU');
foreach ($countries as $country_code) {
    $form_data['selected_country_' . strtolower($country_code)] = ($form_data['country'] === $country_code);
}

// Add grade level selection helpers
for ($i = 1; $i <= 12; $i++) {
    $form_data['selected_grade_' . $i] = ($form_data['grade_level'] === (string)$i);
}

// Prepare template context
$templatecontext = array(
    'wwwroot' => $CFG->wwwroot,
    'sesskey' => sesskey(),
    'user' => array(
        'fullname' => fullname($user),
        'firstname' => $user->firstname,
        'lastname' => $user->lastname
    ),
    'success_message' => $success_message,
    'error_message' => $error_message,
    'form_data' => $form_data
);

// Output the page
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_remui_kids/school_manager_add_teacher', $templatecontext);
echo $OUTPUT->footer();
