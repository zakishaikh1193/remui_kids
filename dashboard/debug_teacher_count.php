<?php
/**
 * Debug Teacher Count - Find out why teacher count is 0
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Teacher Count Debugging</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
</style>";

try {
    // 1. Check if teacher role exists
    echo "<div class='result'>";
    echo "<h3>1. Teacher Role Check</h3>";
    
    $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
    if ($teacherrole) {
        echo "<div class='success'>";
        echo "<h4>✅ Teacher role found:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$teacherrole->id}</li>";
        echo "<li><strong>Shortname:</strong> {$teacherrole->shortname}</li>";
        echo "<li><strong>Name:</strong> {$teacherrole->name}</li>";
        echo "<li><strong>Description:</strong> {$teacherrole->description}</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>❌ Teacher role not found!</h4>";
        echo "<p>The 'teacher' role does not exist in your system.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 2. Check all roles to see what's available
    echo "<div class='result'>";
    echo "<h3>2. All Available Roles</h3>";
    
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

    // 3. Check for teacher-like roles
    echo "<div class='result'>";
    echo "<h3>3. Looking for Teacher-like Roles</h3>";
    
    $teacher_like_roles = $DB->get_records_sql(
        "SELECT * FROM {role} WHERE shortname LIKE '%teacher%' OR shortname LIKE '%instructor%' OR shortname LIKE '%trainer%' OR shortname LIKE '%educator%'",
        []
    );
    
    if (!empty($teacher_like_roles)) {
        echo "<div class='success'>";
        echo "<h4>✅ Found potential teacher roles:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Shortname</th><th>Name</th><th>Users</th></tr>";
        foreach ($teacher_like_roles as $role) {
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
        echo "<h4>⚠️ No teacher-like roles found</h4>";
        echo "<p>No roles with names like 'teacher', 'instructor', 'trainer', or 'educator' were found.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 4. Check teacher role assignments
    if ($teacherrole) {
        echo "<div class='result'>";
        echo "<h3>4. Teacher Role Assignments</h3>";
        
        $total_teacher_assignments = $DB->count_records('role_assignments', ['roleid' => $teacherrole->id]);
        $teacher_system_assignments = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {role_assignments} ra 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ra.roleid = ? AND ctx.contextlevel = ?",
            [$teacherrole->id, CONTEXT_SYSTEM]
        );
        
        echo "<table>";
        echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
        echo "<tr><td>Total Teacher Assignments</td><td>$total_teacher_assignments</td><td>All teacher role assignments</td></tr>";
        echo "<tr><td>System Level Teacher Assignments</td><td>$teacher_system_assignments</td><td>Teacher assignments at system level</td></tr>";
        echo "</table>";
        
        // 5. Count active teacher users (dashboard method)
        $active_teachers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $teacherrole->id]
        );
        
        echo "<div class='count'>$active_teachers</div>";
        echo "<p><strong>Active Teacher Users (Dashboard Count)</strong></p>";
        echo "<p>This is the number that will appear in your dashboard's 'TEACHERS' card.</p>";
        
        if ($active_teachers > 0) {
            echo "<div class='success'>";
            echo "<h4>🎉 Success!</h4>";
            echo "<p>Your teacher count is working! The dashboard will show <strong>$active_teachers teachers</strong>.</p>";
            echo "</div>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ No Active Teachers Found</h4>";
            echo "<p>The count is 0, which means:</p>";
            echo "<ul>";
            echo "<li>No users have the 'teacher' role assigned at system level</li>";
            echo "<li>All teacher users are suspended or deleted</li>";
            echo "<li>Teacher role assignments are at course level, not system level</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        // 6. Show actual teacher users
        echo "<div class='result'>";
        echo "<h3>5. Actual Teacher Users</h3>";
        
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
            echo "<h4>⚠️ No Teacher Users Found</h4>";
            echo "<p>No users have been assigned the 'teacher' role at the system level.</p>";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ Cannot Count Teachers</h4>";
        echo "<p>The 'teacher' role does not exist, so we cannot count teacher users.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 7. Check teacher assignments by context level
    if ($teacherrole) {
        echo "<div class='result'>";
        echo "<h3>6. Teacher Assignments by Context Level</h3>";
        
        $teacher_contexts = $DB->get_records_sql(
            "SELECT 
                ctx.contextlevel,
                CASE 
                    WHEN ctx.contextlevel = 10 THEN 'System Level'
                    WHEN ctx.contextlevel = 40 THEN 'Course Level'
                    WHEN ctx.contextlevel = 50 THEN 'Course Category Level'
                    ELSE 'Other Level'
                END as context_description,
                COUNT(*) as teacher_assignments
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.roleid = ?
             GROUP BY ctx.contextlevel
             ORDER BY ctx.contextlevel",
            [$teacherrole->id]
        );
        
        if (!empty($teacher_contexts)) {
            echo "<table>";
            echo "<tr><th>Context Level</th><th>Description</th><th>Teacher Assignments</th></tr>";
            foreach ($teacher_contexts as $context) {
                echo "<tr>";
                echo "<td>{$context->contextlevel}</td>";
                echo "<td>{$context->context_description}</td>";
                echo "<td>{$context->teacher_assignments}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='issue'>";
            echo "<p>No teacher role assignments found at any context level.</p>";
            echo "</div>";
        }
        echo "</div>";
    }

    // 8. Solutions
    echo "<div class='result'>";
    echo "<h3>7. Solutions to Fix Teacher Count</h3>";
    
    if (!$teacherrole) {
        echo "<div class='issue'>";
        echo "<h4>Solution 1: Create Teacher Role</h4>";
        echo "<p>Create a 'teacher' role in your Moodle system:</p>";
        echo "<ol>";
        echo "<li>Go to <strong>Site Administration → Users → Permissions → Define roles</strong></li>";
        echo "<li>Click <strong>'Add a new role'</strong></li>";
        echo "<li>Set Short name: <strong>teacher</strong></li>";
        echo "<li>Set Name: <strong>Teacher</strong></li>";
        echo "<li>Set Description: <strong>Default role for teachers</strong></li>";
        echo "<li>Configure permissions and save</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>Solution 1: Assign Users to Teacher Role</h4>";
        echo "<p>Assign users to the teacher role at system level:</p>";
        echo "<ol>";
        echo "<li>Go to <strong>Site Administration → Users → Permissions → Assign system roles</strong></li>";
        echo "<li>Click on <strong>Teacher</strong> role</li>";
        echo "<li>Click <strong>'Assign users to this role'</strong></li>";
        echo "<li>Select users and assign them</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h4>Solution 2: Use Different Role</h4>";
    echo "<p>If you have a different role for teachers, update the AJAX endpoint to use that role instead.</p>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h4>Solution 3: Use Course-Level Teachers</h4>";
    echo "<p>If teachers are assigned at course level, modify the query to count course-level assignments.</p>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



