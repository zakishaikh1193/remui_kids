<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_total_users.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Total Users Details');
$PAGE->set_heading('Total Users Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_users':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'firstname';
            $order = $_GET['order'] ?? 'ASC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.timecreated, u.lastaccess, u.suspended, u.deleted
                        FROM {user} u 
                        WHERE u.deleted = 0";
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $users = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) FROM {user} u WHERE u.deleted = 0";
                $count_params = [];
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = [$search_param, $search_param, $search_param, $search_param];
                }
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'users' => array_values($users),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_stats':
            try {
                $total_users = $DB->count_records('user', ['deleted' => 0]);
                $active_users = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [time() - (30 * 24 * 60 * 60)]
                );
                $suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
                $new_this_month = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND timecreated > ?",
                    [strtotime('first day of this month')]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'total_users' => $total_users,
                        'active_users' => $active_users,
                        'suspended_users' => $suspended_users,
                        'new_this_month' => $new_this_month
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

echo $OUTPUT->render_from_template('theme_remui_kids/detail_total_users', $template_data);

echo $OUTPUT->footer();
?>
