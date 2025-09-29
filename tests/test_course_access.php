<?php
/**
 * Test Course Access
 * Simple test to check if course access is working
 */

require_once('../../../config.php');
require_login();

// Get course ID from URL
$courseid = optional_param('id', 0, PARAM_INT);

if (!$courseid) {
    echo "No course ID provided";
    exit;
}

// Get course record
$course = $DB->get_record('course', ['id' => $courseid]);

if (!$course) {
    echo "Course not found";
    exit;
}

echo "<h2>Course Access Test</h2>";
echo "<p><strong>Course ID:</strong> " . $course->id . "</p>";
echo "<p><strong>Course Name:</strong> " . $course->fullname . "</p>";
echo "<p><strong>Course Shortname:</strong> " . $course->shortname . "</p>";
echo "<p><strong>Course Visible:</strong> " . ($course->visible ? 'YES' : 'NO') . "</p>";

// Check IOMAD access
if (class_exists('iomad')) {
    $iomad_check = iomad::iomad_check_course($courseid);
    echo "<p><strong>IOMAD Check:</strong> " . ($iomad_check ? 'ALLOWED' : 'BLOCKED') . "</p>";
}

// Check if user can access course
$context = context_course::instance($courseid);
$can_access = can_access_course($course, $USER);

echo "<p><strong>Can Access Course:</strong> " . ($can_access ? 'YES' : 'NO') . "</p>";

// Check capabilities
$can_view = has_capability('moodle/course:view', $context);
echo "<p><strong>Can View Course:</strong> " . ($can_view ? 'YES' : 'NO') . "</p>";

// Try to get course URL
$course_url = new moodle_url('/course/view.php', ['id' => $courseid]);
echo "<p><strong>Course URL:</strong> <a href='" . $course_url . "' target='_blank'>" . $course_url . "</a></p>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?>



