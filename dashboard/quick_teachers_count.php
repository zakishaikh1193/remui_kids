<?php
/**
 * Quick Teachers Count - Get the count of active teachers users
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Active Teachers Users Count</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .count { font-size: 32px; font-weight: bold; color: #0066cc; text-align: center; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    // Check if teachers role exists
    $teacherrole = $DB->get_record('role', ['shortname' => 'teachers']);
    
    if ($teacherrole) {
        echo "<div class='result'>";
        echo "<h3>✅ Teachers Role Found</h3>";
        echo "<p><strong>Role ID:</strong> {$teacherrole->id}</p>";
        echo "<p><strong>Role Name:</strong> {$teacherrole->name}</p>";
        echo "<p><strong>Shortname:</strong> {$teacherrole->shortname}</p>";
        echo "</div>";
        
        // Count active teachers users
        $active_teachers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $teacherrole->id]
        );
        
        echo "<div class='result'>";
        echo "<h3>📊 Active Teachers Count</h3>";
        echo "<div class='count'>$active_teachers</div>";
        echo "<p style='text-align: center;'><strong>Active Teachers Users</strong></p>";
        echo "<p style='text-align: center;'>This is the number that appears in your dashboard's TEACHERS card.</p>";
        echo "</div>";
        
        if ($active_teachers > 0) {
            echo "<div class='success'>";
            echo "<h4>🎉 Teachers Found!</h4>";
            echo "<p>Your system has <strong>$active_teachers active teachers</strong> with the 'teachers' role assigned at system level.</p>";
            echo "</div>";
            
            // Show the actual teachers
            $teacher_users = $DB->get_records_sql(
                "SELECT 
                    u.id,
                    u.username,
                    u.firstname,
                    u.lastname,
                    u.email,
                    FROM_UNIXTIME(ra.timemodified) as assigned_date
                 FROM {user} u
                 JOIN {role_assignments} ra ON u.id = ra.userid
                 JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ra.roleid = ? AND ctx.contextlevel = ?
                 AND u.deleted = 0 AND u.suspended = 0
                 ORDER BY u.firstname, u.lastname",
                [$teacherrole->id, CONTEXT_SYSTEM]
            );
            
            echo "<div class='result'>";
            echo "<h3>👥 List of Active Teachers</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Assigned Date</th></tr>";
            foreach ($teacher_users as $user) {
                echo "<tr>";
                echo "<td>{$user->id}</td>";
                echo "<td><strong>{$user->firstname} {$user->lastname}</strong></td>";
                echo "<td>{$user->username}</td>";
                echo "<td>{$user->email}</td>";
                echo "<td>{$user->assigned_date}</td>";
                echo "</tr>";
            }
            echo "</table>";
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
        
    } else {
        echo "<div class='issue'>";
        echo "<h4>❌ Teachers Role Not Found</h4>";
        echo "<p>The 'teachers' role does not exist in your system.</p>";
        echo "<p>You need to either:</p>";
        echo "<ul>";
        echo "<li>Create the 'teachers' role</li>";
        echo "<li>Use a different role name</li>";
        echo "<li>Check what roles exist in your system</li>";
        echo "</ul>";
        echo "</div>";
        
        // Show available roles
        $allroles = $DB->get_records('role', [], 'id ASC');
        echo "<div class='result'>";
        echo "<h3>📋 Available Roles in Your System</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Shortname</th><th>Name</th><th>Description</th></tr>";
        foreach ($allroles as $role) {
            echo "<tr>";
            echo "<td>{$role->id}</td>";
            echo "<td><strong>{$role->shortname}</strong></td>";
            echo "<td>{$role->name}</td>";
            echo "<td>" . s($role->description) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // Test AJAX endpoint
    echo "<div class='result'>";
    echo "<h3>🔄 AJAX Endpoint Test</h3>";
    
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
            echo "</div>";
        }
    } else {
        echo "<div class='issue'>";
        echo "<h4>❌ AJAX Endpoint Not Accessible</h4>";
        echo "<p>Could not reach the AJAX endpoint.</p>";
        echo "</div>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='issue'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



