<?php
require_once('../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/theme/remui_kids/admin/bulk_download.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Bulk Download');
$PAGE->set_heading('Bulk Download');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_user_stats':
            $total_users = $DB->count_records('user', ['deleted' => 0]);
            $active_users = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                 JOIN {user_lastaccess} ul ON u.id = ul.userid 
                 WHERE u.deleted = 0 AND ul.timeaccess > ?",
                [time() - (30 * 24 * 60 * 60)]
            );
            $suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
            $students = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {role} r ON ra.roleid = r.id 
                 WHERE u.deleted = 0 AND r.shortname = 'trainee'"
            );
            $teachers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {role} r ON ra.roleid = r.id 
                 WHERE u.deleted = 0 AND r.shortname = 'teachers'"
            );
            
            echo json_encode([
                'status' => 'success',
                'stats' => [
                    'total_users' => $total_users,
                    'active_users' => $active_users,
                    'suspended_users' => $suspended_users,
                    'students' => $students,
                    'teachers' => $teachers
                ]
            ]);
            exit;
            
        case 'export_users':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $format = $data['format'] ?? 'csv';
                $filters = $data['filters'] ?? [];
                $fields = $data['fields'] ?? ['username', 'email', 'firstname', 'lastname'];
                
                // Build query based on filters
                $where_conditions = ['u.deleted = 0'];
                $params = [];
                
                if (!empty($filters['role'])) {
                    $where_conditions[] = "r.shortname = ?";
                    $params[] = $filters['role'];
                }
                
                if (!empty($filters['status'])) {
                    if ($filters['status'] === 'active') {
                        $where_conditions[] = "ul.timeaccess > ?";
                        $params[] = time() - (30 * 24 * 60 * 60);
                    } elseif ($filters['status'] === 'suspended') {
                        $where_conditions[] = "u.suspended = 1";
                    }
                }
                
                if (!empty($filters['date_from'])) {
                    $where_conditions[] = "u.timecreated >= ?";
                    $params[] = strtotime($filters['date_from']);
                }
                
                if (!empty($filters['date_to'])) {
                    $where_conditions[] = "u.timecreated <= ?";
                    $params[] = strtotime($filters['date_to']);
                }
                
                $where_clause = implode(' AND ', $where_conditions);
                
                // Build field selection
                $field_mapping = [
                    'username' => 'u.username',
                    'email' => 'u.email',
                    'firstname' => 'u.firstname',
                    'lastname' => 'u.lastname',
                    'idnumber' => 'u.idnumber',
                    'phone' => 'u.phone1',
                    'city' => 'u.city',
                    'country' => 'u.country',
                    'timecreated' => 'u.timecreated',
                    'lastaccess' => 'u.lastaccess',
                    'suspended' => 'u.suspended',
                    'role' => 'r.shortname'
                ];
                
                $selected_fields = [];
                foreach ($fields as $field) {
                    if (isset($field_mapping[$field])) {
                        $selected_fields[] = $field_mapping[$field] . ' AS ' . $field;
                    }
                }
                
                if (empty($selected_fields)) {
                    $selected_fields = ['u.username', 'u.email', 'u.firstname', 'u.lastname'];
                }
                
                $field_list = implode(', ', $selected_fields);
                
                // Build the query
                $sql = "SELECT {$field_list} FROM {user} u";
                
                if (!empty($filters['role'])) {
                    $sql .= " JOIN {role_assignments} ra ON u.id = ra.userid";
                    $sql .= " JOIN {role} r ON ra.roleid = r.id";
                }
                
                if (!empty($filters['status']) && $filters['status'] === 'active') {
                    $sql .= " JOIN {user_lastaccess} ul ON u.id = ul.userid";
                }
                
                $sql .= " WHERE {$where_clause} ORDER BY u.firstname, u.lastname";
                
                $users = $DB->get_records_sql($sql, $params);
                
                if ($format === 'csv') {
                    exportToCsv($users, $fields);
                } elseif ($format === 'excel') {
                    exportToExcel($users, $fields);
                } elseif ($format === 'json') {
                    exportToJson($users, $fields);
                } else {
                    throw new Exception('Unsupported format');
                }
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_export_history':
            // For demo purposes, return sample export history
            // In a real implementation, you would query the export_history table
            $history = [
                (object)[
                    'id' => 1,
                    'userid' => $USER->id,
                    'filename' => 'users_export_2024-01-15_14-30-25.csv',
                    'format' => 'csv',
                    'record_count' => 150,
                    'created_date' => time() - (2 * 24 * 60 * 60)
                ],
                (object)[
                    'id' => 2,
                    'userid' => $USER->id,
                    'filename' => 'users_export_2024-01-14_09-15-10.xlsx',
                    'format' => 'excel',
                    'record_count' => 89,
                    'created_date' => time() - (3 * 24 * 60 * 60)
                ],
                (object)[
                    'id' => 3,
                    'userid' => $USER->id,
                    'filename' => 'users_export_2024-01-13_16-45-30.json',
                    'format' => 'json',
                    'record_count' => 203,
                    'created_date' => time() - (4 * 24 * 60 * 60)
                ]
            ];
            
            echo json_encode([
                'status' => 'success',
                'history' => array_values($history)
            ]);
            exit;
    }
}

function exportToCsv($users, $fields) {
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $fields);
    
    // Write data
    foreach ($users as $user) {
        $row = [];
        foreach ($fields as $field) {
            $row[] = $user->$field ?? '';
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    
    // Log export
    logExport($filename, 'csv', count($users));
}

function exportToExcel($users, $fields) {
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Simple Excel-like CSV with Excel headers
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $fields);
    
    // Write data
    foreach ($users as $user) {
        $row = [];
        foreach ($fields as $field) {
            $row[] = $user->$field ?? '';
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    
    // Log export
    logExport($filename, 'excel', count($users));
}

function exportToJson($users, $fields) {
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $export_data = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_records' => count($users),
        'fields' => $fields,
        'data' => array_values($users)
    ];
    
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    
    // Log export
    logExport($filename, 'json', count($users));
}

function logExport($filename, $format, $record_count) {
    // For demo purposes, just log to error log
    // In a real implementation, you would insert into export_history table
    error_log("Export logged: {$filename} ({$format}) - {$record_count} records");
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
echo $OUTPUT->render_from_template('theme_remui_kids/bulk_download', $template_data);
?>
