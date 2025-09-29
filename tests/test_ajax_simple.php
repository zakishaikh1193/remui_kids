<?php
// Simple test to verify AJAX endpoint
header('Content-Type: application/json');

try {
    require_once('../../../config.php');
    global $DB;
    
    // Get course count
    $courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    echo json_encode([
        'status' => 'success',
        'total_courses' => $courses,
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'AJAX endpoint working correctly'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>



