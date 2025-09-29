<?php
/**
 * Course Access Diagnostic
 * Comprehensive test to identify course access issues
 */

require_once('../../../config.php');
require_login();

// Get course ID from URL
$courseid = optional_param('id', 0, PARAM_INT);

if (!$courseid) {
    echo "No course ID provided. Usage: ?id=COURSE_ID";
    exit;
}

echo "<h2>Course Access Diagnostic</h2>";
echo "<p><strong>Course ID:</strong> " . $courseid . "</p>";

// Get course record
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Course not found!</p>";
    exit;
}

echo "<p><strong>Course Name:</strong> " . $course->fullname . "</p>";
echo "<p><strong>Course Visible:</strong> " . ($course->visible ? 'YES' : 'NO') . "</p>";

// Check user info
echo "<h3>User Information:</h3>";
echo "<p><strong>User ID:</strong> " . $USER->id . "</p>";
echo "<p><strong>Username:</strong> " . $USER->username . "</p>";
echo "<p><strong>Is Site Admin:</strong> " . (is_siteadmin($USER->id) ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Is Logged In:</strong> " . (isloggedin() ? 'YES' : 'NO') . "</p>";

// Check IOMAD access
echo "<h3>IOMAD Access Check:</h3>";
if (class_exists('iomad')) {
    $iomad_check = iomad::iomad_check_course($courseid);
    echo "<p><strong>IOMAD Check:</strong> " . ($iomad_check ? 'ALLOWED' : 'BLOCKED') . "</p>";
    
    if (!$iomad_check) {
        echo "<p style='color: red;'><strong>IOMAD BLOCKING ACCESS!</strong></p>";
    }
    
    // Check company status
    $companyid = iomad::get_my_companyid(context_system::instance());
    echo "<p><strong>Company ID:</strong> " . ($companyid ? $companyid : 'None') . "</p>";
} else {
    echo "<p><strong>IOMAD:</strong> Not available</p>";
}

// Check course access
echo "<h3>Course Access Check:</h3>";
$context = context_course::instance($courseid);
$can_access = can_access_course($course, $USER);
echo "<p><strong>Can Access Course:</strong> " . ($can_access ? 'YES' : 'NO') . "</p>";

if (!$can_access) {
    echo "<p style='color: red;'><strong>COURSE ACCESS DENIED!</strong></p>";
}

// Check capabilities
$can_view = has_capability('moodle/course:view', $context);
echo "<p><strong>Can View Course:</strong> " . ($can_view ? 'YES' : 'NO') . "</p>";

// Test different URL approaches
echo "<h3>URL Tests:</h3>";

$base_url = $CFG->wwwroot;
echo "<p><strong>Base URL:</strong> " . $base_url . "</p>";

// Test 1: Basic course URL
$url1 = $base_url . '/course/view.php?id=' . $courseid;
echo "<p><strong>Test 1 - Basic URL:</strong> <a href='" . $url1 . "' target='_blank'>" . $url1 . "</a></p>";

// Test 2: Course URL with session key
$url2 = $base_url . '/course/view.php?id=' . $courseid . '&sesskey=' . sesskey();
echo "<p><strong>Test 2 - With Session Key:</strong> <a href='" . $url2 . "' target='_blank'>" . $url2 . "</a></p>";

// Test 3: Course URL with admin bypass
$url3 = $base_url . '/course/view.php?id=' . $courseid . '&admin=1';
echo "<p><strong>Test 3 - With Admin:</strong> <a href='" . $url3 . "' target='_blank'>" . $url3 . "</a></p>";

// Test 4: Course URL with site admin bypass
$url4 = $base_url . '/course/view.php?id=' . $courseid . '&siteadmin=1';
echo "<p><strong>Test 4 - With Site Admin:</strong> <a href='" . $url4 . "' target='_blank'>" . $url4 . "</a></p>";

// Test 5: Course URL with debug
$url5 = $base_url . '/course/view.php?id=' . $courseid . '&debug=1';
echo "<p><strong>Test 5 - With Debug:</strong> <a href='" . $url5 . "' target='_blank'>" . $url5 . "</a></p>";

// Test 6: Course URL with no cache
$url6 = $base_url . '/course/view.php?id=' . $courseid . '&nocache=1';
echo "<p><strong>Test 6 - With No Cache:</strong> <a href='" . $url6 . "' target='_blank'>" . $url6 . "</a></p>";

// Test 7: Course URL with all parameters
$url7 = $base_url . '/course/view.php?id=' . $courseid . '&sesskey=' . sesskey() . '&admin=1&siteadmin=1&debug=1&nocache=1';
echo "<p><strong>Test 7 - All Parameters:</strong> <a href='" . $url7 . "' target='_blank'>" . $url7 . "</a></p>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?>


