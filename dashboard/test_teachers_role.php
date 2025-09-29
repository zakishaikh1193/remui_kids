<?php
/**
 * Test Teachers Role Count - Verify users with "teachers" role
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Teachers Role Count Test</h2>";
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
    // 1. Check if teachers role exists
    echo "<div class='result'>";
    echo "<h3>1. Teachers Role Check</h3>";
    
    $teacherrole = $DB->get_record('role', ['shortname' => 'teachers']);
    if ($teacherrole) {
        echo "<div class='success'>";
        echo "<h4>✅ Teachers role found:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$teacherrole->id}</li>";
        echo "<li><strong>Shortname:</strong> {$teacherrole->shortname}</li>";
        echo "<li><strong>Name:</strong> {$teacherrole->name}</li>";
        echo "<li><strong>Description:</strong> {$teacherrole->description}</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>❌ Teachers role not found!</h4>";
        echo "<p>The 'teachers' role does not exist in your system.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 2. Count users with teachers role
    echo "<div class='result'>";
    echo "<h3>2. Teachers Role Assignments</h3>";
    
    if ($teacherrole) {
        $total_teacher_assignments = $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]);
        $teacher_system_assignments = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {role_assignments} ra 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ra.roleid = ? AND ctx.contextlevel = ?",
            [$teacherrole->id, CONTEXT_SYSTEM]
        );
        
        echo "<table>";
        echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
        echo "<tr><td>Total Teachers Assignments</td><td>$total_teacher_assignments</td><td>All teachers role assignments</td></tr>";
        echo "<tr><td>System Level Teachers Assignments</td><td>$teacher_system_assignments</td><td>Teachers assignments at system level</td></tr>";
        echo "</table>";
        
        // 3. Count active teacher users (dashboard method)
        $active_teachers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $teacherrole->id]
        );
        
        echo "<div class='count'>$active_teachers</div>";
        echo "<p><strong>Active Teachers Users (Dashboard Count)</strong></p>";
        echo "<p>This is the number that will appear in your dashboard's 'TEACHERS' card.</p>";
        
        if ($active_teachers > 0) {
            echo "<div class='success'>";
            echo "<h4>🎉 Success!</h4>";
            echo "<p>Your teachers count is working! The dashboard will show <strong>$active_teachers teachers</strong>.</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ No Active Teachers Found</h4>";
            echo "<p>The count is 0, which means:</p>";
            echo "<ul>";
            echo "<li>No users have the 'teachers' role assigned at system level</li>";
            echo "<li>All teacher users are suspended or deleted</li>";
            echo "<li>Teachers role assignments are at course level, not system level</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        // 4. Show actual teacher users
        echo "<div class='result'>";
        echo "<h3>3. Actual Teachers Users</h3>";
        
        $teacher_users = $DB->get_records_sql(
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
            [$teacherrole->id, CONTEXT_SYSTEM]
        );
        
        if (!empty($teacher_users)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Assigned</th></tr>";
            foreach ($teacher_users as $user) {
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
            echo "<h4>⚠️ No Teachers Users Found</h4>";
            echo "<p>No users have been assigned the 'teachers' role at the system level.</p>";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ Cannot Count Teachers</h4>";
        echo "<p>The 'teachers' role does not exist, so we cannot count teacher users.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 5. Test AJAX endpoint
    echo "<div class='result'>";
    echo "<h3>4. AJAX Endpoint Test</h3>";
    
    $ajax_url = $CFG->wwwroot . '/test_ajax.php';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($ajax_url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['teachers'])) {
            echo "<div class='success'>";
            echo "<h4>✅ AJAX Endpoint Working</h4>";
            echo "<p><strong>Teachers Count from AJAX:</strong> {$data['teachers']}</p>";
            echo "<p><strong>Status:</strong> {$data['status']}</p>";
            echo "<p><strong>Timestamp:</strong> {$data['timestamp']}</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ AJAX Response Issue</h4>";
            echo "<p>AJAX endpoint responded but teachers data is missing.</p>";
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



