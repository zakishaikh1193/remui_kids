<?php
require_once('../../config.php');
require_login();

global $USER, $DB, $CFG;

echo "<h2>Database Debug Information</h2>";

// Check if user is company manager
$companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
if ($companymanagerrole) {
    $context = context_system::instance();
    $is_company_manager = user_has_role_assignment($USER->id, $companymanagerrole->id, $context->id);
    echo "<p><strong>Is Company Manager:</strong> " . ($is_company_manager ? 'YES' : 'NO') . "</p>";
    
    if ($is_company_manager) {
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
            
            if ($company_info) {
                echo "<p><strong>Company ID:</strong> " . $company_info->id . "</p>";
                echo "<p><strong>Company Name:</strong> " . $company_info->name . "</p>";
                
                $company_id = $company_info->id;
                
                // Check available tables
                echo "<h3>Available Tables:</h3>";
                $tables = $DB->get_tables();
                $relevant_tables = array_filter($tables, function($table) {
                    return strpos($table, 'company') !== false || 
                           strpos($table, 'user') !== false || 
                           strpos($table, 'role') !== false || 
                           strpos($table, 'course') !== false ||
                           strpos($table, 'enrol') !== false;
                });
                
                foreach ($relevant_tables as $table) {
                    echo "- " . $table . "<br>";
                }
                
                // Check roles
                echo "<h3>Available Roles:</h3>";
                $roles = $DB->get_records('role');
                foreach ($roles as $role) {
                    echo "- " . $role->shortname . " (ID: " . $role->id . ")<br>";
                }
                
                // Check company users
                echo "<h3>Company Users:</h3>";
                $company_users = $DB->get_records('company_users', ['companyid' => $company_id]);
                echo "Total company users: " . count($company_users) . "<br>";
                
                if (count($company_users) > 0) {
                    echo "<table border='1'>";
                    echo "<tr><th>User ID</th><th>Manager Type</th><th>Username</th><th>First Name</th><th>Last Name</th></tr>";
                    
                    foreach ($company_users as $cu) {
                        $user = $DB->get_record('user', ['id' => $cu->userid]);
                        if ($user) {
                            echo "<tr>";
                            echo "<td>" . $cu->userid . "</td>";
                            echo "<td>" . $cu->managertype . "</td>";
                            echo "<td>" . $user->username . "</td>";
                            echo "<td>" . $user->firstname . "</td>";
                            echo "<td>" . $user->lastname . "</td>";
                            echo "</tr>";
                        }
                    }
                    echo "</table>";
                }
                
                // Check role assignments for company users
                echo "<h3>Role Assignments for Company Users:</h3>";
                $role_assignments = $DB->get_records_sql(
                    "SELECT ra.*, r.shortname as role_shortname, u.username, u.firstname, u.lastname
                     FROM {role_assignments} ra
                     JOIN {role} r ON ra.roleid = r.id
                     JOIN {user} u ON ra.userid = u.id
                     JOIN {company_users} cu ON u.id = cu.userid
                     WHERE cu.companyid = ?",
                    [$company_id]
                );
                
                echo "Total role assignments: " . count($role_assignments) . "<br>";
                if (count($role_assignments) > 0) {
                    echo "<table border='1'>";
                    echo "<tr><th>User</th><th>Role</th><th>Context</th></tr>";
                    foreach ($role_assignments as $ra) {
                        echo "<tr>";
                        echo "<td>" . $ra->firstname . " " . $ra->lastname . " (" . $ra->username . ")</td>";
                        echo "<td>" . $ra->role_shortname . "</td>";
                        echo "<td>" . $ra->contextid . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                // Test teacher count query
                echo "<h3>Teacher Count Test:</h3>";
                $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
                if ($teacher_role) {
                    echo "Teacher role found: ID " . $teacher_role->id . "<br>";
                    
                    $total_teachers = $DB->count_records_sql(
                        "SELECT COUNT(DISTINCT u.id) 
                         FROM {user} u 
                         JOIN {company_users} cu ON u.id = cu.userid 
                         JOIN {role_assignments} ra ON u.id = ra.userid 
                         JOIN {role} r ON ra.roleid = r.id 
                         WHERE cu.companyid = ? AND r.shortname = 'teacher' AND u.deleted = 0",
                        [$company_id]
                    );
                    
                    echo "Total teachers found: " . $total_teachers . "<br>";
                    
                    // Debug the query step by step
                    $step1 = $DB->count_records('company_users', ['companyid' => $company_id]);
                    echo "Step 1 - Company users: " . $step1 . "<br>";
                    
                    $step2 = $DB->count_records_sql(
                        "SELECT COUNT(DISTINCT u.id) 
                         FROM {user} u 
                         JOIN {company_users} cu ON u.id = cu.userid 
                         WHERE cu.companyid = ? AND u.deleted = 0",
                        [$company_id]
                    );
                    echo "Step 2 - Active users in company: " . $step2 . "<br>";
                    
                    $step3 = $DB->count_records_sql(
                        "SELECT COUNT(DISTINCT u.id) 
                         FROM {user} u 
                         JOIN {company_users} cu ON u.id = cu.userid 
                         JOIN {role_assignments} ra ON u.id = ra.userid 
                         WHERE cu.companyid = ? AND u.deleted = 0",
                        [$company_id]
                    );
                    echo "Step 3 - Users with role assignments: " . $step3 . "<br>";
                    
                } else {
                    echo "Teacher role not found!<br>";
                }
                
                // Check courses
                if ($DB->get_manager()->table_exists('company_course')) {
                    echo "<h3>Company Courses:</h3>";
                    $company_courses = $DB->get_records('company_course', ['companyid' => $company_id]);
                    echo "Total courses assigned to company: " . count($company_courses) . "<br>";
                    
                    if (count($company_courses) > 0) {
                        echo "<table border='1'>";
                        echo "<tr><th>Course ID</th><th>Course Name</th></tr>";
                        foreach ($company_courses as $cc) {
                            $course = $DB->get_record('course', ['id' => $cc->courseid]);
                            if ($course) {
                                echo "<tr>";
                                echo "<td>" . $cc->courseid . "</td>";
                                echo "<td>" . $course->fullname . "</td>";
                                echo "</tr>";
                            }
                        }
                        echo "</table>";
                    }
                } else {
                    echo "Company course table does not exist!<br>";
                }
                
            } else {
                echo "<p>No company information found for this user!</p>";
            }
        } else {
            echo "<p>Company tables do not exist!</p>";
        }
    }
} else {
    echo "<p>Company manager role not found!</p>";
}

echo "<hr>";
echo "<p><a href='school_manager_dashboard.php'>Back to Dashboard</a></p>";
?>

