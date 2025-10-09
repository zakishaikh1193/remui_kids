<?php
require_once('../../config.php');
require_login();

global $USER, $DB;

echo "<h2>Simple Database Debug</h2>";
echo "<p><strong>Current User ID:</strong> " . $USER->id . "</p>";
echo "<p><strong>Current User:</strong> " . fullname($USER) . " (" . $USER->username . ")</p>";

// Check if we can connect to database
try {
    $test_query = $DB->get_record_sql("SELECT 1 as test");
    echo "<p><strong>Database Connection:</strong> ✅ Working</p>";
} catch (Exception $e) {
    echo "<p><strong>Database Connection:</strong> ❌ Error: " . $e->getMessage() . "</p>";
}

// Check basic tables
$tables_to_check = ['user', 'role', 'company', 'company_users'];
foreach ($tables_to_check as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "<p><strong>Table '{$table}':</strong> " . ($exists ? '✅ Exists' : '❌ Missing') . "</p>";
}

// Check roles
echo "<h3>Available Roles:</h3>";
$roles = $DB->get_records('role');
foreach ($roles as $role) {
    echo "- " . $role->shortname . " (ID: " . $role->id . ")<br>";
}

// Check if user has any role assignments
echo "<h3>Current User's Role Assignments:</h3>";
$user_roles = $DB->get_records_sql(
    "SELECT r.shortname, r.id, ra.contextid
     FROM {role_assignments} ra
     JOIN {role} r ON ra.roleid = r.id
     WHERE ra.userid = ?",
    [$USER->id]
);

if (count($user_roles) > 0) {
    foreach ($user_roles as $role) {
        echo "- " . $role->shortname . " (Context: " . $role->contextid . ")<br>";
    }
} else {
    echo "No role assignments found for this user!<br>";
}

// Check company tables if they exist
if ($DB->get_manager()->table_exists('company') && $DB->get_manager()->table_exists('company_users')) {
    echo "<h3>Company Information:</h3>";
    
    $company_user = $DB->get_record('company_users', ['userid' => $USER->id]);
    if ($company_user) {
        echo "<p><strong>User is in company:</strong> ID " . $company_user->companyid . "</p>";
        echo "<p><strong>Manager Type:</strong> " . $company_user->managertype . "</p>";
        
        $company = $DB->get_record('company', ['id' => $company_user->companyid]);
        if ($company) {
            echo "<p><strong>Company Name:</strong> " . $company->name . "</p>";
        }
    } else {
        echo "<p>User is not associated with any company!</p>";
    }
} else {
    echo "<p>Company tables do not exist!</p>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Full Debug</a> | <a href='school_manager_dashboard.php'>Dashboard</a></p>";
?>

