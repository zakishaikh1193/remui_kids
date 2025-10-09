<?php
require_once('../../config.php');
require_login();

global $USER, $DB;

// Check if user is site admin
if (!is_siteadmin()) {
    die("This tool requires Super Admin access!");
}

echo "<h2>Super Admin - Associate Teachers with Companies</h2>";
echo "<p><strong>Current User:</strong> " . fullname($USER) . " (Site Admin)</p>";

// Get all companies
$companies = $DB->get_records('company');
echo "<h3>Available Companies:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Company ID</th><th>Company Name</th><th>Short Name</th><th>Current Teachers</th><th>Actions</th></tr>";

foreach ($companies as $company) {
    // Count current teachers in company
    $teacher_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id) 
         FROM {user} u 
         JOIN {company_users} cu ON u.id = cu.userid 
         JOIN {role_assignments} ra ON u.id = ra.userid 
         JOIN {role} r ON ra.roleid = r.id 
         WHERE cu.companyid = ? AND r.shortname = 'teacher' AND u.deleted = 0",
        [$company->id]
    );
    
    echo "<tr>";
    echo "<td>" . $company->id . "</td>";
    echo "<td>" . $company->name . "</td>";
    echo "<td>" . $company->shortname . "</td>";
    echo "<td>" . $teacher_count . "</td>";
    echo "<td><a href='?action=manage&companyid=" . $company->id . "'>Manage Teachers</a></td>";
    echo "</tr>";
}
echo "</table>";

