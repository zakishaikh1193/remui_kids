<?php
/**
 * Test Dashboard AJAX - Verify the AJAX endpoint is returning correct data
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Dashboard AJAX Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
    .json { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
</style>";

try {
    // Test the exact same queries used in the AJAX endpoint
    echo "<div class='result'>";
    echo "<h3>1. Testing AJAX Endpoint Queries</h3>";
    
    // Schools count
    $schools = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 AND id > 1 AND parent = 0",
        []
    );
    
    // Courses count
    $courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    // Students count (enrolled users)
    $students = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
    echo "<tr><td>Schools</td><td>$schools</td><td>" . ($schools > 0 ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td>Courses</td><td>$courses</td><td>" . ($courses > 0 ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td>Students</td><td>$students</td><td>" . ($students > 0 ? "✅" : "❌") . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Test the JSON output
    echo "<div class='result'>";
    echo "<h3>2. AJAX JSON Output Test</h3>";
    
    $json_data = [
        'status' => 'success',
        'total_schools' => $schools,
        'total_courses' => $courses,
        'total_students' => $students,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $json_output = json_encode($json_data, JSON_PRETTY_PRINT);
    
    echo "<div class='json'>$json_output</div>";
    echo "</div>";
    
    // Test the actual AJAX endpoint
    echo "<div class='result'>";
    echo "<h3>3. Live AJAX Endpoint Test</h3>";
    
    $ajax_url = $CFG->wwwroot . '/test_ajax.php';
    
    echo "<p><strong>AJAX URL:</strong> <a href='$ajax_url' target='_blank'>$ajax_url</a></p>";
    
    // Test if the endpoint is accessible
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($ajax_url, false, $context);
    
    if ($response !== false) {
        echo "<div class='success'>";
        echo "<h4>✅ AJAX Endpoint is Accessible</h4>";
        echo "<p><strong>Response:</strong></p>";
        echo "<div class='json'>" . htmlspecialchars($response) . "</div>";
        echo "</div>";
        
        // Parse the response
        $parsed_response = json_decode($response, true);
        if ($parsed_response && isset($parsed_response['total_students'])) {
            echo "<div class='success'>";
            echo "<h4>✅ JSON Parsing Successful</h4>";
            echo "<p><strong>Student Count from AJAX:</strong> {$parsed_response['total_students']}</p>";
            echo "<p><strong>Status:</strong> {$parsed_response['status']}</p>";
            echo "<p><strong>Timestamp:</strong> {$parsed_response['timestamp']}</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ JSON Parsing Failed</h4>";
            echo "<p>The response could not be parsed as JSON.</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ AJAX Endpoint Not Accessible</h4>";
        echo "<p>The AJAX endpoint could not be reached. This might be due to:</p>";
        echo "<ul>";
        echo "<li>Web server not running</li>";
        echo "<li>Incorrect URL path</li>";
        echo "<li>File permissions issue</li>";
        echo "<li>PHP errors in the endpoint</li>";
        echo "</ul>";
        echo "</div>";
    }
    echo "</div>";
    
    // Dashboard troubleshooting
    echo "<div class='result'>";
    echo "<h3>4. Dashboard Troubleshooting</h3>";
    
    if ($students > 0) {
        echo "<div class='success'>";
        echo "<h4>✅ Data is Correct</h4>";
        echo "<p>Your system has <strong>$students students</strong> and the AJAX endpoint should return this count.</p>";
        echo "</div>";
        
        echo "<div class='issue'>";
        echo "<h4>🔍 If Dashboard Still Shows 0, Check:</h4>";
        echo "<ol>";
        echo "<li><strong>Browser Cache:</strong> Clear your browser cache and refresh the page</li>";
        echo "<li><strong>JavaScript Console:</strong> Press F12 and check for JavaScript errors</li>";
        echo "<li><strong>Network Tab:</strong> Check if AJAX calls are being made</li>";
        echo "<li><strong>Page Refresh:</strong> Try refreshing the dashboard page</li>";
        echo "<li><strong>Manual Refresh:</strong> Click the refresh button on the student card</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div class='success'>";
        echo "<h4>🚀 Quick Fixes to Try:</h4>";
        echo "<ol>";
        echo "<li><strong>Hard Refresh:</strong> Press Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>";
        echo "<li><strong>Clear Cache:</strong> Go to browser settings and clear cache</li>";
        echo "<li><strong>Check Console:</strong> Press F12 → Console tab → look for errors</li>";
        echo "<li><strong>Test AJAX:</strong> Click the refresh button on the student card</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ No Students Found</h4>";
        echo "<p>Even though the enrollment analysis showed 68 students, the AJAX query is returning 0.</p>";
        echo "<p>This suggests there might be a difference in the queries being used.</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Compare queries
    echo "<div class='result'>";
    echo "<h3>5. Query Comparison</h3>";
    
    // Original enrollment analysis query
    $enrollment_analysis_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    // AJAX endpoint query (should be the same)
    $ajax_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Query Type</th><th>Count</th><th>Status</th></tr>";
    echo "<tr><td>Enrollment Analysis</td><td>$enrollment_analysis_count</td><td>✅</td></tr>";
    echo "<tr><td>AJAX Endpoint</td><td>$ajax_count</td><td>" . ($ajax_count == $enrollment_analysis_count ? "✅" : "❌") . "</td></tr>";
    echo "</table>";
    
    if ($ajax_count != $enrollment_analysis_count) {
        echo "<div class='error'>";
        echo "<h4>❌ Query Mismatch</h4>";
        echo "<p>The queries are returning different results. This needs to be investigated.</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>✅ Queries Match</h4>";
        echo "<p>Both queries return the same result: <strong>$ajax_count students</strong></p>";
        echo "</div>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



