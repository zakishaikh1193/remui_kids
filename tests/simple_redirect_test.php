<?php
/**
 * Simple Redirect Test
 * Test basic course redirect functionality
 */

require_once('../../../config.php');
require_login();

// Get course ID from URL
$courseid = optional_param('id', 0, PARAM_INT);

if (!$courseid) {
    echo "No course ID provided. Usage: ?id=COURSE_ID";
    exit;
}

echo "<h2>Simple Redirect Test</h2>";
echo "<p><strong>Course ID:</strong> " . $courseid . "</p>";
echo "<p><strong>Moodle URL:</strong> " . $CFG->wwwroot . "</p>";

// Get course record
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Course not found!</p>";
    exit;
}

echo "<p><strong>Course Name:</strong> " . $course->fullname . "</p>";
echo "<p><strong>Course Visible:</strong> " . ($course->visible ? 'YES' : 'NO') . "</p>";

// Check IOMAD access
if (class_exists('iomad')) {
    $iomad_check = iomad::iomad_check_course($courseid);
    echo "<p><strong>IOMAD Check:</strong> " . ($iomad_check ? 'ALLOWED' : 'BLOCKED') . "</p>";
    
    if (!$iomad_check) {
        echo "<p style='color: red;'><strong>IOMAD BLOCKING ACCESS!</strong> This is likely the cause of the loading issue.</p>";
    }
}

// Check course access
$context = context_course::instance($courseid);
$can_access = can_access_course($course, $USER);
echo "<p><strong>Can Access Course:</strong> " . ($can_access ? 'YES' : 'NO') . "</p>";

if (!$can_access) {
    echo "<p style='color: red;'><strong>COURSE ACCESS DENIED!</strong> This is the cause of the loading issue.</p>";
}

// Test different redirect methods
echo "<h3>Test Methods:</h3>";

echo "<p><strong>Method 1 - Direct Link:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "' target='_blank'>Direct Link (New Tab)</a><br><br>";

echo "<p><strong>Method 2 - JavaScript Redirect:</strong></p>";
echo "<button onclick=\"window.location.href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "'\">JavaScript Redirect</button><br><br>";

echo "<p><strong>Method 3 - PHP Redirect:</strong></p>";
echo "<button onclick=\"window.location.href='?redirect=1&id=" . $courseid . "'\">PHP Redirect</button><br><br>";

echo "<p><strong>Method 4 - Admin Bypass:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&admin=1&sesskey=" . sesskey() . "' target='_blank'>Admin Bypass (New Tab)</a><br><br>";

echo "<p><strong>Method 5 - Site Admin Bypass:</strong></p>";
echo "<a href='" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "&siteadmin=1' target='_blank'>Site Admin Bypass (New Tab)</a><br><br>";

// Handle PHP redirect
if (isset($_GET['redirect'])) {
    echo "<p>Redirecting via PHP...</p>";
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?>