// Handle company management
if (isset($_GET['action']) && $_GET['action'] == 'manage' && isset($_GET['companyid'])) {
    $company_id = intval($_GET['companyid']);
    $company = $DB->get_record('company', ['id' => $company_id]);
    
    if ($company) {
        echo "<hr>";
        echo "<h3>Managing Teachers for: " . $company->name . "</h3>";
        
        // Get all teachers
        $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
        if ($teacher_role) {
            $teachers = $DB->get_records_sql(
                "SELECT DISTINCT u.*
                 FROM {user} u
                 JOIN {role_assignments} ra ON u.id = ra.userid
                 WHERE ra.roleid = ? AND u.deleted = 0
                 ORDER BY u.firstname, u.lastname",
                [$teacher_role->id]
            );
            
            echo "<h4>All Teachers in System (" . count($teachers) . " found):</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Grade</th><th>In Company</th><th>Action</th></tr>";
            
            foreach ($teachers as $teacher) {
                // Check if teacher is in this company
                $in_company = $DB->get_record('company_users', ['userid' => $teacher->id, 'companyid' => $company_id]);
                
                // Try to get grade information (if stored in custom field)
                $grade = "N/A";
                $grade_field = $DB->get_record_sql(
                    "SELECT f.*, d.data
                     FROM {user_info_field} f
                     LEFT JOIN {user_info_data} d ON f.id = d.fieldid AND d.userid = ?
                     WHERE f.shortname = 'grade' OR f.name LIKE '%grade%'
                     LIMIT 1",
                    [$teacher->id]
                );
                
                if ($grade_field && !empty($grade_field->data)) {
                    $grade = $grade_field->data;
                }
                
                echo "<tr>";
                echo "<td>" . $teacher->id . "</td>";
                echo "<td>" . $teacher->username . "</td>";
                echo "<td>" . $teacher->firstname . " " . $teacher->lastname . "</td>";
                echo "<td>" . $teacher->email . "</td>";
                echo "<td>" . $grade . "</td>";
                echo "<td>" . ($in_company ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</td>";
                echo "<td>";
                
                if ($in_company) {
                    echo "<span style='color: green;'>Already in Company</span>";
                } else {
                    echo "<a href='?action=add_teacher&companyid=" . $company_id . "&teacherid=" . $teacher->id . "' style='color: blue;'>Add to Company</a>";
                }
                
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Bulk actions
            echo "<hr>";
            echo "<h4>Bulk Actions:</h4>";
            echo "<p><a href='?action=add_all_teachers&companyid=" . $company_id . "' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add ALL Teachers to This Company</a></p>";
            echo "<p><a href='?action=add_grade_teachers&companyid=" . $company_id . "' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add Teachers by Grade (1-10)</a></p>";
        }
    }
}

// Handle adding individual teacher
if (isset($_GET['action']) && $_GET['action'] == 'add_teacher' && isset($_GET['companyid']) && isset($_GET['teacherid'])) {
    $company_id = intval($_GET['companyid']);
    $teacher_id = intval($_GET['teacherid']);
    
    $company = $DB->get_record('company', ['id' => $company_id]);
    $teacher = $DB->get_record('user', ['id' => $teacher_id]);
    
    if ($company && $teacher) {
        // Check if already in company
        $existing = $DB->get_record('company_users', ['userid' => $teacher_id, 'companyid' => $company_id]);
        
        if (!$existing) {
            $company_user = new stdClass();
            $company_user->userid = $teacher_id;
            $company_user->companyid = $company_id;
            $company_user->managertype = 0; // Regular user, not manager
            $company_user->timecreated = time();
            $company_user->timemodified = time();
            
            if ($DB->insert_record('company_users', $company_user)) {
                echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Success!</strong> Teacher " . $teacher->firstname . " " . $teacher->lastname . " has been added to company " . $company->name . ".";
                echo "</div>";
            } else {
                echo "<div style='background: lightcoral; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Error!</strong> Failed to add teacher to company.";
                echo "</div>";
            }
        } else {
            echo "<div style='background: lightyellow; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Info:</strong> Teacher is already in this company.";
            echo "</div>";
        }
        
        echo "<script>setTimeout(function(){ window.location.href = 'super_admin_associate_teachers.php?action=manage&companyid=" . $company_id . "'; }, 2000);</script>";
    }
}

// Handle adding all teachers
if (isset($_GET['action']) && $_GET['action'] == 'add_all_teachers' && isset($_GET['companyid'])) {
    $company_id = intval($_GET['companyid']);
    $company = $DB->get_record('company', ['id' => $company_id]);
    
    if ($company) {
        $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
        if ($teacher_role) {
            $teachers = $DB->get_records_sql(
                "SELECT DISTINCT u.*
                 FROM {user} u
                 JOIN {role_assignments} ra ON u.id = ra.userid
                 WHERE ra.roleid = ? AND u.deleted = 0",
                [$teacher_role->id]
            );
            
            $added_count = 0;
            $already_count = 0;
            
            echo "<div style='background: lightblue; padding: 15px; margin: 10px 0;'>";
            echo "<h4>Adding All Teachers to: " . $company->name . "</h4>";
            
            foreach ($teachers as $teacher) {
                $existing = $DB->get_record('company_users', ['userid' => $teacher->id, 'companyid' => $company_id]);
                
                if (!$existing) {
                    $company_user = new stdClass();
                    $company_user->userid = $teacher->id;
                    $company_user->companyid = $company_id;
                    $company_user->managertype = 0;
                    $company_user->timecreated = time();
                    $company_user->timemodified = time();
                    
                    if ($DB->insert_record('company_users', $company_user)) {
                        $added_count++;
                        echo "<p style='color: green;'>✅ Added: " . $teacher->firstname . " " . $teacher->lastname . "</p>";
                    }
                } else {
                    $already_count++;
                    echo "<p style='color: blue;'>ℹ️ Already in company: " . $teacher->firstname . " " . $teacher->lastname . "</p>";
                }
            }
            
            echo "</div>";
            echo "<div style='background: lightgreen; padding: 15px; margin: 10px 0;'>";
            echo "<h4>Summary:</h4>";
            echo "<p><strong>Added:</strong> " . $added_count . " teachers</p>";
            echo "<p><strong>Already in company:</strong> " . $already_count . " teachers</p>";
            echo "<p><strong>Total teachers in company:</strong> " . ($added_count + $already_count) . "</p>";
            echo "</div>";
            
            echo "<script>setTimeout(function(){ window.location.href = 'super_admin_associate_teachers.php'; }, 3000);</script>";
        }
    }
}

// Handle adding teachers by grade
if (isset($_GET['action']) && $_GET['action'] == 'add_grade_teachers' && isset($_GET['companyid'])) {
    $company_id = intval($_GET['companyid']);
    $company = $DB->get_record('company', ['id' => $company_id]);
    
    if ($company) {
        echo "<div style='background: lightyellow; padding: 15px; margin: 10px 0;'>";
        echo "<h4>Add Teachers by Grade to: " . $company->name . "</h4>";
        echo "<p>Select which grades to add teachers for:</p>";
        
        for ($grade = 1; $grade <= 10; $grade++) {
            echo "<p><a href='?action=add_grade&companyid=" . $company_id . "&grade=" . $grade . "' style='background: #17a2b8; color: white; padding: 5px 15px; text-decoration: none; border-radius: 3px; margin: 2px; display: inline-block;'>Grade " . $grade . "</a></p>";
        }
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='school_manager_dashboard.php'>Go to School Manager Dashboard</a> | <a href='auto_associate_teachers.php'>Auto-Associate Tool</a></p>";
?>

