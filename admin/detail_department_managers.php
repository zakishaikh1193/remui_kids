<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_department_managers.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Department Managers Details');
$PAGE->set_heading('Department Managers Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_managers':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'firstname';
            $order = $_GET['order'] ?? 'ASC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Get department managers from company_users table
                $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.lastaccess,
                               cu.managertype, cu.departmentid, cu.companyid,
                               d.name as department_name, c.name as company_name,
                               COUNT(cu2.userid) as employees_count
                        FROM {company_users} cu
                        JOIN {user} u ON cu.userid = u.id
                        LEFT JOIN {department} d ON cu.departmentid = d.id
                        LEFT JOIN {company} c ON cu.companyid = c.id
                        LEFT JOIN {company_users} cu2 ON cu.departmentid = cu2.departmentid 
                                                     AND cu2.managertype = 0 
                                                     AND cu2.suspended = 0
                        WHERE cu.managertype IN (1, 2) AND u.deleted = 0 AND cu.suspended = 0";
                
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR d.name LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " GROUP BY u.id, cu.id ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $managers = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT u.id) FROM {company_users} cu
                              JOIN {user} u ON cu.userid = u.id
                              WHERE cu.managertype IN (1, 2) AND u.deleted = 0 AND cu.suspended = 0";
                $count_params = [];
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = [$search_param, $search_param, $search_param, $search_param];
                }
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'managers' => array_values($managers),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_manager_stats':
            try {
                // Get real manager statistics
                $department_managers = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     WHERE cu.managertype = 2 AND u.deleted = 0 AND cu.suspended = 0"
                );
                
                $company_managers = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     WHERE cu.managertype = 1 AND u.deleted = 0 AND cu.suspended = 0"
                );
                
                $total_employees = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     WHERE cu.managertype = 0 AND u.deleted = 0 AND cu.suspended = 0"
                );
                
                $active_managers = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cu.userid) FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     JOIN {user_lastaccess} ul ON u.id = ul.userid
                     WHERE cu.managertype IN (1, 2) AND u.deleted = 0 AND cu.suspended = 0
                     AND ul.timeaccess > ?",
                    [time() - (30 * 24 * 60 * 60)]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'department_managers' => $department_managers,
                        'company_managers' => $company_managers,
                        'total_employees' => $total_employees,
                        'active_managers' => $active_managers
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Render the page
echo $OUTPUT->header();

$template_data = [
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->render_from_template('theme_remui_kids/detail_department_managers', $template_data);

echo $OUTPUT->footer();
?>
