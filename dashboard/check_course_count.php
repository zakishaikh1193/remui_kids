<?php
/**
 * Quick script to check current course count
 * Access via: http://your-site.com/iomad-test/check_course_count.php
 */

require_once('../../../config.php');

global $DB, $USER;

// Check if we're logged in
if (!isloggedin()) {
    die("Please log in first to check course count.");
}

echo "<h2>Current Course Count</h2>";

// Get current course count
$current_courses = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT c.id) 
     FROM {course} c 
     JOIN {enrol} e ON c.id = e.courseid 
     JOIN {user_enrolments} ue ON e.id = ue.enrolid 
     WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1",
    [$USER->id]
);

// Get course details
$courses = $DB->get_records_sql(
    "SELECT c.id, c.fullname, c.shortname, c.visible
     FROM {course} c 
     JOIN {enrol} e ON c.id = e.courseid 
     JOIN {user_enrolments} ue ON e.id = ue.enrolid 
     WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1
     ORDER BY c.fullname",
    [$USER->id]
);

echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 8px; border: 2px solid #28a745;'>";
echo "<h3>📊 Your Dashboard Statistics</h3>";
echo "<p><strong>Total Courses:</strong> <span style='font-size: 24px; color: #007cba; font-weight: bold;'>" . $current_courses . "</span></p>";
echo "<p><strong>Logged in as:</strong> " . fullname($USER) . "</p>";
echo "<p><strong>User ID:</strong> " . $USER->id . "</p>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>📚 Your Enrolled Courses</h3>";

if (empty($courses)) {
    echo "<p style='color: #6c757d;'>No courses found.</p>";
} else {
    echo "<ul style='list-style: none; padding: 0;'>";
    foreach ($courses as $course) {
        echo "<li style='background: white; margin: 5px 0; padding: 10px; border-radius: 5px; border-left: 4px solid #007cba;'>";
        echo "<strong>" . $course->fullname . "</strong><br>";
        echo "<small style='color: #6c757d;'>Short name: " . $course->shortname . " | ID: " . $course->id . "</small>";
        echo "</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 8px; border: 1px solid #ffeaa7;'>";
echo "<h3>🎯 What This Means for Your Dashboard</h3>";
echo "<p>The <strong>'Courses' card</strong> in your dashboard should display: <strong>" . $current_courses . "</strong></p>";
echo "<p>This number comes directly from the database and updates automatically when you add/remove courses.</p>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='" . $CFG->wwwroot . "/my/' style='background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>View Dashboard</a>";
echo "<a href='" . $CFG->wwwroot . "/course/index.php' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>View All Courses</a>";
echo "</div>";
?>

