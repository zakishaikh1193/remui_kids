<?php
require_once('../../../config.php');
require_once('theme/remui_kids/lib.php');

global $USER;

// Test with current user or specify a user ID
$userid = $USER->id;

echo "=== TESTING DASHBOARD STATISTICS ===" . PHP_EOL;
echo "User ID: $userid" . PHP_EOL;
echo "User Name: " . fullname($USER) . PHP_EOL . PHP_EOL;

// Get the statistics using the theme function
$stats = theme_remui_kids_get_elementary_dashboard_stats($userid);

echo "=== DASHBOARD STATISTICS CARDS ===" . PHP_EOL;
echo "Courses Card: " . $stats['total_courses'] . PHP_EOL;
echo "Lessons Done Card: " . $stats['lessons_completed'] . PHP_EOL;
echo "Activities Done Card: " . $stats['activities_completed'] . PHP_EOL;
echo "Overall Progress Card: " . $stats['overall_progress'] . "%" . PHP_EOL . PHP_EOL;

echo "=== DETAILED BREAKDOWN ===" . PHP_EOL;
echo "Total Activities Available: " . $stats['total_activities'] . PHP_EOL;
echo "Activities Completed: " . $stats['activities_completed'] . PHP_EOL;
echo "Progress Calculation: (" . $stats['activities_completed'] . " / " . $stats['total_activities'] . ") * 100 = " . $stats['overall_progress'] . "%" . PHP_EOL . PHP_EOL;

// Test with different user IDs if needed
echo "=== TESTING WITH DIFFERENT USERS ===" . PHP_EOL;
$testusers = $DB->get_records_sql(
    "SELECT id, firstname, lastname FROM {user} WHERE deleted = 0 AND id > 1 LIMIT 5"
);

foreach ($testusers as $user) {
    $userstats = theme_remui_kids_get_elementary_dashboard_stats($user->id);
    echo "User: " . fullname($user) . " (ID: " . $user->id . ")" . PHP_EOL;
    echo "  Courses: " . $userstats['total_courses'] . PHP_EOL;
    echo "  Lessons Done: " . $userstats['lessons_completed'] . PHP_EOL;
    echo "  Activities Done: " . $userstats['activities_completed'] . PHP_EOL;
    echo "  Overall Progress: " . $userstats['overall_progress'] . "%" . PHP_EOL . PHP_EOL;
}

echo "=== JSON OUTPUT FOR AJAX ===" . PHP_EOL;
echo json_encode($stats, JSON_PRETTY_PRINT) . PHP_EOL;
?>
