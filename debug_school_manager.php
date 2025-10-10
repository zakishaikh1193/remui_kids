<?php
/**
 * Debug School Manager Detection
 * This page helps debug why some school managers are not being detected
 */

require_once('../../config.php');
require_login();

// Only allow admins to access this debug page
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/debug_school_manager.php');
$PAGE->set_title('Debug School Manager Detection');
$PAGE->set_heading('Debug School Manager Detection');

echo $OUTPUT->header();

echo "<h2>School Manager Detection Debug</h2>";

// Get current user
$user = $USER;
echo "<h3>Current User: {$user->username} (ID: {$user->id})</h3>";

// Check all detection methods
$isschoolmanager = false;
$detectionmethods = [];

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

// Method 4: IOMAD company
if (!$isschoolmanager && class_exists('company')) {
    try {
        $company = company::by_userid($user->id);
        if ($company) {
            $companyuser = $DB->get_record('company_users', ['userid' => $user->id]);
            if ($companyuser && $companyuser->managertype > 0) {
                $isschoolmanager = true;
                $detectionmethods[] = "Method 4: IOMAD company admin";
                echo "<p><strong>✓ Method 4:</strong> IOMAD company admin found (Company: {$company->name}, Manager Type: {$companyuser->managertype})</p>";
            } else {
                echo "<p><strong>✗ Method 4:</strong> IOMAD company found but not admin (Company: {$company->name})</p>";
            }
        } else {
            echo "<p><strong>✗ Method 4:</strong> No IOMAD company found</p>";
        }
    } catch (Exception $e) {
        echo "<p><strong>✗ Method 4:</strong> IOMAD error: " . $e->getMessage() . "</p>";
    }
}

// Show all user roles
echo "<h3>All User Roles:</h3>";
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
echo "<h3>User Profile Fields:</h3>";
echo "<p><strong>Department:</strong> " . ($user->department ?? 'NULL') . "</p>";
echo "<p><strong>Institution:</strong> " . ($user->institution ?? 'NULL') . "</p>";

// Final result
echo "<h3>Final Detection Result:</h3>";
if ($isschoolmanager) {
    echo "<p style='color: green; font-weight: bold;'>✓ USER IS DETECTED AS SCHOOL MANAGER</p>";
    echo "<p>Detection methods that worked:</p><ul>";
    foreach ($detectionmethods as $method) {
        echo "<li>{$method}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ USER IS NOT DETECTED AS SCHOOL MANAGER</p>";
    echo "<p>None of the detection methods found this user to be a school manager.</p>";
}

echo $OUTPUT->footer();
?>
