<?php
/**
 * Check Enrollments - Detailed analysis of why student count is 0
 */

require_once('../../../config.php');
global $DB;

echo "<h2>Enrollment Analysis - Why Student Count is 0</h2>";
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
    .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
    .button:hover { background: #0056b3; }
</style>";

try {
    // 1. Check total users
    echo "<div class='result'>";
    echo "<h3>1. User Statistics</h3>";
    
    $total_users = $DB->count_records('user');
    $active_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
    $admin_users = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id) FROM {user} u JOIN {role_assignments} ra ON u.id = ra.userid JOIN {role} r ON ra.roleid = r.id WHERE r.shortname = 'manager' AND u.deleted = 0",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
    echo "<tr><td>Total Users</td><td>$total_users</td><td>All users in database</td></tr>";
    echo "<tr><td>Active Users</td><td>$active_users</td><td>Not deleted or suspended</td></tr>";
    echo "<tr><td>Admin Users</td><td>$admin_users</td><td>Users with manager role</td></tr>";
    echo "</table>";
    echo "</div>";

    // 2. Check courses
    echo "<div class='result'>";
    echo "<h3>2. Course Statistics</h3>";
    
    $total_courses = $DB->count_records('course');
    $visible_courses = $DB->count_records('course', ['visible' => 1]);
    $dashboard_courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
    echo "<tr><td>Total Courses</td><td>$total_courses</td><td>All courses in database</td></tr>";
    echo "<tr><td>Visible Courses</td><td>$visible_courses</td><td>Courses that are not hidden</td></tr>";
    echo "<tr><td>Dashboard Courses</td><td>$dashboard_courses</td><td>Visible courses (excluding site course)</td></tr>";
    echo "</table>";
    echo "</div>";

    // 3. Check enrollment methods
    echo "<div class='result'>";
    echo "<h3>3. Enrollment Methods</h3>";
    
    $enrollment_methods = $DB->get_records_sql(
        "SELECT 
            e.enrol,
            COUNT(*) as count,
            COUNT(CASE WHEN e.status = 0 THEN 1 END) as active_count
         FROM {enrol} e
         GROUP BY e.enrol
         ORDER BY count DESC",
        []
    );
    
    if (!empty($enrollment_methods)) {
        echo "<table>";
        echo "<tr><th>Enrollment Method</th><th>Total</th><th>Active</th><th>Description</th></tr>";
        foreach ($enrollment_methods as $method) {
            $description = match($method->enrol) {
                'manual' => 'Manual enrollment by teachers/admins',
                'self' => 'Self-enrollment (students can enroll themselves)',
                'guest' => 'Guest access (no enrollment needed)',
                'cohort' => 'Cohort-based enrollment',
                'database' => 'External database enrollment',
                'ldap' => 'LDAP enrollment',
                'flatfile' => 'CSV file enrollment',
                default => 'Other enrollment method'
            };
            echo "<tr>";
            echo "<td><strong>{$method->enrol}</strong></td>";
            echo "<td>{$method->count}</td>";
            echo "<td>{$method->active_count}</td>";
            echo "<td>$description</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>⚠️ No Enrollment Methods Found</h4>";
        echo "<p>No enrollment methods are configured for any courses.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 4. Check actual enrollments
    echo "<div class='result'>";
    echo "<h3>4. Actual Enrollments</h3>";
    
    $total_enrollments = $DB->count_records('user_enrolments');
    $active_enrollments = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.status = 0",
        []
    );
    $enrolled_users = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT ue.userid)
         FROM {user_enrolments} ue
         JOIN {user} u ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Description</th></tr>";
    echo "<tr><td>Total Enrollments</td><td>$total_enrollments</td><td>All enrollment records</td></tr>";
    echo "<tr><td>Active Enrollments</td><td>$active_enrollments</td><td>Enrollments in active methods</td></tr>";
    echo "<tr><td>Enrolled Users (Students)</td><td>$enrolled_users</td><td>Unique users enrolled in courses</td></tr>";
    echo "</table>";
    echo "</div>";

    // 5. Check courses with enrollments
    echo "<div class='result'>";
    echo "<h3>5. Courses with Enrollments</h3>";
    
    $courses_with_enrollments = $DB->get_records_sql(
        "SELECT 
            c.id,
            c.fullname,
            c.shortname,
            c.visible,
            COUNT(DISTINCT ue.userid) as enrolled_students,
            COUNT(ue.id) as total_enrollments
         FROM {course} c
         LEFT JOIN {enrol} e ON c.id = e.courseid AND e.status = 0
         LEFT JOIN {user_enrolments} ue ON e.id = ue.enrolid
         LEFT JOIN {user} u ON ue.userid = u.id AND u.deleted = 0 AND u.suspended = 0
         WHERE c.visible = 1 AND c.id > 1
         GROUP BY c.id, c.fullname, c.shortname, c.visible
         ORDER BY enrolled_students DESC",
        []
    );
    
    if (!empty($courses_with_enrollments)) {
        echo "<table>";
        echo "<tr><th>Course ID</th><th>Course Name</th><th>Short Name</th><th>Visible</th><th>Enrolled Students</th><th>Total Enrollments</th></tr>";
        foreach ($courses_with_enrollments as $course) {
            $class = $course->enrolled_students > 0 ? 'style="background-color: #d4edda;"' : '';
            echo "<tr $class>";
            echo "<td>{$course->id}</td>";
            echo "<td><strong>{$course->fullname}</strong></td>";
            echo "<td>{$course->shortname}</td>";
            echo "<td>" . ($course->visible ? 'Yes' : 'No') . "</td>";
            echo "<td><strong>{$course->enrolled_students}</strong></td>";
            echo "<td>{$course->total_enrollments}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>⚠️ No Courses with Enrollments</h4>";
        echo "<p>No courses have any enrolled students.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 6. Solutions
    echo "<div class='result'>";
    echo "<h3>6. Why Student Count is 0 & Solutions</h3>";
    
    if ($enrolled_users == 0) {
        echo "<div class='warning'>";
        echo "<h4>🔍 Analysis: Why Student Count is 0</h4>";
        echo "<ul>";
        
        if ($active_users == 0) {
            echo "<li><strong>No Active Users:</strong> There are no active users in the system</li>";
        } elseif ($dashboard_courses == 0) {
            echo "<li><strong>No Courses:</strong> There are no visible courses to enroll in</li>";
        } elseif ($total_enrollments == 0) {
            echo "<li><strong>No Enrollments:</strong> Users exist and courses exist, but no enrollments have been made</li>";
        } else {
            echo "<li><strong>No Active Enrollments:</strong> Enrollments exist but enrollment methods are disabled</li>";
        }
        
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='success'>";
        echo "<h4>✅ Solutions to Get Student Count > 0</h4>";
        
        if ($active_users == 0) {
            echo "<h5>1. Create Users</h5>";
            echo "<p>You need to create some users first:</p>";
            echo "<ul>";
            echo "<li>Go to <strong>Site Administration → Users → Accounts → Add a new user</strong></li>";
            echo "<li>Create some test users</li>";
            echo "<li>Make sure they are not suspended or deleted</li>";
            echo "</ul>";
        }
        
        if ($dashboard_courses == 0) {
            echo "<h5>2. Create Courses</h5>";
            echo "<p>You need to create some courses:</p>";
            echo "<ul>";
            echo "<li>Go to <strong>Site Administration → Courses → Add a new course</strong></li>";
            echo "<li>Create some test courses</li>";
            echo "<li>Make sure they are visible</li>";
            echo "</ul>";
        }
        
        if ($active_users > 0 && $dashboard_courses > 0) {
            echo "<h5>3. Enable Enrollment Methods</h5>";
            echo "<p>Enable enrollment methods for your courses:</p>";
            echo "<ul>";
            echo "<li>Go to <strong>Site Administration → Plugins → Enrolments → Manage enrol plugins</strong></li>";
            echo "<li>Enable <strong>Manual enrolment</strong> (for admin enrollment)</li>";
            echo "<li>Enable <strong>Self enrolment</strong> (for student self-enrollment)</li>";
            echo "</ul>";
            
            echo "<h5>4. Enroll Users in Courses</h5>";
            echo "<p>Enroll users in courses:</p>";
            echo "<ul>";
            echo "<li>Go to a course</li>";
            echo "<li>Click <strong>Participants</strong> in the course menu</li>";
            echo "<li>Click <strong>Enrol users</strong></li>";
            echo "<li>Select users and enroll them</li>";
            echo "</ul>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>🎉 Great! Student Count is Working</h4>";
        echo "<p>Your system has <strong>$enrolled_users enrolled students</strong>.</p>";
        echo "<p>The dashboard should be showing this count correctly.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 7. Quick test enrollment
    if ($active_users > 0 && $dashboard_courses > 0 && $enrolled_users == 0) {
        echo "<div class='result'>";
        echo "<h3>7. Quick Test - Create Sample Enrollment</h3>";
        echo "<p>If you want to test the student count quickly, I can help you create a sample enrollment.</p>";
        echo "<p><strong>Note:</strong> This is for testing purposes only.</p>";
        
        echo "<form method='post'>";
        echo "<button type='submit' name='create_test_enrollment' class='button'>Create Test Enrollment</button>";
        echo "</form>";
        echo "</div>";
        
        if (isset($_POST['create_test_enrollment'])) {
            try {
                // Get first active user
                $user = $DB->get_record('user', ['deleted' => 0, 'suspended' => 0], '*', IGNORE_MULTIPLE);
                
                // Get first visible course
                $course = $DB->get_record('course', ['visible' => 1, 'id' => ['>' => 1]], '*', IGNORE_MULTIPLE);
                
                if ($user && $course) {
                    // Check if manual enrollment method exists
                    $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual', 'status' => 0]);
                    
                    if (!$enrol) {
                        // Create manual enrollment method
                        $enrol = new stdClass();
                        $enrol->courseid = $course->id;
                        $enrol->enrol = 'manual';
                        $enrol->status = 0;
                        $enrol->timecreated = time();
                        $enrol->timemodified = time();
                        $enrol->id = $DB->insert_record('enrol', $enrol);
                    }
                    
                    // Check if user is already enrolled
                    $existing = $DB->get_record('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user->id]);
                    
                    if (!$existing) {
                        // Create enrollment
                        $enrollment = new stdClass();
                        $enrollment->enrolid = $enrol->id;
                        $enrollment->userid = $user->id;
                        $enrollment->timestart = time();
                        $enrollment->timeend = 0;
                        $enrollment->modifierid = 2; // Admin user
                        $enrollment->timecreated = time();
                        $enrollment->timemodified = time();
                        $enrollment->id = $DB->insert_record('user_enrolments', $enrollment);
                        
                        echo "<div class='success'>";
                        echo "<h4>✅ Test Enrollment Created!</h4>";
                        echo "<p>User <strong>{$user->firstname} {$user->lastname}</strong> has been enrolled in course <strong>{$course->fullname}</strong>.</p>";
                        echo "<p>Your student count should now be 1. Refresh your dashboard to see the update!</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='issue'>";
                        echo "<p>User is already enrolled in this course.</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='issue'>";
                    echo "<p>Could not find suitable user or course for test enrollment.</p>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='issue'>";
                echo "<p>Error creating test enrollment: " . $e->getMessage() . "</p>";
                echo "</div>";
            }
        }
    }

} catch (Exception $e) {
    echo "<div class='issue'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



