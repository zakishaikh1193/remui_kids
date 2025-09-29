<?php
/**
 * Test Trainee Count - Verify users with trainee role
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Trainee Role Count Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
</style>";

try {
    // 1. Check if trainee role exists
    echo "<div class='result'>";
    echo "<h3>1. Trainee Role Check</h3>";
    
    $traineerole = $DB->get_record('role', ['shortname' => 'trainee']);
    if ($traineerole) {
        echo "<div class='success'>";
        echo "<h4>✅ Trainee role found:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$traineerole->id}</li>";
        echo "<li><strong>Shortname:</strong> {$traineerole->shortname}</li>";
        echo "<li><strong>Name:</strong> {$traineerole->name}</li>";
        echo "<li><strong>Description:</strong> {$traineerole->description}</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>❌ Trainee role not found!</h4>";
        echo "<p>The 'trainee' role does not exist in your system.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 2. Count users with trainee role
    echo "<div class='result'>";
    echo "<h3>2. Trainee Role Assignments</h3>";
    
    if ($traineerole) {
        $total_trainee_assignments = $DB->count_records('role_assignments', ['roleid' => $traineerole->id]);
        $trainee_system_assignments = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {role_assignments} ra 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ra.roleid = ? AND ctx.contextlevel = ?",
            [$traineerole->id, CONTEXT_SYSTEM]
        );
        
        echo "<table>";
        echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
        echo "<tr><td>Total Trainee Assignments</td><td>$total_trainee_assignments</td><td>All trainee role assignments</td></tr>";
        echo "<tr><td>System Level Trainee Assignments</td><td>$trainee_system_assignments</td><td>Trainee assignments at system level</td></tr>";
        echo "</table>";
        
        // 3. Count active trainee users (dashboard method)
        $active_trainees = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $traineerole->id]
        );
        
        echo "<div class='count'>$active_trainees</div>";
        echo "<p><strong>Active Trainee Users (Dashboard Count)</strong></p>";
        echo "<p>This is the number that will appear in your dashboard's 'TOTAL STUDENTS' card.</p>";
        
        if ($active_trainees > 0) {
            echo "<div class='success'>";
            echo "<h4>🎉 Success!</h4>";
            echo "<p>Your trainee count is working! The dashboard will show <strong>$active_trainees trainees</strong>.</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ No Active Trainees Found</h4>";
            echo "<p>The count is 0, which means:</p>";
            echo "<ul>";
            echo "<li>No users have the 'trainee' role assigned at system level</li>";
            echo "<li>All trainee users are suspended or deleted</li>";
            echo "<li>Trainee role assignments are at course level, not system level</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        // 4. Show actual trainee users
        echo "<div class='result'>";
        echo "<h3>3. Actual Trainee Users</h3>";
        
        $trainee_users = $DB->get_records_sql(
            "SELECT 
                u.id,
                u.username,
                u.firstname,
                u.lastname,
                u.email,
                u.suspended,
                u.deleted,
                ra.timemodified,
                CASE 
                    WHEN u.deleted = 1 THEN 'Deleted'
                    WHEN u.suspended = 1 THEN 'Suspended'
                    ELSE 'Active'
                END as status
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.roleid = ? AND ctx.contextlevel = ?
             ORDER BY u.firstname, u.lastname",
            [$traineerole->id, CONTEXT_SYSTEM]
        );
        
        if (!empty($trainee_users)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Assigned</th></tr>";
            foreach ($trainee_users as $user) {
                $class = ($user->status == 'Active') ? 'style="background-color: #d4edda;"' : 'style="background-color: #f8d7da;"';
                echo "<tr $class>";
                echo "<td>{$user->id}</td>";
                echo "<td><strong>{$user->firstname} {$user->lastname}</strong></td>";
                echo "<td>{$user->username}</td>";
                echo "<td>{$user->email}</td>";
                echo "<td><strong>{$user->status}</strong></td>";
                echo "<td>" . date('Y-m-d H:i:s', $user->timemodified) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ No Trainee Users Found</h4>";
            echo "<p>No users have been assigned the 'trainee' role at the system level.</p>";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ Cannot Count Trainees</h4>";
        echo "<p>The 'trainee' role does not exist, so we cannot count trainee users.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 5. Test AJAX endpoint
    echo "<div class='result'>";
    echo "<h3>4. AJAX Endpoint Test</h3>";
    
    $ajax_url = 'http://localhost/Kodeit-Iomad-local/iomad-test/test_ajax.php';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($ajax_url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['total_students'])) {
            echo "<div class='success'>";
            echo "<h4>✅ AJAX Endpoint Working</h4>";
            echo "<p><strong>Student Count from AJAX:</strong> {$data['total_students']}</p>";
            echo "<p><strong>Status:</strong> {$data['status']}</p>";
            echo "<p><strong>Timestamp:</strong> {$data['timestamp']}</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ AJAX Response Issue</h4>";
            echo "<p>AJAX endpoint responded but data format is unexpected.</p>";
            echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ AJAX Endpoint Not Accessible</h4>";
        echo "<p>Could not reach the AJAX endpoint.</p>";
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



