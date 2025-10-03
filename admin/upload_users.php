<?php
// Aggressive cache prevention - must be first
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(uniqid()) . '"');

require_once('../../../config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/upload_users.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Upload Users');
$PAGE->set_heading('Upload Users');

// Handle AJAX requests
if (isset($_GET['action'])) {
    // Additional headers for AJAX
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('ETag: "' . md5(uniqid()) . '"');
    
    switch ($_GET['action']) {
        case 'upload_csv':
            try {
                // Debug: Log the request
                error_log("CSV Upload Debug - Files: " . print_r($_FILES, true));
                error_log("CSV Upload Debug - POST: " . print_r($_POST, true));
                
                if (!isset($_FILES['csv_file'])) {
                    throw new Exception('No file uploaded - csv_file not found in request');
                }
                
                if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    $error_msg = isset($error_messages[$_FILES['csv_file']['error']]) 
                        ? $error_messages[$_FILES['csv_file']['error']] 
                        : 'Unknown upload error: ' . $_FILES['csv_file']['error'];
                    throw new Exception($error_msg);
                }
                
                $file = $_FILES['csv_file'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if ($file_extension !== 'csv') {
                    throw new Exception('Please upload a CSV file. File extension: ' . $file_extension);
                }
                
                if (!file_exists($file['tmp_name'])) {
                    throw new Exception('Temporary file not found');
                }
                
                if (!is_readable($file['tmp_name'])) {
                    throw new Exception('Temporary file is not readable');
                }
                
                $csv_data = [];
                $handle = fopen($file['tmp_name'], 'r');
                
                if (!$handle) {
                    throw new Exception('Could not open CSV file for reading');
                }
                
                $headers = fgetcsv($handle);
                
                if (!$headers) {
                    fclose($handle);
                    throw new Exception('Invalid CSV file - could not read headers');
                }
                
                // Remove BOM and trim headers
                $headers = array_map(function($header) {
                    return trim($header, "\xEF\xBB\xBF \t\n\r\0\x0B");
                }, $headers);
                
                $required_headers = ['username', 'email', 'firstname', 'lastname', 'password'];
                $missing_headers = array_diff($required_headers, $headers);
                
                if (!empty($missing_headers)) {
                    fclose($handle);
                    throw new Exception('Missing required headers: ' . implode(', ', $missing_headers) . '. Found headers: ' . implode(', ', $headers));
                }
                
                $row_number = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $row_number++;
                    
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    if (count($row) !== count($headers)) {
                        fclose($handle);
                        throw new Exception("Row {$row_number}: Column count mismatch. Expected " . count($headers) . " columns, got " . count($row));
                    }
                    
                    $csv_data[] = array_combine($headers, $row);
                }
                fclose($handle);
                
                if (empty($csv_data)) {
                    throw new Exception('No data rows found in CSV file');
                }
                
                $validation_results = validateCsvData($csv_data);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $csv_data,
                    'validation' => $validation_results,
                    'total_rows' => count($csv_data)
                ]);
                
            } catch (Exception $e) {
                error_log("CSV Upload Error: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'create_users':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $users_data = $data['users'] ?? [];
                $company_id = $data['company_id'] ?? 1; // Default company
                $department_id = $data['department_id'] ?? null;
                $results = [];
                $success_count = 0;
                $error_count = 0;
                
                foreach ($users_data as $index => $user_data) {
                    try {
                        // Check if user already exists
                        if ($DB->record_exists('user', ['username' => $user_data['username']])) {
                            throw new Exception("Username '{$user_data['username']}' already exists");
                        }
                        
                        if ($DB->record_exists('user', ['email' => $user_data['email']])) {
                            throw new Exception("Email '{$user_data['email']}' already exists");
                        }
                        
                        // Create user object
                        $user = new stdClass();
                        $user->username = $user_data['username'];
                        $user->email = $user_data['email'];
                        $user->firstname = $user_data['firstname'];
                        $user->lastname = $user_data['lastname'];
                        $user->password = hash_internal_user_password($user_data['password']);
                        $user->confirmed = 1;
                        $user->mnethostid = $CFG->mnet_localhost_id;
                        $user->timecreated = time();
                        $user->timemodified = time();
                        $user->deleted = 0;
                        $user->suspended = 0;
                        $user->auth = 'manual';
                        
                        $userid = $DB->insert_record('user', $user);
                        
                        // Assign student role
                        $role = $DB->get_record('role', ['shortname' => 'student']);
                        if ($role) {
                            $context = context_system::instance();
                            role_assign($role->id, $userid, $context->id);
                        }
                        
                        // Assign user to company if company_users table exists
                        if ($DB->get_manager()->table_exists('company_users')) {
                            // Get default department if not specified
                            if (!$department_id) {
                                $default_dept = $DB->get_record('department', ['company' => $company_id, 'parent' => 0]);
                                $department_id = $default_dept ? $default_dept->id : 0;
                            }
                            
                            $company_user = new stdClass();
                            $company_user->userid = $userid;
                            $company_user->companyid = $company_id;
                            $company_user->departmentid = $department_id;
                            $company_user->managertype = 0; // Regular user
                            $company_user->suspended = 0;
                            $company_user->educator = 0;
                            $company_user->lastused = time();
                            
                            $DB->insert_record('company_users', $company_user);
                        }
                        
                        $results[] = [
                            'row' => $index + 1,
                            'status' => 'success',
                            'message' => "User '{$user_data['username']}' created successfully",
                            'userid' => $userid
                        ];
                        $success_count++;
                        
                    } catch (Exception $e) {
                        $results[] = [
                            'row' => $index + 1,
                            'status' => 'error',
                            'message' => $e->getMessage(),
                            'data' => $user_data
                        ];
                        $error_count++;
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'results' => $results,
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'total_count' => count($users_data)
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_companies':
            try {
                $companies = [];
                if ($DB->get_manager()->table_exists('company')) {
                    $companies = $DB->get_records('company', null, 'name ASC', 'id, name, shortname');
                } else {
                    // Fallback to course categories
                    $companies = $DB->get_records('course_categories', ['parent' => 0], 'name ASC', 'id, name, idnumber as shortname');
                }
                
                if (empty($companies)) {
                    $companies = [(object)['id' => 1, 'name' => 'Default Company', 'shortname' => 'DEFAULT']];
                }
                
                echo json_encode(['status' => 'success', 'companies' => array_values($companies)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_departments':
            $company_id = $_GET['company_id'] ?? null;
            if (!$company_id) {
                echo json_encode(['status' => 'error', 'message' => 'Company ID is required']);
                exit;
            }
            
            try {
                $departments = [];
                if ($DB->get_manager()->table_exists('department')) {
                    $departments = $DB->get_records('department', ['company' => $company_id], 'name ASC', 'id, name');
                }
                
                if (empty($departments)) {
                    $departments = [(object)['id' => 0, 'name' => 'Default Department']];
                }
                
                echo json_encode(['status' => 'success', 'departments' => array_values($departments)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'download_template':
            $template_data = [
                ['username', 'email', 'firstname', 'lastname', 'password'],
                ['john.doe', 'john.doe@example.com', 'John', 'Doe', 'password123'],
                ['jane.smith', 'jane.smith@example.com', 'Jane', 'Smith', 'password456']
            ];
            
            $filename = 'user_upload_template.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            foreach ($template_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
    }
}

function validateCsvData($csv_data) {
    global $DB;
    
    $validation_results = [];
    $errors = [];
    $warnings = [];
    
    foreach ($csv_data as $index => $row) {
        $row_errors = [];
        $row_warnings = [];
        
        // Validate required fields
        if (empty($row['username'])) {
            $row_errors[] = 'Username is required';
        } elseif (strlen($row['username']) < 3) {
            $row_errors[] = 'Username must be at least 3 characters';
        }
        
        if (empty($row['email'])) {
            $row_errors[] = 'Email is required';
        } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $row_errors[] = 'Invalid email format';
        }
        
        if (empty($row['firstname'])) {
            $row_errors[] = 'First name is required';
        }
        
        if (empty($row['lastname'])) {
            $row_errors[] = 'Last name is required';
        }
        
        if (empty($row['password'])) {
            $row_errors[] = 'Password is required';
        } elseif (strlen($row['password']) < 8) {
            $row_errors[] = 'Password must be at least 8 characters';
        }
        
        // Check for duplicates in CSV
        $duplicate_username = false;
        $duplicate_email = false;
        foreach ($csv_data as $check_index => $check_row) {
            if ($check_index !== $index) {
                if ($row['username'] === $check_row['username']) {
                    $duplicate_username = true;
                }
                if ($row['email'] === $check_row['email']) {
                    $duplicate_email = true;
                }
            }
        }
        
        if ($duplicate_username) {
            $row_errors[] = 'Duplicate username in CSV';
        }
        
        if ($duplicate_email) {
            $row_errors[] = 'Duplicate email in CSV';
        }
        
        // Check against database
        if (!empty($row['username']) && $DB->record_exists('user', ['username' => $row['username']])) {
            $row_errors[] = 'Username already exists in database';
        }
        
        if (!empty($row['email']) && $DB->record_exists('user', ['email' => $row['email']])) {
            $row_errors[] = 'Email already exists in database';
        }
        
        $validation_results[] = [
            'row' => $index + 1,
            'data' => $row,
            'errors' => $row_errors,
            'warnings' => $row_warnings,
            'valid' => empty($row_errors)
        ];
        
        if (!empty($row_errors)) {
            $errors = array_merge($errors, $row_errors);
        }
        if (!empty($row_warnings)) {
            $warnings = array_merge($warnings, $row_warnings);
        }
    }
    
    return [
        'results' => $validation_results,
        'total_errors' => count($errors),
        'total_warnings' => count($warnings),
        'valid_rows' => count(array_filter($validation_results, function($r) { return $r['valid']; }))
    ];
}

// Render the page
echo $OUTPUT->header();

$template_data = [
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->render_from_template('theme_remui_kids/upload_users', $template_data);

echo $OUTPUT->footer();
?>