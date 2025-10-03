<?php
/**
 * School Count Verification Tool
 * Run this to verify if your dashboard count is correct
 */

require_once('../../../config.php');

global $DB;

echo "<h2>School Count Verification</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
    .issue { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .counted { background-color: #d4edda; }
    .not-counted { background-color: #f8d7da; }
</style>";

try {
    // 1. Dashboard counts (what should be displayed)
    $dashboard_schools = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 AND id > 1 AND parent = 0",
        []
    );
    
    $dashboard_courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    // Get student role
    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    $dashboard_students = 0;
    if ($studentrole) {
        $dashboard_students = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $studentrole->id]
        );
    }
    
    echo "<div class='result'>";
    echo "<h3>🎯 Dashboard Counts</h3>";
    echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
    echo "<div style='text-align: center; padding: 15px; background: #e7f3ff; border-radius: 8px; min-width: 150px;'>";
    echo "<div class='count'>$dashboard_schools</div>";
    echo "<div><strong>TOTAL SCHOOLS</strong></div>";
    echo "</div>";
    echo "<div style='text-align: center; padding: 15px; background: #e7f3ff; border-radius: 8px; min-width: 150px;'>";
    echo "<div class='count'>$dashboard_courses</div>";
    echo "<div><strong>TOTAL COURSES</strong></div>";
    echo "</div>";
    echo "<div style='text-align: center; padding: 15px; background: #e7f3ff; border-radius: 8px; min-width: 150px;'>";
    echo "<div class='count'>$dashboard_students</div>";
    echo "<div><strong>TOTAL STUDENTS</strong></div>";
    echo "</div>";
    echo "</div>";
    echo "<p>These are the numbers that should appear in your dashboard cards.</p>";
    echo "</div>";
    
    // 2. Detailed breakdown
    echo "<div class='result'>";
    echo "<h3>📊 Detailed Breakdown</h3>";
    
    $total_categories = $DB->count_records('course_categories');
    $visible_categories = $DB->count_records('course_categories', ['visible' => 1]);
    $top_level_categories = $DB->count_records('course_categories', ['parent' => 0]);
    $excluded_system = $DB->count_records('course_categories', ['id' => 1]);
    
    $total_courses = $DB->count_records('course');
    $visible_courses = $DB->count_records('course', ['visible' => 1]);
    $site_course = $DB->count_records('course', ['id' => 1]);
    
    $total_users = $DB->count_records('user', ['deleted' => 0]);
    $suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
    $active_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Explanation</th></tr>";
    echo "<tr><td colspan='3'><strong>SCHOOLS</strong></td></tr>";
    echo "<tr><td>Total Categories</td><td>$total_categories</td><td>All categories in database</td></tr>";
    echo "<tr><td>Visible Categories</td><td>$visible_categories</td><td>Categories that are not hidden</td></tr>";
    echo "<tr><td>Top Level Categories</td><td>$top_level_categories</td><td>Categories with parent = 0</td></tr>";
    echo "<tr><td>System Category (ID=1)</td><td>$excluded_system</td><td>Excluded from count (id > 1)</td></tr>";
    echo "<tr class='counted'><td><strong>Dashboard Schools</strong></td><td><strong>$dashboard_schools</strong></td><td><strong>Final count shown in dashboard</strong></td></tr>";
    
    echo "<tr><td colspan='3'><strong>COURSES</strong></td></tr>";
    echo "<tr><td>Total Courses</td><td>$total_courses</td><td>All courses in database</td></tr>";
    echo "<tr><td>Visible Courses</td><td>$visible_courses</td><td>Courses that are not hidden</td></tr>";
    echo "<tr><td>Site Course (ID=1)</td><td>$site_course</td><td>Excluded from count (id > 1)</td></tr>";
    echo "<tr class='counted'><td><strong>Dashboard Courses</strong></td><td><strong>$dashboard_courses</strong></td><td><strong>Final count shown in dashboard</strong></td></tr>";
    
    echo "<tr><td colspan='3'><strong>STUDENTS</strong></td></tr>";
    echo "<tr><td>Total Users</td><td>$total_users</td><td>All users (not deleted)</td></tr>";
    echo "<tr><td>Active Users</td><td>$active_users</td><td>Users not suspended</td></tr>";
    echo "<tr><td>Suspended Users</td><td>$suspended_users</td><td>Excluded from count</td></tr>";
    echo "<tr class='counted'><td><strong>Dashboard Students</strong></td><td><strong>$dashboard_students</strong></td><td><strong>Users with student role (system level)</strong></td></tr>";
    echo "</table>";
    echo "</div>";
    
    // 3. School categories being counted
    echo "<div class='result'>";
    echo "<h3>🏫 School Categories Being Counted</h3>";
    
    $school_categories = $DB->get_records_sql(
        "SELECT id, name, description FROM {course_categories} 
         WHERE visible = 1 AND id > 1 AND parent = 0 
         ORDER BY name",
        []
    );
    
    if (!empty($school_categories)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>School Name</th><th>Description</th></tr>";
        foreach ($school_categories as $school) {
            echo "<tr class='counted'>";
            echo "<td>{$school->id}</td>";
            echo "<td><strong>{$school->name}</strong></td>";
            echo "<td>" . ($school->description ?: 'No description') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='issue'>";
        echo "<h4>⚠️ No School Categories Found</h4>";
        echo "<p>This means all your categories are either:</p>";
        echo "<ul>";
        echo "<li>Sub-categories (parent > 0)</li>";
        echo "<li>Hidden (visible = 0)</li>";
        echo "<li>System category (id = 1)</li>";
        echo "</ul>";
        echo "</div>";
    }
    echo "</div>";
    
    // 4. Courses per school
    echo "<div class='result'>";
    echo "<h3>📚 Courses per School</h3>";
    
    $courses_per_school = $DB->get_records_sql(
        "SELECT 
            cc.id,
            cc.name as school_name,
            COUNT(c.id) as total_courses,
            COUNT(CASE WHEN c.visible = 1 THEN 1 END) as visible_courses
         FROM {course_categories} cc
         LEFT JOIN {course} c ON cc.id = c.category
         WHERE cc.visible = 1 AND cc.id > 1 AND cc.parent = 0
         GROUP BY cc.id, cc.name
         ORDER BY total_courses DESC",
        []
    );
    
    if (!empty($courses_per_school)) {
        echo "<table>";
        echo "<tr><th>School Name</th><th>Total Courses</th><th>Visible Courses</th></tr>";
        foreach ($courses_per_school as $school) {
            echo "<tr>";
            echo "<td>{$school->school_name}</td>";
            echo "<td>{$school->total_courses}</td>";
            echo "<td>{$school->visible_courses}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 5. All courses breakdown
    echo "<div class='result'>";
    echo "<h3>📖 All Courses Breakdown</h3>";
    
    $all_courses = $DB->get_records_sql(
        "SELECT 
            c.id,
            c.fullname,
            c.shortname,
            c.visible,
            cc.name as category_name,
            CASE 
                WHEN c.id = 1 THEN 'Site Course (Excluded)'
                WHEN c.visible = 0 THEN 'Hidden'
                ELSE 'Counted'
            END as status
         FROM {course} c
         LEFT JOIN {course_categories} cc ON c.category = cc.id
         ORDER BY c.id",
        []
    );
    
    if (!empty($all_courses)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Course Name</th><th>Category</th><th>Visible</th><th>Status</th></tr>";
        foreach ($all_courses as $course) {
            $class = ($course->status == 'Counted') ? 'counted' : 'not-counted';
            echo "<tr class='$class'>";
            echo "<td>{$course->id}</td>";
            echo "<td>{$course->fullname}</td>";
            echo "<td>{$course->category_name}</td>";
            echo "<td>" . ($course->visible ? 'Yes' : 'No') . "</td>";
            echo "<td><strong>{$course->status}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 6. Students breakdown
    echo "<div class='result'>";
    echo "<h3>👥 Students Breakdown</h3>";
    
    if ($studentrole) {
        $students = $DB->get_records_sql(
            "SELECT 
                u.id,
                u.username,
                u.firstname,
                u.lastname,
                u.email,
                u.suspended,
                u.deleted,
                CASE 
                    WHEN u.deleted = 1 THEN 'Deleted'
                    WHEN u.suspended = 1 THEN 'Suspended'
                    ELSE 'Active'
                END as status
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ?
             ORDER BY u.firstname, u.lastname",
            [CONTEXT_SYSTEM, $studentrole->id]
        );
        
        if (!empty($students)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th></tr>";
            foreach ($students as $student) {
                $class = ($student->status == 'Active') ? 'counted' : 'not-counted';
                echo "<tr class='$class'>";
                echo "<td>{$student->id}</td>";
                echo "<td>{$student->firstname} {$student->lastname}</td>";
                echo "<td>{$student->username}</td>";
                echo "<td>{$student->email}</td>";
                echo "<td><strong>{$student->status}</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='issue'>";
            echo "<h4>⚠️ No Students Found</h4>";
            echo "<p>No users have been assigned the 'student' role at the system level.</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='issue'>";
        echo "<h4>⚠️ Student Role Not Found</h4>";
        echo "<p>The 'student' role does not exist in your system.</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // 5. All categories overview
    echo "<div class='result'>";
    echo "<h3>📋 All Categories Overview</h3>";
    
    $all_categories = $DB->get_records('course_categories', [], 'parent, name', 'id,name,parent,visible');
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent</th><th>Visible</th><th>Type</th><th>Counted?</th></tr>";
    foreach ($all_categories as $cat) {
        $type = $cat->parent == 0 ? 'Top Level' : 'Sub Category';
        $counted = ($cat->visible == 1 && $cat->id > 1 && $cat->parent == 0) ? 'YES' : 'NO';
        $class = ($counted == 'YES') ? 'counted' : 'not-counted';
        
        echo "<tr class='$class'>";
        echo "<td>{$cat->id}</td>";
        echo "<td>{$cat->name}</td>";
        echo "<td>{$cat->parent}</td>";
        echo "<td>" . ($cat->visible ? 'Yes' : 'No') . "</td>";
        echo "<td>$type</td>";
        echo "<td><strong>$counted</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 6. Verification summary
    echo "<div class='success'>";
    echo "<h3>✅ Verification Summary</h3>";
    echo "<p><strong>Your dashboard should show: $dashboard_count schools</strong></p>";
    echo "<p>This count includes:</p>";
    echo "<ul>";
    echo "<li>✅ Visible categories (visible = 1)</li>";
    echo "<li>✅ Top-level categories (parent = 0)</li>";
    echo "<li>✅ Non-system categories (id > 1)</li>";
    echo "</ul>";
    echo "<p>This count excludes:</p>";
    echo "<ul>";
    echo "<li>❌ Hidden categories (visible = 0)</li>";
    echo "<li>❌ Sub-categories (parent > 0)</li>";
    echo "<li>❌ System category (id = 1)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='issue'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
