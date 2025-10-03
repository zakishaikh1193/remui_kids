<?php
/**
 * Fix Student Count - Create student role or use alternative counting
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Fix Student Count Issue</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .warning { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
    .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .button:hover { background: #0056b3; }
</style>";

try {
    // 1. Show all existing roles
    echo "<div class='result'>";
    echo "<h3>1. Existing Roles in Your System</h3>";
    
    $allroles = $DB->get_records('role', [], 'id ASC');
    if (!empty($allroles)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Shortname</th><th>Name</th><th>Description</th><th>Users</th></tr>";
        foreach ($allroles as $role) {
            $user_count = $DB->count_records('role_assignments', ['roleid' => $role->id]);
            echo "<tr>";
            echo "<td>{$role->id}</td>";
            echo "<td><strong>{$role->shortname}</strong></td>";
            echo "<td>{$role->name}</td>";
            echo "<td>" . s($role->description) . "</td>";
            echo "<td>$user_count</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 2. Check for common student-like roles
    echo "<div class='result'>";
    echo "<h3>2. Looking for Student-like Roles</h3>";
    
    $student_like_roles = $DB->get_records_sql(
        "SELECT * FROM {role} WHERE shortname LIKE '%student%' OR shortname LIKE '%learner%' OR shortname LIKE '%participant%' OR shortname LIKE '%user%'",
        []
    );
    
    if (!empty($student_like_roles)) {
        echo "<div class='success'>";
        echo "<h4>✅ Found potential student roles:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Shortname</th><th>Name</th><th>Users</th></tr>";
        foreach ($student_like_roles as $role) {
            $user_count = $DB->count_records('role_assignments', ['roleid' => $role->id]);
            echo "<tr>";
            echo "<td>{$role->id}</td>";
            echo "<td><strong>{$role->shortname}</strong></td>";
            echo "<td>{$role->name}</td>";
            echo "<td>$user_count</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>⚠️ No student-like roles found</h4>";
        echo "<p>No roles with names like 'student', 'learner', 'participant', or 'user' were found.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 3. Check enrolled users (alternative method)
    echo "<div class='result'>";
    echo "<h3>3. Alternative: Count Enrolled Users</h3>";
    
    $enrolled_users = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    echo "<div class='count'>$enrolled_users</div>";
    echo "<p><strong>Users enrolled in courses (alternative student count)</strong></p>";
    
    if ($enrolled_users > 0) {
        echo "<div class='success'>";
        echo "<p>✅ This could be used as your student count instead of role-based counting.</p>";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<p>⚠️ No enrolled users found either.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 4. Check total active users
    echo "<div class='result'>";
    echo "<h3>4. Total Active Users</h3>";
    
    $active_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
    echo "<div class='count'>$active_users</div>";
    echo "<p><strong>Total active users in the system</strong></p>";
    echo "</div>";

    // 5. Solutions
    echo "<div class='result'>";
    echo "<h3>5. Solutions to Fix Student Count</h3>";
    
    echo "<h4>Option 1: Create Student Role</h4>";
    echo "<p>Create a 'student' role in your Moodle system:</p>";
    echo "<ol>";
    echo "<li>Go to <strong>Site Administration → Users → Permissions → Define roles</strong></li>";
    echo "<li>Click <strong>'Add a new role'</strong></li>";
    echo "<li>Set Short name: <strong>student</strong></li>";
    echo "<li>Set Name: <strong>Student</strong></li>";
    echo "<li>Set Description: <strong>Default role for students</strong></li>";
    echo "<li>Configure permissions and save</li>";
    echo "</ol>";
    
    echo "<h4>Option 2: Use Existing Role</h4>";
    if (!empty($student_like_roles)) {
        echo "<p>Use one of these existing roles instead of 'student':</p>";
        foreach ($student_like_roles as $role) {
            echo "<p><strong>{$role->shortname}</strong> - {$role->name}</p>";
        }
    } else {
        echo "<p>No suitable existing roles found.</p>";
    }
    
    echo "<h4>Option 3: Use Enrolled Users Count</h4>";
    echo "<p>Count users enrolled in courses instead of role-based counting.</p>";
    
    echo "<h4>Option 4: Use Total Active Users</h4>";
    echo "<p>Count all active users as students.</p>";
    echo "</div>";

    // 6. Test different counting methods
    echo "<div class='result'>";
    echo "<h3>6. Test Different Counting Methods</h3>";
    
    echo "<table>";
    echo "<tr><th>Method</th><th>Count</th><th>Description</th></tr>";
    
    // Method 1: Current (student role at system level)
    $current_method = 0;
    echo "<tr><td>Current Method (student role)</td><td>$current_method</td><td>Not working - role doesn't exist</td></tr>";
    
    // Method 2: Enrolled users
    echo "<tr><td>Enrolled Users</td><td>$enrolled_users</td><td>Users enrolled in courses</td></tr>";
    
    // Method 3: Active users
    echo "<tr><td>Active Users</td><td>$active_users</td><td>All active users</td></tr>";
    
    // Method 4: Users with any role
    $users_with_roles = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id)
         FROM {user} u
         JOIN {role_assignments} ra ON u.id = ra.userid
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    echo "<tr><td>Users with Any Role</td><td>$users_with_roles</td><td>Users assigned any role</td></tr>";
    
    echo "</table>";
    echo "</div>";

    // 7. Quick fix buttons
    echo "<div class='result'>";
    echo "<h3>7. Quick Fix Options</h3>";
    
    echo "<p>Choose which counting method you want to use:</p>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='use_enrolled' class='button'>Use Enrolled Users Count ($enrolled_users)</button>";
    echo "<button type='submit' name='use_active' class='button'>Use Active Users Count ($active_users)</button>";
    echo "<button type='submit' name='use_any_role' class='button'>Use Users with Any Role ($users_with_roles)</button>";
    echo "</form>";
    echo "</div>";

    // Handle form submission
    if ($_POST) {
        echo "<div class='result'>";
        echo "<h3>8. Updating AJAX Endpoint</h3>";
        
        if (isset($_POST['use_enrolled'])) {
            $new_query = "SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {user} u ON ue.userid = u.id WHERE u.deleted = 0 AND u.suspended = 0";
            $method_name = "Enrolled Users";
        } elseif (isset($_POST['use_active'])) {
            $new_query = "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND suspended = 0";
            $method_name = "Active Users";
        } elseif (isset($_POST['use_any_role'])) {
            $new_query = "SELECT COUNT(DISTINCT u.id) FROM {user} u JOIN {role_assignments} ra ON u.id = ra.userid WHERE u.deleted = 0 AND u.suspended = 0";
            $method_name = "Users with Any Role";
        }
        
        echo "<div class='success'>";
        echo "<h4>✅ Selected Method: $method_name</h4>";
        echo "<p>New query: <code>$new_query</code></p>";
        echo "<p>I'll update the AJAX endpoint to use this method.</p>";
        echo "</div>";
        echo "</div>";
        
        // Update the AJAX endpoint
        $ajax_content = "<?php
// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON first
header('Content-Type: application/json');

try {
    // Adjust the path to config.php based on its location relative to this file
    require_once('../../../config.php'); // Assuming this file is in the Moodle root directory

    global \$DB;

    // Get total schools (top-level categories, excluding system categories)
    \$totalschools = \$DB->count_records_sql(
        \"SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 AND id > 1 AND parent = 0\",
        []
    );

    // Get total courses (visible, excluding site course)
    \$totalcourses = \$DB->count_records_sql(
        \"SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1\",
        []
    );

    // Get total students - using $method_name method
    \$totalstudents = \$DB->count_records_sql(
        \"$new_query\",
        []
    );

    echo json_encode([
        'status' => 'success',
        'total_schools' => \$totalschools,
        'total_courses' => \$totalcourses,
        'total_students' => \$totalstudents,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception \$e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch statistics',
        'message' => \$e->getMessage(),
        'status' => 'error'
    ]);
} catch (Error \$e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error',
        'message' => \$e->getMessage(),
        'status' => 'error'
    ]);
}
?>";

        file_put_contents('test_ajax.php', $ajax_content);
        
        echo "<div class='success'>";
        echo "<h4>✅ AJAX Endpoint Updated!</h4>";
        echo "<p>The <code>test_ajax.php</code> file has been updated to use the $method_name counting method.</p>";
        echo "<p>Your dashboard should now show the correct student count.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='issue'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



