<?php
/**
 * Script to set a specific course count in the dashboard
 * This will override the database query result
 */

require_once('../../../config.php');

// Configuration - Change this number to what you want to display
$DESIRED_COURSE_COUNT = 8; // Change this to any number you want

echo "=== SETTING COURSE COUNT TO: $DESIRED_COURSE_COUNT ===" . PHP_EOL;

// Read the current theme file
$theme_file = 'theme/remui_kids/lib.php';
$content = file_get_contents($theme_file);

// Find the line with the override comment
$search_line = '// $totalcourses = 8; // Set to any number you want';
$replace_line = '$totalcourses = ' . $DESIRED_COURSE_COUNT . '; // Set to any number you want';

// Replace the commented line with the active line
$new_content = str_replace($search_line, $replace_line, $content);

// Write the updated content back to the file
if (file_put_contents($theme_file, $new_content)) {
    echo "✅ Successfully updated course count to: $DESIRED_COURSE_COUNT" . PHP_EOL;
    echo "📁 File updated: $theme_file" . PHP_EOL;
    echo "🔄 Please refresh your dashboard to see the changes" . PHP_EOL;
} else {
    echo "❌ Error updating the file" . PHP_EOL;
}

echo PHP_EOL . "=== TO REVERT TO DATABASE COUNT ===" . PHP_EOL;
echo "Change line 592 in theme/remui_kids/lib.php from:" . PHP_EOL;
echo '$totalcourses = ' . $DESIRED_COURSE_COUNT . ';' . PHP_EOL;
echo "to:" . PHP_EOL;
echo '// $totalcourses = ' . $DESIRED_COURSE_COUNT . ';' . PHP_EOL;
echo "(Add // at the beginning to comment it out)" . PHP_EOL;
?>
