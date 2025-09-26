<?php
require_once('../../../config.php');
require_once('theme/remui_kids/lib.php');

global $USER, $DB;

echo "=== DASHBOARD CARDS DATA TEST ===" . PHP_EOL;
echo "Current User: " . fullname($USER) . " (ID: " . $USER->id . ")" . PHP_EOL . PHP_EOL;

// Get the dashboard statistics using the same function as the dashboard
$stats = theme_remui_kids_get_elementary_dashboard_stats($USER->id);

echo "=== DASHBOARD STATISTICS CARDS ===" . PHP_EOL;
echo "┌─────────────────────────────────┐" . PHP_EOL;
echo "│  Courses Card: " . str_pad($stats['total_courses'], 3) . "        │" . PHP_EOL;
echo "│  Lessons Done Card: " . str_pad($stats['lessons_completed'], 3) . "    │" . PHP_EOL;
echo "│  Activities Done Card: " . str_pad($stats['activities_completed'], 3) . " │" . PHP_EOL;
echo "│  Overall Progress Card: " . str_pad($stats['overall_progress'] . "%", 3) . " │" . PHP_EOL;
echo "└─────────────────────────────────┘" . PHP_EOL . PHP_EOL;

echo "=== DETAILED BREAKDOWN ===" . PHP_EOL;
echo "Total Courses Enrolled: " . $stats['total_courses'] . PHP_EOL;
echo "Lessons Completed: " . $stats['lessons_completed'] . PHP_EOL;
echo "Activities Completed: " . $stats['activities_completed'] . PHP_EOL;
echo "Total Activities Available: " . $stats['total_activities'] . PHP_EOL;
echo "Overall Progress: " . $stats['overall_progress'] . "%" . PHP_EOL . PHP_EOL;

// Show the actual SQL queries being used
echo "=== SQL QUERIES USED ===" . PHP_EOL;
echo "1. Courses Count Query:" . PHP_EOL;
echo "   SELECT COUNT(DISTINCT c.id) FROM mdl_course c JOIN mdl_enrol e ON c.id = e.courseid JOIN mdl_user_enrolments ue ON e.id = ue.enrolid WHERE ue.userid = " . $USER->id . " AND c.visible = 1 AND c.id > 1" . PHP_EOL . PHP_EOL;

echo "2. Lessons Completed Query:" . PHP_EOL;
echo "   SELECT COUNT(DISTINCT cmc.coursemoduleid) FROM mdl_course_modules_completion cmc JOIN mdl_course_modules cm ON cmc.coursemoduleid = cm.id JOIN mdl_course c ON cm.course = c.id WHERE cmc.userid = " . $USER->id . " AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1" . PHP_EOL . PHP_EOL;

echo "3. Activities Completed Query:" . PHP_EOL;
echo "   SELECT COUNT(*) FROM mdl_course_modules_completion cmc JOIN mdl_course_modules cm ON cmc.coursemoduleid = cm.id JOIN mdl_course c ON cm.course = c.id WHERE cmc.userid = " . $USER->id . " AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1" . PHP_EOL . PHP_EOL;

// Test with different users
echo "=== TESTING WITH DIFFERENT USERS ===" . PHP_EOL;
$users = $DB->get_records_sql(
    "SELECT id, firstname, lastname FROM {user} WHERE deleted = 0 AND id > 1 LIMIT 5",
    []
);

foreach ($users as $user) {
    $userstats = theme_remui_kids_get_elementary_dashboard_stats($user->id);
    echo "User: " . fullname($user) . " (ID: " . $user->id . ")" . PHP_EOL;
    echo "  Courses: " . $userstats['total_courses'] . " | Lessons: " . $userstats['lessons_completed'] . " | Activities: " . $userstats['activities_completed'] . " | Progress: " . $userstats['overall_progress'] . "%" . PHP_EOL;
}

echo PHP_EOL . "=== JSON OUTPUT FOR AJAX ===" . PHP_EOL;
echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;
?>
