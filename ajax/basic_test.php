<?php
header('Content-Type: application/json');

try {
    $config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php';
    require_once($config_path);
    
    global $DB;
    
    // Simple test query
    $count = $DB->count_records('course_categories', ['visible' => 1]);
    
    echo json_encode([
        'status' => 'success',
        'total_categories' => $count,
        'message' => 'Basic test working'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>


