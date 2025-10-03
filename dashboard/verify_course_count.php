<?php
/**
 * Course Count Verification Tool
 * Run this to verify if your dashboard course count is correct
 */

require_once('../../../config.php');

global $DB;

echo "<h2>Course Count Verification</h2>";
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
    .course-card { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #007bff; }
</style>";

try {
    // 1. Dashboard course count (what should be displayed)
    $dashboard_courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    echo "<div class='result'>";
    echo "<h3>🎯 Dashboard Course Count</h3>";
    echo "<div class='count'>$dashboard_courses</div>";
    echo "<p>This is the number that should appear in your dashboard's 'TOTAL COURSES' card.</p>";
    echo "</div>";
    
    // 2. Detailed breakdown
    echo "<div class='result'>";
    echo "<h3>📊 Course Count Breakdown</h3>";
    
    $total_courses = $DB->count_records('course');
    $visible_courses = $DB->count_records('course', ['visible' => 1]);
    $hidden_courses = $DB->count_records('course', ['visible' => 0]);
    $site_course = $DB->count_records('course', ['id' => 1]);
    $visible_non_site = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th><th>Explanation</th></tr>";
    echo "<tr><td>Total Courses</td><td>$total_courses</td><td>All courses in database</td></tr>";
    echo "<tr><td>Visible Courses</td><td>$visible_courses</td><td>Courses that are not hidden</td></tr>";
    echo "<tr><td>Hidden Courses</td><td>$hidden_courses</td><td>Courses that are hidden (excluded)</td></tr>";
    echo "<tr><td>Site Course (ID=1)</td><td>$site_course</td><td>System course (excluded from count)</td></tr>";
    echo "<tr class='counted'><td><strong>Dashboard Courses</strong></td><td><strong>$dashboard_courses</strong></td><td><strong>Final count shown in dashboard</strong></td></tr>";
    echo "</table>";
    echo "</div>";
    
    // 3. All courses list
    echo "<div class='result'>";
    echo "<h3>📖 All Courses in System</h3>";
    
    $all_courses = $DB->get_records_sql(
        "SELECT 
            c.id,
            c.fullname,
            c.shortname,
            c.visible,
            c.startdate,
            c.enddate,
            cc.name as category_name,
            CASE 
                WHEN c.id = 1 THEN 'Site Course (Excluded)'
                WHEN c.visible = 0 THEN 'Hidden (Excluded)'
                ELSE 'Counted'
            END as status
         FROM {course} c
         LEFT JOIN {course_categories} cc ON c.category = cc.id
         ORDER BY c.id",
        []
    );
    
    if (!empty($all_courses)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Course Name</th><th>Short Name</th><th>Category</th><th>Visible</th><th>Start Date</th><th>Status</th></tr>";
        foreach ($all_courses as $course) {
            $class = ($course->status == 'Counted') ? 'counted' : 'not-counted';
            $startdate = $course->startdate ? date('Y-m-d', $course->startdate) : 'Not set';
            echo "<tr class='$class'>";
            echo "<td>{$course->id}</td>";
            echo "<td><strong>{$course->fullname}</strong></td>";
            echo "<td>{$course->shortname}</td>";
            echo "<td>{$course->category_name}</td>";
            echo "<td>" . ($course->visible ? 'Yes' : 'No') . "</td>";
            echo "<td>$startdate</td>";
            echo "<td><strong>{$course->status}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 4. Courses by category
    echo "<div class='result'>";
    echo "<h3>📚 Courses by Category</h3>";
    
    $courses_by_category = $DB->get_records_sql(
        "SELECT 
            cc.id as category_id,
            cc.name as category_name,
            COUNT(c.id) as total_courses,
            COUNT(CASE WHEN c.visible = 1 THEN 1 END) as visible_courses,
            COUNT(CASE WHEN c.visible = 1 AND c.id > 1 THEN 1 END) as dashboard_courses
         FROM {course_categories} cc
         LEFT JOIN {course} c ON cc.id = c.category
         GROUP BY cc.id, cc.name
         ORDER BY dashboard_courses DESC",
        []
    );
    
    if (!empty($courses_by_category)) {
        echo "<table>";
        echo "<tr><th>Category</th><th>Total Courses</th><th>Visible Courses</th><th>Dashboard Courses</th></tr>";
        foreach ($courses_by_category as $category) {
            echo "<tr>";
            echo "<td><strong>{$category->category_name}</strong></td>";
            echo "<td>{$category->total_courses}</td>";
            echo "<td>{$category->visible_courses}</td>";
            echo "<td class='counted'><strong>{$category->dashboard_courses}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 5. Course enrollment statistics
    echo "<div class='result'>";
    echo "<h3>👥 Course Enrollment Statistics</h3>";
    
    $enrollment_stats = $DB->get_records_sql(
        "SELECT 
            c.id,
            c.fullname,
            c.visible,
            COUNT(DISTINCT ue.userid) as enrolled_users,
            COUNT(DISTINCT CASE WHEN ue.status = 0 THEN ue.userid END) as active_enrollments
         FROM {course} c
         LEFT JOIN {enrol} e ON c.id = e.courseid
         LEFT JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE c.visible = 1 AND c.id > 1
         GROUP BY c.id, c.fullname, c.visible
         ORDER BY enrolled_users DESC",
        []
    );
    
    if (!empty($enrollment_stats)) {
        echo "<table>";
        echo "<tr><th>Course Name</th><th>Total Enrollments</th><th>Active Enrollments</th></tr>";
        foreach ($enrollment_stats as $course) {
            echo "<tr class='counted'>";
            echo "<td><strong>{$course->fullname}</strong></td>";
            echo "<td>{$course->enrolled_users}</td>";
            echo "<td>{$course->active_enrollments}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 6. Course creation timeline
    echo "<div class='result'>";
    echo "<h3>📅 Course Creation Timeline</h3>";
    
    $recent_courses = $DB->get_records_sql(
        "SELECT 
            c.id,
            c.fullname,
            c.visible,
            FROM_UNIXTIME(c.timecreated) as created_date,
            CASE 
                WHEN c.id = 1 THEN 'Site Course'
                WHEN c.visible = 0 THEN 'Hidden'
                ELSE 'Counted'
            END as status
         FROM {course} c
         ORDER BY c.timecreated DESC
         LIMIT 10",
        []
    );
    
    if (!empty($recent_courses)) {
        echo "<table>";
        echo "<tr><th>Course Name</th><th>Created Date</th><th>Visible</th><th>Status</th></tr>";
        foreach ($recent_courses as $course) {
            $class = ($course->status == 'Counted') ? 'counted' : 'not-counted';
            echo "<tr class='$class'>";
            echo "<td><strong>{$course->fullname}</strong></td>";
            echo "<td>{$course->created_date}</td>";
            echo "<td>" . ($course->visible ? 'Yes' : 'No') . "</td>";
            echo "<td><strong>{$course->status}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 7. Verification summary
    echo "<div class='success'>";
    echo "<h3>✅ Course Count Verification Summary</h3>";
    echo "<p><strong>Your dashboard should show: $dashboard_courses courses</strong></p>";
    echo "<p>This count includes:</p>";
    echo "<ul>";
    echo "<li>✅ Visible courses (visible = 1)</li>";
    echo "<li>✅ Non-system courses (id > 1)</li>";
    echo "</ul>";
    echo "<p>This count excludes:</p>";
    echo "<ul>";
    echo "<li>❌ Hidden courses (visible = 0)</li>";
    echo "<li>❌ Site course (id = 1)</li>";
    echo "</ul>";
    echo "<p><strong>Breakdown:</strong> $visible_courses visible courses - $site_course site course = $dashboard_courses dashboard courses</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='issue'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>



