<?php
/**
 * Fixed stats endpoint with correct path
 */

// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON first
header('Content-Type: application/json');

try {
    // Get the correct path to config.php
    $config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php';
    
    if (!file_exists($config_path)) {
        echo json_encode(['error' => 'Config file not found: ' . $config_path, 'status' => 'error']);
        exit;
    }
    
    require_once($config_path);
    
    // Check if user is logged in and has admin privileges
    require_login();
    $context = context_system::instance();
    if (!has_capability('moodle/site:config', $context)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied', 'status' => 'error']);
        exit;
    }
    
    global $DB;
    
    // Simple school count query
    $totalschools = $DB->count_records_sql(
        "SELECT COUNT(*) 
         FROM {course_categories} 
         WHERE visible = 1 
         AND id > 1 
         AND parent = 0",
        []
    );
    
    // Simple courses count
    $totalcourses = $DB->count_records_sql(
        "SELECT COUNT(*) 
         FROM {course} 
         WHERE visible = 1 
         AND id > 1",
        []
    );
    
    // Students count - using trainee role for consistency with enrollment system
    $traineerole = $DB->get_record('role', ['shortname' => 'trainee']);
    $totalstudents = 0;
    if ($traineerole) {
        $totalstudents = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {role} r ON ra.roleid = r.id 
             WHERE r.shortname = 'trainee' AND u.deleted = 0 AND u.suspended = 0"
        );
    }
    
    $stats = [
        'total_schools' => $totalschools,
        'total_courses' => $totalcourses,
        'total_students' => $totalstudents,
        'avg_course_rating' => 0,
        'last_updated' => time(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'success'
    ];
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch statistics',
        'message' => $e->getMessage(),
        'status' => 'error'
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error',
        'message' => $e->getMessage(),
        'status' => 'error'
    ]);
}
?>

