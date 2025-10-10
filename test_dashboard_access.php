<?php
/**
 * Test Dashboard Access - Debug Page
 * This page helps debug why some school managers can't see the dashboard
 */

require_once('../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/test_dashboard_access.php');
$PAGE->set_title('Test Dashboard Access');
$PAGE->set_heading('Test Dashboard Access');

echo $OUTPUT->header();

echo "<h2>Dashboard Access Test</h2>";

// Get current user
$user = $USER;
echo "<h3>Current User: {$user->username} (ID: {$user->id})</h3>";

// Test all detection methods
$isschoolmanager = false;
$detectionmethods = [];

echo "<h4>Detection Method Results:</h4>";

// Method 1: Specific school manager roles (system level)
$schoolmanagerroles = $DB->get_records_sql(
    "SELECT DISTINCT r.shortname, r.name 
     FROM {role} r 
     JOIN {role_assignments} ra ON r.id = ra.roleid 
     JOIN {context} ctx ON ra.contextid = ctx.id 
     WHERE ra.userid = ? 
     AND ctx.contextlevel = ? 
     AND r.shortname IN ('school_manager', 'manager', 'schooladmin', 'school_admin')",
    [$user->id, CONTEXT_SYSTEM]
);

if (!empty($schoolmanagerroles)) {
    $isschoolmanager = true;
    $detectionmethods[] = "Method 1: System-level school manager roles";
    echo "<p><strong>✓ Method 1:</strong> System-level school manager roles found:</p><ul>";
    foreach ($schoolmanagerroles as $role) {
        echo "<li>{$role->shortname} - {$role->name}</li>";
    }
    echo "</ul>";
} else {
    echo "<p><strong>✗ Method 1:</strong> No system-level school manager roles found</p>";
}

// Method 2: Any context level roles
if (!$isschoolmanager) {
    $anycontextroles = $DB->get_records_sql(
        "SELECT DISTINCT r.shortname, r.name 
         FROM {role} r 
         JOIN {role_assignments} ra ON r.id = ra.roleid 
         WHERE ra.userid = ? 
         AND r.shortname IN ('school_manager', 'manager', 'schooladmin', 'school_admin')",
        [$user->id]
    );
    
    if (!empty($anycontextroles)) {
        $isschoolmanager = true;
        $detectionmethods[] = "Method 2: Any context school manager roles";
        echo "<p><strong>✓ Method 2:</strong> Any context school manager roles found:</p><ul>";
        foreach ($anycontextroles as $role) {
            echo "<li>{$role->shortname} - {$role->name}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><strong>✗ Method 2:</strong> No any-context school manager roles found</p>";
    }
}

// Method 3: Capabilities
if (!$isschoolmanager) {
    $context = context_system::instance();
    $capabilities = [];
    if (has_capability('moodle/site:config', $context, $user)) $capabilities[] = 'moodle/site:config';
    if (has_capability('moodle/user:create', $context, $user)) $capabilities[] = 'moodle/user:create';
    if (has_capability('moodle/user:manageownfiles', $context, $user)) $capabilities[] = 'moodle/user:manageownfiles';
    
    if (!empty($capabilities)) {
        $isschoolmanager = true;
        $detectionmethods[] = "Method 3: Administrative capabilities";
        echo "<p><strong>✓ Method 3:</strong> Administrative capabilities found:</p><ul>";
        foreach ($capabilities as $cap) {
            echo "<li>{$cap}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><strong>✗ Method 3:</strong> No administrative capabilities found</p>";
    }
}

// Show all user roles
echo "<h4>All User Roles:</h4>";
$allroles = $DB->get_records_sql(
    "SELECT r.shortname, r.name, ctx.contextlevel, ctx.contextname
     FROM {role} r 
     JOIN {role_assignments} ra ON r.id = ra.roleid 
     JOIN {context} ctx ON ra.contextid = ctx.id 
     WHERE ra.userid = ?
     ORDER BY ctx.contextlevel, r.name",
    [$user->id]
);

if (!empty($allroles)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Role Shortname</th><th>Role Name</th><th>Context Level</th><th>Context Name</th></tr>";
    foreach ($allroles as $role) {
        echo "<tr><td>{$role->shortname}</td><td>{$role->name}</td><td>{$role->contextlevel}</td><td>{$role->contextname}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No roles found for this user.</p>";
}

// Show user profile fields
echo "<h4>User Profile Fields:</h4>";
echo "<p><strong>Department:</strong> " . ($user->department ?? 'NULL') . "</p>";
echo "<p><strong>Institution:</strong> " . ($user->institution ?? 'NULL') . "</p>";

// Final result
echo "<h4>Final Detection Result:</h4>";
if ($isschoolmanager) {
    echo "<p style='color: green; font-weight: bold;'>✓ USER IS DETECTED AS SCHOOL MANAGER</p>";
    echo "<p>Detection methods that worked:</p><ul>";
    foreach ($detectionmethods as $method) {
        echo "<li>{$method}</li>";
    }
    echo "</ul>";
    
    echo "<h4>Dashboard Access Test:</h4>";
    echo "<p><a href='{$CFG->wwwroot}/my/' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Dashboard Access</a></p>";
    echo "<p>Click the button above to test if you can access the school manager dashboard.</p>";
    
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ USER IS NOT DETECTED AS SCHOOL MANAGER</p>";
    echo "<p>None of the detection methods found this user to be a school manager.</p>";
}

echo "<h4>Debug Information:</h4>";
echo "<p><strong>User ID:</strong> {$user->id}</p>";
echo "<strong>Username:</strong> {$user->username}</p>";
echo "<p><strong>Full Name:</strong> " . fullname($user) . "</p>";
echo "<p><strong>Email:</strong> {$user->email}</p>";
echo "<p><strong>First Access:</strong> " . date('Y-m-d H:i:s', $user->firstaccess) . "</p>";
echo "<p><strong>Last Access:</strong> " . date('Y-m-d H:i:s', $user->lastaccess) . "</p>";

echo $OUTPUT->footer();
?>
