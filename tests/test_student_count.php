<?php
/**
 * Test Student Count - Verify the enrolled users count
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Student Count Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
</style>";

try {
    // Test the new student count method
    $students = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    echo "<div class='result'>";
    echo "<h3>✅ Student Count Test Results</h3>";
    echo "<div class='count'>$students</div>";
    echo "<p><strong>Enrolled Users Count (New Method)</strong></p>";
    echo "<p>This is the number that will now appear in your dashboard's 'TOTAL STUDENTS' card.</p>";
    echo "</div>";
    
    if ($students > 0) {
        echo "<div class='success'>";
        echo "<h4>🎉 Success!</h4>";
        echo "<p>Your student count is now working! The dashboard will show <strong>$students students</strong>.</p>";
        echo "<p>This count includes all users who are enrolled in courses and are active (not deleted or suspended).</p>";
        echo "</div>";
    } else {
        echo "<div class='result'>";
        echo "<h4>ℹ️ No Enrolled Users Found</h4>";
        echo "<p>The count is 0, which means:</p>";
        echo "<ul>";
        echo "<li>No users are currently enrolled in courses</li>";
        echo "<li>All enrolled users are suspended or deleted</li>";
        echo "<li>Courses exist but no enrollments have been made</li>";
        echo "</ul>";
        echo "<p>This is normal for a new or empty system.</p>";
        echo "</div>";
    }
    
    // Show some additional info
    $total_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
    $total_enrollments = $DB->count_records('user_enrolments');
    
    echo "<div class='result'>";
    echo "<h3>Additional Information</h3>";
    echo "<p><strong>Total Active Users:</strong> $total_users</p>";
    echo "<p><strong>Total Enrollments:</strong> $total_enrollments</p>";
    echo "<p><strong>Students (Enrolled Users):</strong> $students</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



