<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_recent_uploads.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Recent Uploads Details');
$PAGE->set_heading('Recent Uploads Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_uploads':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'timecreated';
            $order = $_GET['order'] ?? 'DESC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Get recent user uploads from user table (users created in last 30 days)
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.timecreated,
                               u.timecreated as upload_date, u.auth, u.confirmed,
                               'CSV Upload' as upload_method, 'Completed' as status,
                               '1' as users_count, 'N/A' as file_size
                        FROM {user} u
                        WHERE u.deleted = 0 AND u.timecreated > ?";
                
                $params = [strtotime('-30 days')];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $uploads = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) FROM {user} u WHERE u.deleted = 0 AND u.timecreated > ?";
                $count_params = [strtotime('-30 days')];
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
                }
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                // Debug: Log the results
                error_log("Uploads query result: " . count($uploads) . " records found");
                error_log("Total count: " . $total_count);
                
                echo json_encode([
                    'status' => 'success',
                    'uploads' => array_values($uploads),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                error_log("Uploads query error: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_upload_stats':
            try {
                // Get real upload statistics
                $uploads_this_month = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND timecreated > ?",
                    [strtotime('first day of this month')]
                );
                
                $uploads_this_week = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND timecreated > ?",
                    [strtotime('1 week ago')]
                );
                
                $uploads_today = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND timecreated > ?",
                    [strtotime('today')]
                );
                
                $total_users = $DB->count_records('user', ['deleted' => 0]);
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'uploads_this_month' => $uploads_this_month,
                        'uploads_this_week' => $uploads_this_week,
                        'uploads_today' => $uploads_today,
                        'total_users' => $total_users
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

echo $OUTPUT->render_from_template('theme_remui_kids/detail_recent_uploads', $template_data);

echo $OUTPUT->footer();
?>
