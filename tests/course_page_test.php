<?php
/**
 * Course Page Test
 * Test if the course page itself is causing issues
 */

require_once('../../../config.php');
require_login();

// Get course ID from URL
$courseid = optional_param('id', 0, PARAM_INT);

if (!$courseid) {
    echo "No course ID provided. Usage: ?id=COURSE_ID";
    exit;
}

// Get course record
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    echo "Course not found!";
    exit;
}

echo "<h2>Course Page Test</h2>";
echo "<p><strong>Course ID:</strong> " . $courseid . "</p>";
echo "<p><strong>Course Name:</strong> " . $course->fullname . "</p>";

// Test different approaches to access the course
echo "<h3>Test Methods:</h3>";

echo "<p><strong>Method 1 - Direct Course URL:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "' target='_blank'>Direct Course URL</a><br><br>";

echo "<p><strong>Method 2 - Course URL with Debug:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&debug=1' target='_blank'>Course URL with Debug</a><br><br>";

echo "<p><strong>Method 3 - Course URL with Admin:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&admin=1' target='_blank'>Course URL with Admin</a><br><br>";

echo "<p><strong>Method 4 - Course URL with Site Admin:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&siteadmin=1' target='_blank'>Course URL with Site Admin</a><br><br>";

echo "<p><strong>Method 5 - Course URL with No Cache:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&nocache=1' target='_blank'>Course URL with No Cache</a><br><br>";

// Check if there are any JavaScript conflicts
echo "<h3>JavaScript Test:</h3>";
echo "<button onclick=\"alert('JavaScript is working!')\">Test JavaScript</button><br><br>";

echo "<button onclick=\"window.location.href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "'\">JavaScript Redirect Test</button><br><br>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?>
