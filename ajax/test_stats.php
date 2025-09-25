<?php
/**
 * Test endpoint to debug AJAX issues
 */

// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON first
header('Content-Type: application/json');

try {
    require_once('../../../../config.php');
    
    // Simple test response
    echo json_encode([
        'status' => 'success',
        'message' => 'AJAX endpoint is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_logged_in' => isloggedin(),
        'is_admin' => is_siteadmin()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>


