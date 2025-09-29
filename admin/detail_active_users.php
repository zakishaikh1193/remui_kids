<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_active_users.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Active Users Details');
$PAGE->set_heading('Active Users Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_active_users':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'lastaccess';
            $order = $_GET['order'] ?? 'DESC';
            $days = $_GET['days'] ?? 30;
            
            $offset = ($page - 1) * $per_page;
            $time_threshold = time() - ($days * 24 * 60 * 60);
            
            try {
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.timecreated, ul.timeaccess, u.suspended
                        FROM {user} u 
                        JOIN {user_lastaccess} ul ON u.id = ul.userid 
                        WHERE u.deleted = 0 AND ul.timeaccess > ?";
                $params = [$time_threshold];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $users = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                             JOIN {user_lastaccess} ul ON u.id = ul.userid 
                             WHERE u.deleted = 0 AND ul.timeaccess > ?";
                $count_params = [$time_threshold];
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
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
            
        case 'get_activity_stats':
            try {
                $days_30 = time() - (30 * 24 * 60 * 60);
                $days_7 = time() - (7 * 24 * 60 * 60);
                $days_1 = time() - (24 * 60 * 60);
                
                $active_30_days = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [$days_30]
                );
                
                $active_7_days = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [$days_7]
                );
                
                $active_1_day = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [$days_1]
                );
                
                $online_now = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [time() - 300] // 5 minutes
                );
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'active_30_days' => $active_30_days,
                        'active_7_days' => $active_7_days,
                        'active_1_day' => $active_1_day,
                        'online_now' => $online_now
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

echo $OUTPUT->render_from_template('theme_remui_kids/detail_active_users', $template_data);

echo $OUTPUT->footer();
?>
