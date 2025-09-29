<?php
require_once('../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/theme/remui_kids/admin/assign_school.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Assign Users to School');
$PAGE->set_heading('Assign Users to School');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'test':
            echo json_encode(['status' => 'success', 'message' => 'Test endpoint working']);
            exit;
        case 'get_school_users':
            $school_id = $_GET['school_id'] ?? null;
            $search = $_GET['search'] ?? '';
            $filter = $_GET['filter'] ?? '';
            
            if (!$school_id) {
                echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
                exit;
            }
            
            try {
                $school_users = [];
                
                // Get users assigned to this company/school using company_users table
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.lastaccess, cu.managertype, cu.departmentid
                        FROM {user} u
                        JOIN {company_users} cu ON u.id = cu.userid
                        WHERE cu.companyid = ? AND u.deleted = 0 AND u.suspended = 0 AND cu.suspended = 0";
                $params = [$school_id];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.firstname, u.lastname LIMIT 100";
                $school_users = $DB->get_records_sql($sql, $params);
                
                echo json_encode([
                    'status' => 'success',
                    'users' => array_values($school_users)
                ]);
            } catch (Exception $e) {
                error_log("Error in get_school_users: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_available_users':
            $school_id = $_GET['school_id'] ?? null;
            $search = $_GET['search'] ?? '';
            $filter = $_GET['filter'] ?? '';
            
            if (!$school_id) {
                echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
                exit;
            }
            
            try {
                $available_users = [];
                
                // Get users NOT assigned to this company/school using company_users table
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.lastaccess
                        FROM {user} u
                        WHERE u.deleted = 0 AND u.suspended = 0
                        AND u.id NOT IN (
                            SELECT cu.userid FROM {company_users} cu 
                            WHERE cu.companyid = ? AND cu.suspended = 0
                        )";
                $params = [$school_id];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.firstname, u.lastname LIMIT 100";
                $available_users = $DB->get_records_sql($sql, $params);
                
                echo json_encode([
                    'status' => 'success',
                    'users' => array_values($available_users)
                ]);
            } catch (Exception $e) {
                error_log("Error in get_available_users: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_schools':
            try {
                // Check if company table exists
                if (!$DB->get_manager()->table_exists('company')) {
                    // If no company table, use course categories as schools
                    $schools = $DB->get_records('course_categories', ['parent' => 0], 'name ASC', 'id, name, idnumber as code');
                    foreach ($schools as $school) {
                        $school->address = '';
                        $school->phone = '';
                        $school->email = '';
                    }
                } else {
                    // Get schools from mdl_company table
                    $schools = $DB->get_records('company', null, 'name ASC', 'id, name, shortname, address, city, region, postcode, country');
                    // Add code field from shortname for compatibility
                    foreach ($schools as $school) {
                        $school->code = $school->shortname;
                        $school->phone = '';
                        $school->email = '';
                    }
                }
                
                // If no companies exist, create a default "General" school
                if (empty($schools)) {
                    $schools = [
                        (object)[
                            'id' => 1,
                            'name' => 'General School',
                            'code' => 'GEN001',
                            'address' => '',
                            'phone' => '',
                            'email' => ''
                        ]
                    ];
                }
                
                echo json_encode(['status' => 'success', 'schools' => array_values($schools)]);
            } catch (Exception $e) {
                error_log("Error in get_schools: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_school_details':
            $school_id = $_GET['school_id'] ?? null;
            
            if (!$school_id) {
                echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
                exit;
            }
            
            try {
                // Get school details from mdl_company table or course_categories
                if ($DB->get_manager()->table_exists('company')) {
                    $school = $DB->get_record('company', ['id' => $school_id], 'id, name, shortname, address, city, region, postcode, country');
                    if ($school) {
                        $school->code = $school->shortname;
                        $school->phone = '';
                        $school->email = '';
                    }
                } else {
                    $school = $DB->get_record('course_categories', ['id' => $school_id], 'id, name, idnumber as code');
                    if ($school) {
                        $school->address = '';
                        $school->phone = '';
                        $school->email = '';
                    }
                }
                
                if (!$school) {
                    echo json_encode(['status' => 'error', 'message' => 'School not found']);
                    exit;
                }
                
                echo json_encode(['status' => 'success', 'school' => $school]);
            } catch (Exception $e) {
                error_log("Error in get_school_details: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'assign_users':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $user_ids = $data['user_ids'] ?? [];
                $school_id = $data['school_id'] ?? null;
                $action = $data['action'] ?? 'add';
                
                if (empty($user_ids) || !$school_id) {
                    throw new Exception("User IDs and School ID are required");
                }
                
                $processed_count = 0;
                $errors = [];
                
                foreach ($user_ids as $user_id) {
                    // Check if user exists
                    if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
                        $errors[] = "User ID {$user_id} not found";
                        continue;
                    }
                    
                    if ($action === 'add') {
                        // Add user to company/school using company_users table
                        // Get the top-level department for this company
                        $top_department = $DB->get_record('department', ['company' => $school_id, 'parent' => 0]);
                        if (!$top_department) {
                            $errors[] = "No top-level department found for company {$school_id}";
                            continue;
                        }
                        
                        // Check if already assigned
                        if (!$DB->record_exists('company_users', ['userid' => $user_id, 'companyid' => $school_id, 'departmentid' => $top_department->id])) {
                            $assignment = new stdClass();
                            $assignment->userid = $user_id;
                            $assignment->companyid = $school_id;
                            $assignment->departmentid = $top_department->id;
                            $assignment->managertype = 0; // Regular user (0 - User, 1 - Company manager, 2 - Department manager)
                            $assignment->suspended = 0;
                            $assignment->educator = 0;
                            $assignment->lastused = time();
                            
                            if ($DB->insert_record('company_users', $assignment)) {
                                $processed_count++;
                            } else {
                                $errors[] = "Failed to assign user {$user_id} to school";
                            }
                        }
                    } else {
                        // Remove user from company/school
                        if ($DB->delete_records('company_users', ['userid' => $user_id, 'companyid' => $school_id])) {
                            $processed_count++;
                        } else {
                            $errors[] = "Failed to remove user {$user_id} from school";
                        }
                    }
                }
                
                $action_text = $action === 'add' ? 'added to' : 'removed from';
                $message = "Successfully {$action_text} {$processed_count} users to/from school";
                
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'processed_count' => $processed_count,
                    'errors' => $errors
                ]);
                
            } catch (Exception $e) {
                error_log("Error in assign_users: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_user_schools':
            $user_id = $_GET['user_id'] ?? null;
            
            if (!$user_id) {
                echo json_encode(['status' => 'error', 'message' => 'User ID required']);
                exit;
            }
            
            // For demo purposes, return empty array since we don't have user_school table
            // In a real implementation, you would query the user_school table
            echo json_encode([
                'status' => 'success',
                'schools' => []
            ]);
            exit;
    }
}

// Get template data
$template_data = [
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ],
    'user' => [
        'firstname' => $USER->firstname,
        'lastname' => $USER->lastname
    ]
];

// Render template
echo $OUTPUT->render_from_template('theme_remui_kids/assign_school', $template_data);
?>
