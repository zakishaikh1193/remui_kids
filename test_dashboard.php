<?php
/**
 * Test script for school manager dashboard
 * This helps debug any issues with the main dashboard
 */

require_once('../../config.php');
require_login();

echo "Testing School Manager Dashboard...<br><br>";

// Test 1: Check if user is logged in
echo "✓ User logged in: " . $USER->username . "<br>";

// Test 2: Check company manager role
$companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
if ($companymanagerrole) {
    echo "✓ Company manager role exists (ID: " . $companymanagerrole->id . ")<br>";
    
    $context = context_system::instance();
    $is_company_manager = user_has_role_assignment($USER->id, $companymanagerrole->id, $context->id);
    echo "✓ User is company manager: " . ($is_company_manager ? 'Yes' : 'No') . "<br>";
} else {
    echo "✗ Company manager role not found<br>";
}

// Test 3: Check if company tables exist
$company_table_exists = $DB->get_manager()->table_exists('company');
$company_users_table_exists = $DB->get_manager()->table_exists('company_users');

echo "✓ Company table exists: " . ($company_table_exists ? 'Yes' : 'No') . "<br>";
echo "✓ Company users table exists: " . ($company_users_table_exists ? 'Yes' : 'No') . "<br>";

// Test 4: Try to get company info
if ($is_company_manager && $company_table_exists && $company_users_table_exists) {
    $company_info = $DB->get_record_sql(
        "SELECT c.*, u.firstname, u.lastname, u.email 
         FROM {company} c 
         JOIN {company_users} cu ON c.id = cu.companyid 
         JOIN {user} u ON cu.userid = u.id 
         WHERE cu.userid = ? AND cu.managertype = 1",
        [$USER->id]
    );
    
    if ($company_info) {
        echo "✓ Company info found: " . $company_info->name . "<br>";
    } else {
        echo "✗ No company info found for user<br>";
    }
}

// Test 5: Check template files
$sidebar_template = 'theme/remui_kids/templates/school_manager_sidebar.mustache';
$dashboard_template = 'theme/remui_kids/templates/school_manager_dashboard.mustache';

echo "✓ Sidebar template exists: " . (file_exists($CFG->dirroot . '/' . $sidebar_template) ? 'Yes' : 'No') . "<br>";
echo "✓ Dashboard template exists: " . (file_exists($CFG->dirroot . '/' . $dashboard_template) ? 'Yes' : 'No') . "<br>";

echo "<br>Test completed. If all tests pass, the dashboard should work.<br>";
echo "<br><a href='school_manager_dashboard.php'>Try accessing the dashboard</a>";
?>

