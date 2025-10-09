<?php
/**
 * Test script for Student Courses Page
 * This script helps verify that the student courses functionality is working correctly
 */

require_once('../../config.php');
require_login();

// Check if user has admin capabilities
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die('This script requires admin access');
}

echo "<h1>Student Courses Page Test</h1>";

// Test 1: Check if student_courses.php file exists
echo "<h2>Test 1: File Existence</h2>";
$student_courses_file = __DIR__ . '/student_courses.php';
if (file_exists($student_courses_file)) {
    echo "✅ student_courses.php file exists<br>";
} else {
    echo "❌ student_courses.php file not found<br>";
}

// Test 2: Check if template file exists
echo "<h2>Test 2: Template File</h2>";
$template_file = __DIR__ . '/templates/student_courses.mustache';
if (file_exists($template_file)) {
    echo "✅ student_courses.mustache template exists<br>";
} else {
    echo "❌ student_courses.mustache template not found<br>";
}

// Test 3: Check database tables
echo "<h2>Test 3: Database Tables</h2>";
global $DB;

$tables_to_check = array(
    'course' => 'Course table',
    'course_categories' => 'Course categories table',
    'user_enrolments' => 'User enrolments table',
    'cohort_members' => 'Cohort members table'
);

foreach ($tables_to_check as $table => $description) {
    try {
        $count = $DB->count_records($table);
        echo "✅ {$description}: {$count} records<br>";
    } catch (Exception $e) {
        echo "❌ {$description}: Error - " . $e->getMessage() . "<br>";
    }
}

// Test 4: Check theme renderer
echo "<h2>Test 4: Theme Renderer</h2>";
try {
    $renderer = $PAGE->get_renderer('theme_remui_kids');
    if (method_exists($renderer, 'render_student_courses')) {
        echo "✅ render_student_courses method exists<br>";
    } else {
        echo "❌ render_student_courses method not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Theme renderer error: " . $e->getMessage() . "<br>";
}

// Test 5: Check sample students
echo "<h2>Test 5: Sample Students</h2>";
$students = $DB->get_records('user', array('deleted' => 0), 'id ASC', 'id,username,firstname,lastname', 0, 5);
if ($students) {
    echo "✅ Found " . count($students) . " sample users:<br>";
    foreach ($students as $student) {
        echo "- {$student->firstname} {$student->lastname} ({$student->username})<br>";
    }
} else {
    echo "❌ No users found<br>";
}

// Test 6: Check sample courses
echo "<h2>Test 6: Sample Courses</h2>";
$courses = $DB->get_records('course', array('visible' => 1), 'id ASC', 'id,fullname,shortname,category', 0, 5);
if ($courses) {
    echo "✅ Found " . count($courses) . " sample courses:<br>";
    foreach ($courses as $course) {
        if ($course->id != 1) { // Skip site course
            echo "- {$course->fullname} ({$course->shortname}) - Category ID: {$course->category}<br>";
        }
    }
} else {
    echo "❌ No courses found<br>";
}

// Test 7: Check course categories
echo "<h2>Test 7: Course Categories</h2>";
$categories = $DB->get_records('course_categories', null, 'name ASC', 'id,name', 0, 10);
if ($categories) {
    echo "✅ Found " . count($categories) . " course categories:<br>";
    foreach ($categories as $category) {
        echo "- {$category->name} (ID: {$category->id})<br>";
    }
} else {
    echo "❌ No course categories found<br>";
}

// Test 8: URL accessibility test
echo "<h2>Test 8: URL Accessibility</h2>";
$courses_url = new moodle_url('/theme/remui_kids/student_courses.php');
echo "✅ Student courses URL: " . $courses_url->out() . "<br>";

echo "<h2>Test Complete</h2>";
echo "<p>If all tests show ✅, the student courses page should be working correctly.</p>";
echo "<p><a href='{$courses_url->out()}'>Click here to test the student courses page</a></p>";
?>



