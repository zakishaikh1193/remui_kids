<?php
// Test page to verify real data functions are working
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/pages/test_real_data.php');
$PAGE->set_title('Test Real Data Functions');
$PAGE->set_heading('Test Real Data Functions');

echo $OUTPUT->header();

echo '<h2>Testing Recent Student Activity Function</h2>';
$recent_activity = theme_remui_kids_get_recent_student_activity();
echo '<p>Found ' . count($recent_activity) . ' recent activities</p>';
if (!empty($recent_activity)) {
    echo '<ul>';
    foreach (array_slice($recent_activity, 0, 5) as $activity) {
        echo '<li>' . $activity['student_name'] . ' - ' . $activity['activity_name'] . ' (' . $activity['time'] . ')</li>';
    }
    echo '</ul>';
} else {
    echo '<p>No recent activity found</p>';
}

echo '<h2>Testing Top Courses Function</h2>';
$top_courses = theme_remui_kids_get_top_courses_by_enrollment(5);
echo '<p>Found ' . count($top_courses) . ' top courses</p>';
if (!empty($top_courses)) {
    echo '<ul>';
    foreach ($top_courses as $course) {
        echo '<li>' . $course['name'] . ' (' . $course['enrollment_count'] . ' students, ' . $course['element_count'] . ' elements)</li>';
    }
    echo '</ul>';
} else {
    echo '<p>No top courses found</p>';
}

echo $OUTPUT->footer();
?>
