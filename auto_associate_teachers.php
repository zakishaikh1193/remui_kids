<?php
require_once('../../config.php');
require_login();

global $USER, $DB;

// Check if user is company manager
$companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
if (!$companymanagerrole) {
    die("Company manager role not found!");
}

$context = context_system::instance();
$is_company_manager = user_has_role_assignment($USER->id, $companymanagerrole->id, $context->id);

if (!$is_company_manager) {
    die("You must be a company manager to access this page!");
}

// Get company info
if ($DB->get_manager()->table_exists('company') && $DB->get_manager()->table_exists('company_users')) {
    $company_info = $DB->get_record_sql(
        "SELECT c.*, u.firstname, u.lastname, u.email
         FROM {company} c
         JOIN {company_users} cu ON c.id = cu.companyid
         JOIN {user} u ON cu.userid = u.id
         WHERE cu.userid = ? AND cu.managertype = 1",
        [$USER->id]
    );
    
    if (!$company_info) {
        die("No company information found for this user!");
    }
    
    echo "<h2>Auto-Associate Teachers with Company: " . $company_info->name . "</h2>";
    
    // Get teacher role
    $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
    if (!$teacher_role) {
        die("Teacher role not found!");
    }
    
    // Get all teachers
    $teachers = $DB->get_records_sql(
        "SELECT DISTINCT u.*
         FROM {user} u
         JOIN {role_assignments} ra ON u.id = ra.userid
         WHERE ra.roleid = ? AND u.deleted = 0
         ORDER BY u.firstname, u.lastname",
        [$teacher_role->id]
    );
    
    echo "<h3>Processing " . count($teachers) . " teachers...</h3>";
    
    $associated_count = 0;
    $already_associated_count = 0;
    $error_count = 0;
    
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Processing Results:</h4>";
    
    foreach ($teachers as $teacher) {
        // Check if teacher is already in company
        $in_company = $DB->get_record('company_users', ['userid' => $teacher->id, 'companyid' => $company_info->id]);
        
        if (!$in_company) {
            $company_user = new stdClass();
            $company_user->userid = $teacher->id;
            $company_user->companyid = $company_info->id;
            $company_user->managertype = 0; // 0 = regular user, 1 = manager
            $company_user->timecreated = time();
            $company_user->timemodified = time();
            
            if ($DB->insert_record('company_users', $company_user)) {
                $associated_count++;
                echo "<p style='color: green;'>✅ Associated: " . $teacher->firstname . " " . $teacher->lastname . " (" . $teacher->username . ")</p>";
            } else {
                $error_count++;
                echo "<p style='color: red;'>❌ Error associating: " . $teacher->firstname . " " . $teacher->lastname . "</p>";
            }
        } else {
            $already_associated_count++;
            echo "<p style='color: blue;'>ℹ️ Already associated: " . $teacher->firstname . " " . $teacher->lastname . "</p>";
        }
    }
    
    echo "</div>";
    
    echo "<div style='background: lightgreen; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Summary:</h3>";
    echo "<p><strong>New associations:</strong> " . $associated_count . "</p>";
    echo "<p><strong>Already associated:</strong> " . $already_associated_count . "</p>";
    echo "<p><strong>Errors:</strong> " . $error_count . "</p>";
    echo "<p><strong>Total teachers in company:</strong> " . ($associated_count + $already_associated_count) . "</p>";
    echo "</div>";
    
    // Test the dashboard query after association
    $teacher_roles = ['teacher', 'editingteacher', 'coursecreator', 'manager'];
    $total_teachers_in_dashboard = 0;
    
    foreach ($teacher_roles as $role_shortname) {
        $count = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {company_users} cu ON u.id = cu.userid 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {role} r ON ra.roleid = r.id 
             WHERE cu.companyid = ? AND r.shortname = ? AND u.deleted = 0",
            [$company_info->id, $role_shortname]
        );
        $total_teachers_in_dashboard += $count;
    }
    
    echo "<div style='background: lightblue; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Dashboard Statistics Preview:</h3>";
    echo "<p><strong>Total Teachers (will show in dashboard):</strong> " . $total_teachers_in_dashboard . "</p>";
    echo "<p><em>This should now match the number of teachers you see in your data!</em></p>";
    echo "</div>";
    
    if ($associated_count > 0 || $already_associated_count > 0) {
        echo "<div style='background: lightyellow; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Next Steps:</h3>";
        echo "<p>1. Go to your <a href='school_manager_dashboard.php'>School Manager Dashboard</a></p>";
        echo "<p>2. Click the 'Refresh Stats' button to update the counts</p>";
        echo "<p>3. You should now see the correct teacher count!</p>";
        echo "</div>";
    }
    
} else {
    echo "<p>Company tables do not exist!</p>";
}

echo "<hr>";
echo "<p><a href='associate_teachers.php'>Manual Association Tool</a> | <a href='school_manager_dashboard.php'>Dashboard</a></p>";
?>

