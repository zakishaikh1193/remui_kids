<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_pending_approvals.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Pending Approvals Details');
$PAGE->set_heading('Pending Approvals Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_pending_approvals':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'created_at';
            $order = $_GET['order'] ?? 'DESC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                // Get pending training event approvals from trainingevent_users table
                $sql = "SELECT te.id, te.name as title, te.startdatetime, te.enddatetime, 
                               te.coursecapacity, te.approvaltype, teu.id as approval_id,
                               teu.userid, teu.approved, teu.waitlisted, teu.booking_notes,
                               u.firstname, u.lastname, u.email, u.username,
                               c.fullname as course_name
                        FROM {trainingevent} te
                        JOIN {trainingevent_users} teu ON te.id = teu.trainingeventid
                        JOIN {user} u ON teu.userid = u.id
                        JOIN {course} c ON te.course = c.id
                        WHERE teu.approved = 0 AND u.deleted = 0";
                
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " AND (te.name LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY te.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $events = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) FROM {trainingevent} te
                              JOIN {trainingevent_users} teu ON te.id = teu.trainingeventid
                              JOIN {user} u ON teu.userid = u.id
                              WHERE teu.approved = 0 AND u.deleted = 0";
                $count_params = [];
                if (!empty($search)) {
                    $count_sql .= " AND (te.name LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
                    $count_params = [$search_param, $search_param, $search_param, $search_param];
                }
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'events' => array_values($events),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_approval_stats':
            try {
                // Get real approval statistics
                $pending_approvals = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {trainingevent_users} WHERE approved = 0"
                );
                
                $approved_this_week = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {trainingevent_users} 
                     WHERE approved = 1 AND timecreated > ?",
                    [strtotime('1 week ago')]
                );
                
                $rejected_this_week = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {trainingevent_users} 
                     WHERE approved = 2 AND timecreated > ?",
                    [strtotime('1 week ago')]
                );
                
                $total_events = $DB->count_records('trainingevent');
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'pending_approvals' => $pending_approvals,
                        'approved_this_week' => $approved_this_week,
                        'rejected_this_week' => $rejected_this_week,
                        'total_events' => $total_events
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'approve_event':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $approval_id = $data['approval_id'] ?? null;
                $action = $data['action'] ?? 'approve'; // approve or reject
                
                if (!$approval_id) {
                    throw new Exception('Approval ID is required');
                }
                
                $approval_status = ($action === 'approve') ? 1 : 2; // 1 = approved, 2 = rejected
                
                $result = $DB->set_field('trainingevent_users', 'approved', $approval_status, ['id' => $approval_id]);
                
                if ($result) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Event ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update approval status');
                }
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

echo $OUTPUT->render_from_template('theme_remui_kids/detail_pending_approvals', $template_data);

echo $OUTPUT->footer();
?>
