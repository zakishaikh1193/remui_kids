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
    
    echo "<h2>Associate Teachers with Company: " . $company_info->name . "</h2>";
    echo "<p><strong>Company ID:</strong> " . $company_info->id . "</p>";
    
    // Get all users with 'teacher' role
    $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
    if (!$teacher_role) {
        die("Teacher role not found!");
    }
    
    echo "<p><strong>Teacher Role ID:</strong> " . $teacher_role->id . "</p>";
    
    // Get all teachers
    $teachers = $DB->get_records_sql(
        "SELECT DISTINCT u.*
         FROM {user} u
         JOIN {role_assignments} ra ON u.id = ra.userid
         WHERE ra.roleid = ? AND u.deleted = 0
         ORDER BY u.firstname, u.lastname",
        [$teacher_role->id]
    );
    
    echo "<h3>Found " . count($teachers) . " teachers in the system:</h3>";
    
    if (count($teachers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Already in Company</th><th>Action</th></tr>";
        
        foreach ($teachers as $teacher) {
            // Check if teacher is already in company
            $in_company = $DB->get_record('company_users', ['userid' => $teacher->id, 'companyid' => $company_info->id]);
            $already_in_company = $in_company ? 'YES' : 'NO';
            
            echo "<tr>";
            echo "<td>" . $teacher->id . "</td>";
            echo "<td>" . $teacher->username . "</td>";
            echo "<td>" . $teacher->firstname . " " . $teacher->lastname . "</td>";
            echo "<td>" . $teacher->email . "</td>";
            echo "<td>" . $already_in_company . "</td>";
            echo "<td>";
            
            if ($in_company) {
                echo "<span style='color: green;'>Already Associated</span>";
            } else {
                echo "<a href='?action=associate&userid=" . $teacher->id . "' style='color: blue;'>Associate with Company</a>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Handle association action
        if (isset($_GET['action']) && $_GET['action'] == 'associate' && isset($_GET['userid'])) {
            $userid = intval($_GET['userid']);
            
            // Verify the user exists and has teacher role
            $teacher = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
            $has_teacher_role = $DB->record_exists('role_assignments', ['userid' => $userid, 'roleid' => $teacher_role->id]);
            
            if ($teacher && $has_teacher_role) {
                // Add user to company
                $company_user = new stdClass();
                $company_user->userid = $userid;
                $company_user->companyid = $company_info->id;
                $company_user->managertype = 0; // 0 = regular user, 1 = manager
                $company_user->timecreated = time();
                $company_user->timemodified = time();
                
                $result = $DB->insert_record('company_users', $company_user);
                
                if ($result) {
                    echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
                    echo "<strong>Success!</strong> Teacher " . $teacher->firstname . " " . $teacher->lastname . " has been associated with the company.";
                    echo "</div>";
                    echo "<script>setTimeout(function(){ window.location.href = 'associate_teachers.php'; }, 2000);</script>";
                } else {
                    echo "<div style='background: lightcoral; padding: 10px; margin: 10px 0;'>";
                    echo "<strong>Error!</strong> Failed to associate teacher with company.";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: lightcoral; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Error!</strong> Invalid teacher or teacher role not found.";
                echo "</div>";
            }
        }
        
        // Bulk associate all teachers
        echo "<hr>";
        echo "<h3>Bulk Actions:</h3>";
        echo "<p><a href='?action=associate_all' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Associate ALL Teachers with Company</a></p>";
        
        if (isset($_GET['action']) && $_GET['action'] == 'associate_all') {
            $associated_count = 0;
            $already_associated_count = 0;
            
            foreach ($teachers as $teacher) {
                // Check if already in company
                $in_company = $DB->get_record('company_users', ['userid' => $teacher->id, 'companyid' => $company_info->id]);
                
                if (!$in_company) {
                    $company_user = new stdClass();
                    $company_user->userid = $teacher->id;
                    $company_user->companyid = $company_info->id;
                    $company_user->managertype = 0;
                    $company_user->timecreated = time();
                    $company_user->timemodified = time();
                    
                    if ($DB->insert_record('company_users', $company_user)) {
                        $associated_count++;
                    }
                } else {
                    $already_associated_count++;
                }
            }
            
            echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Bulk Association Complete!</strong><br>";
            echo "New associations: " . $associated_count . "<br>";
            echo "Already associated: " . $already_associated_count . "<br>";
            echo "Total teachers in company: " . ($associated_count + $already_associated_count);
            echo "</div>";
            echo "<script>setTimeout(function(){ window.location.href = 'associate_teachers.php'; }, 3000);</script>";
        }
        
    } else {
        echo "<p>No teachers found in the system!</p>";
    }
    
    // Show current company statistics
    echo "<hr>";
    echo "<h3>Current Company Statistics:</h3>";
    
    $total_company_users = $DB->count_records('company_users', ['companyid' => $company_info->id]);
    $company_managers = $DB->count_records('company_users', ['companyid' => $company_info->id, 'managertype' => 1]);
    $company_teachers = $DB->count_records('company_users', ['companyid' => $company_info->id, 'managertype' => 0]);
    
    echo "<p><strong>Total Company Users:</strong> " . $total_company_users . "</p>";
    echo "<p><strong>Company Managers:</strong> " . $company_managers . "</p>";
    echo "<p><strong>Company Teachers:</strong> " . $company_teachers . "</p>";
    
    // Test the dashboard query
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
    
    echo "<p><strong>Teachers that will show in dashboard:</strong> " . $total_teachers_in_dashboard . "</p>";
    
} else {
    echo "<p>Company tables do not exist!</p>";
}

echo "<hr>";
echo "<p><a href='school_manager_dashboard.php'>Back to Dashboard</a></p>";
?>

