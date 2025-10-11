<?php
/**
 * Example: How to use the Elementary Sidebar in your pages
 *
 * This file shows how to include the elementary sidebar in any page
 * for consistent navigation across all elementary pages.
 *
 * @package    theme_remui_kids
 * @copyright  2024 KodeIt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib/sidebar_helper.php');

require_login();

global $USER, $DB, $PAGE, $OUTPUT, $CFG;

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/examples/sidebar_usage_example.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title('Example Page with Sidebar', false);
$PAGE->set_heading('Example Page with Sidebar');

// Get sidebar context for current page
$sidebar_context = theme_remui_kids_get_elementary_sidebar_context('lessons', $USER);

// Prepare template context
$templatecontext = [
    // Include sidebar context
    ...$sidebar_context,
    
    // Your page-specific content
    'page_content' => 'This is an example page showing how to use the elementary sidebar.',
    'example_data' => [
        'title' => 'Example Page',
        'description' => 'This page demonstrates how to include the elementary sidebar.'
    ]
];

echo $OUTPUT->header();

// Include sidebar styles
echo theme_remui_kids_get_elementary_sidebar_styles();

// Render the page with sidebar
echo '<div class="main-content-with-sidebar">';
echo $OUTPUT->render_from_template('theme_remui_kids/example_page_with_sidebar', $templatecontext);
echo '</div>';

echo $OUTPUT->footer();

