<?php
/**
 * Users Management Dashboard
 * Comprehensive user administration and management tools
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
        case 'get_dashboard_stats':
            try {
                // Get total users
                $total_users = $DB->count_records('user', ['deleted' => 0]);
                
                // Get active users (logged in within last 30 days)
                $active_users = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [time() - (30 * 24 * 60 * 60)]
                );
                
                // Get pending approvals (mock data for now)
                $pending_approvals = 0;
                
                // Get department managers from company_users table
                $department_managers = 0;
                $count_sql = "SELECT COUNT(DISTINCT u.id) FROM {company_users} cu
                JOIN {user} u ON cu.userid = u.id
                WHERE cu.managertype IN (1, 2) AND u.deleted = 0 AND cu.suspended = 0"; 
                $department_managers = $DB->count_records_sql($count_sql);
                // Get recent uploads (users created this month)
                $recent_uploads = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE timecreated > ? AND deleted = 0",
                    [strtotime('first day of this month')]
                );
                
                // Get recent user activity
                $recent_activity = $DB->get_records_sql(
                    "SELECT u.id, u.firstname, u.lastname, u.timecreated,
                            CASE 
                                WHEN u.timecreated > ? THEN 'User created'
                                ELSE 'User updated'
                            END as activity_type
                     FROM {user} u 
                     WHERE u.deleted = 0 AND u.id > 1
                     ORDER BY u.timecreated DESC 
                     LIMIT 10",
                    [strtotime('-7 days')]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'total_users' => $total_users,
                    'active_users' => $active_users,
                    'pending_approvals' => $pending_approvals,
                    'department_managers' => $department_managers,
                    'recent_uploads' => $recent_uploads,
                    'recent_activity' => array_values($recent_activity),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'create_user':
            // Handle user creation
            echo json_encode(['status' => 'success', 'message' => 'User creation functionality will be implemented']);
            exit;
            
        case 'bulk_upload':
            // Handle bulk upload
            echo json_encode(['status' => 'success', 'message' => 'Bulk upload functionality will be implemented']);
            exit;
            
        case 'export_users':
            // Handle user export
            echo json_encode(['status' => 'success', 'message' => 'Export functionality will be implemented']);
            exit;
            
        case 'approve_events':
            // Handle event approval
            echo json_encode(['status' => 'success', 'message' => 'Event approval functionality will be implemented']);
            exit;
    }
}

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/users_management_dashboard.php');
$PAGE->set_title('Users Management Dashboard');
$PAGE->set_heading('Users Management Dashboard');

// Get initial dashboard statistics
$total_users = $DB->count_records('user', ['deleted' => 0]);
$active_users = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
     JOIN {user_lastaccess} ul ON u.id = ul.userid 
     WHERE u.deleted = 0 AND ul.timeaccess > ?",
    [time() - (30 * 24 * 60 * 60)]
);
$pending_approvals = 0;
$department_managers = 0;
if ($DB->get_manager()->table_exists('company_users')) {
    $department_managers = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cu.userid) 
         FROM {company_users} cu
         JOIN {user} u ON cu.userid = u.id
         WHERE cu.managertype = 2 AND u.deleted = 0 AND cu.suspended = 0"
    );
}
$recent_uploads = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {user} WHERE timecreated > ? AND deleted = 0",
    [strtotime('first day of this month')]
);

// Get recent activity
$recent_activity = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, u.timecreated,
            CASE 
                WHEN u.timecreated > ? THEN 'User created'
                ELSE 'User updated'
            END as activity_type
     FROM {user} u 
     WHERE u.deleted = 0 AND u.id > 1
     ORDER BY u.timecreated DESC 
     LIMIT 10",
    [strtotime('-7 days')]
);

// Prepare template data
$templatecontext = [
    'total_users' => $total_users,
    'active_users' => $active_users,
    'pending_approvals' => $pending_approvals,
    'department_managers' => $department_managers,
    'recent_uploads' => $recent_uploads,
    'recent_activity' => array_values($recent_activity),
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_remui_kids/users_management_dashboard', $templatecontext);
echo $OUTPUT->footer();
?>
