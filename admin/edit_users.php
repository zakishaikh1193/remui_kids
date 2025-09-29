<?php
/**
 * Edit Users Page
 * Comprehensive user editing with search, filters, and bulk operations
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
        case 'get_users':
            try {
                $page = intval($_GET['page'] ?? 1);
                $per_page = intval($_GET['per_page'] ?? 20);
                $search = trim($_GET['search'] ?? '');
                $role_filter = $_GET['role'] ?? '';
                $status_filter = $_GET['status'] ?? '';
                $sort_by = $_GET['sort'] ?? 'firstname';
                $sort_order = $_GET['order'] ?? 'ASC';
                $offset = ($page - 1) * $per_page;
                
                $where_conditions = ["u.deleted = 0"];
                $params = [];
                
                if ($search) {
                    $where_conditions[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                if ($role_filter) {
                    $where_conditions[] = "r.shortname = ?";
                    $params[] = $role_filter;
                }
                
                if ($status_filter === 'active') {
                    $where_conditions[] = "u.suspended = 0";
                } elseif ($status_filter === 'suspended') {
                    $where_conditions[] = "u.suspended = 1";
                }
                
                $where_clause = implode(' AND ', $where_conditions);
                
                // Get users with role information
                $users = $DB->get_records_sql(
                    "SELECT u.*, 
                            r.shortname as role_shortname,
                            r.name as role_name,
                            MAX(ul.timeaccess) as last_access,
                            COUNT(ue.id) as enrollment_count
                     FROM {user} u 
                     LEFT JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid = ?
                     LEFT JOIN {role} r ON ra.roleid = r.id
                     LEFT JOIN {user_lastaccess} ul ON u.id = ul.userid
                     LEFT JOIN {user_enrolments} ue ON u.id = ue.userid
                     WHERE {$where_clause}
                     GROUP BY u.id, r.shortname, r.name
                     ORDER BY u.{$sort_by} {$sort_order}
                     LIMIT {$per_page} OFFSET {$offset}",
                    array_merge([$context->id], $params)
                );
                
                // Get total count
                $total_count = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) 
                     FROM {user} u 
                     LEFT JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid = ?
                     LEFT JOIN {role} r ON ra.roleid = r.id
                     WHERE {$where_clause}",
                    array_merge([$context->id], $params)
                );
                
                // Format users data
                $formatted_users = [];
                foreach ($users as $user) {
                    $formatted_users[] = [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'fullname' => fullname($user),
                        'role' => $user->role_name ?? 'No Role',
                        'role_shortname' => $user->role_shortname ?? '',
                        'suspended' => $user->suspended,
                        'last_access' => $user->last_access ? date('M j, Y g:i A', $user->last_access) : 'Never',
                        'enrollment_count' => $user->enrollment_count,
                        'timecreated' => date('M j, Y', $user->timecreated)
                    ];
                }
                
                echo json_encode([
                    'status' => 'success',
                    'users' => $formatted_users,
                    'total' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_user':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $userid = intval($data['userid']);
                
                if (!$userid) {
                    throw new Exception("Invalid user ID");
                }
                
                $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
                if (!$user) {
                    throw new Exception("User not found");
                }
                
                // Update user fields
                $update_fields = ['firstname', 'lastname', 'email', 'suspended'];
                foreach ($update_fields as $field) {
                    if (isset($data[$field])) {
                        $user->$field = $data[$field];
                    }
                }
                
                $user->timemodified = time();
                
                if (!$DB->update_record('user', $user)) {
                    throw new Exception("Failed to update user");
                }
                
                // Update role if specified
                if (isset($data['role'])) {
                    // Remove existing role assignments
                    $DB->delete_records('role_assignments', ['userid' => $userid, 'contextid' => $context->id]);
                    
                    // Assign new role
                    if ($data['role']) {
                        $role = $DB->get_record('role', ['shortname' => $data['role']]);
                        if ($role) {
                            role_assign($role->id, $userid, $context->id);
                        }
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User updated successfully'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'bulk_update':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $user_ids = $data['user_ids'] ?? [];
                $action = $data['action'] ?? '';
                
                if (empty($user_ids) || !$action) {
                    throw new Exception("Invalid bulk operation");
                }
                
                $updated_count = 0;
                
                foreach ($user_ids as $userid) {
                    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
                    if (!$user) continue;
                    
                    switch ($action) {
                        case 'suspend':
                            $user->suspended = 1;
                            break;
                        case 'unsuspend':
                            $user->suspended = 0;
                            break;
                        case 'delete':
                            $user->deleted = 1;
                            break;
                    }
                    
                    $user->timemodified = time();
                    if ($DB->update_record('user', $user)) {
                        $updated_count++;
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => "Successfully updated {$updated_count} users",
                    'updated_count' => $updated_count
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_user_details':
            try {
                $userid = intval($_GET['userid']);
                $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
                
                if (!$user) {
                    throw new Exception("User not found");
                }
                
                // Get user role
                $role = $DB->get_record_sql(
                    "SELECT r.* FROM {role} r 
                     JOIN {role_assignments} ra ON r.id = ra.roleid 
                     WHERE ra.userid = ? AND ra.contextid = ?",
                    [$userid, $context->id]
                );
                
                // Get user enrollments
                $enrollments = $DB->get_records_sql(
                    "SELECT c.id, c.fullname, c.shortname, ue.timecreated
                     FROM {course} c
                     JOIN {enrol} e ON c.id = e.courseid
                     JOIN {user_enrolments} ue ON e.id = ue.enrolid
                     WHERE ue.userid = ? AND c.visible = 1
                     ORDER BY ue.timecreated DESC
                     LIMIT 10",
                    [$userid]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'suspended' => $user->suspended,
                        'timecreated' => $user->timecreated,
                        'lastaccess' => $user->lastaccess,
                        'role' => $role ? $role->shortname : '',
                        'role_name' => $role ? $role->name : 'No Role',
                        'enrollments' => array_values($enrollments)
                    ]
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
    }
}

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/edit_users.php');
$PAGE->set_title('Edit Users');
$PAGE->set_heading('Edit Users');

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
echo $OUTPUT->render_from_template('theme_remui_kids/edit_users', $templatecontext);
echo $OUTPUT->footer();
?>
