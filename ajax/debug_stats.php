<?php
/**
 * Debug endpoint to identify the 500 error
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

try {
    echo json_encode(['step' => '1', 'message' => 'Starting debug...']);
    
    require_once('../../../config.php');
    echo json_encode(['step' => '2', 'message' => 'Config loaded']);
    
    // Check if user is logged in
    if (!isloggedin()) {
        echo json_encode(['step' => '3', 'message' => 'User not logged in']);
        exit;
    }
    echo json_encode(['step' => '4', 'message' => 'User is logged in']);
    
    // Check admin capability
    $context = context_system::instance();
    if (!has_capability('moodle/site:config', $context)) {
        echo json_encode(['step' => '5', 'message' => 'User is not admin']);
        exit;
    }
    echo json_encode(['step' => '6', 'message' => 'User is admin']);
    
    // Try to include the theme library
    $lib_path = $CFG->dirroot . '/theme/remui_kids/lib.php';
    if (!file_exists($lib_path)) {
        echo json_encode(['step' => '7', 'message' => 'Theme lib file not found: ' . $lib_path]);
        exit;
    }
    echo json_encode(['step' => '8', 'message' => 'Theme lib file exists']);
    
    require_once($lib_path);
    echo json_encode(['step' => '9', 'message' => 'Theme lib loaded']);
    
    // Try to call the function
    if (!function_exists('theme_remui_kids_get_admin_dashboard_stats')) {
        echo json_encode(['step' => '10', 'message' => 'Function not found']);
        exit;
    }
    echo json_encode(['step' => '11', 'message' => 'Function exists']);
    
    $stats = theme_remui_kids_get_admin_dashboard_stats();
    echo json_encode(['step' => '12', 'message' => 'Stats retrieved', 'stats' => $stats]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Exception caught',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'error' => 'PHP Error caught',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>



