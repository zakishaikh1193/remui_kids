<?php
/**
 * Code Editor page for remui_kids theme
 * This page embeds the Judge0 IDE from the local ide-master directory
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
$PAGE->set_url('/theme/remui_kids/code_editor.php');
$PAGE->set_pagelayout('base'); // Use base layout for proper Moodle integration
$PAGE->set_title('Code Editor', false); // Remove site name from title
$PAGE->set_heading('Code Editor');

// Get sidebar context for current page
$sidebar_context = theme_remui_kids_get_elementary_sidebar_context('code_editor', $USER);

// Prepare template context
$templatecontext = [
    // Include sidebar context
    ...$sidebar_context,
    
    // Page-specific content
    'page_title' => 'Code Editor',
    'page_subtitle' => 'Professional code editor with Judge0 execution engine',
    'student_name' => $USER->firstname,
    'ide_base_url' => $CFG->wwwroot . '/theme/remui_kids/ide-master',
];

echo $OUTPUT->header();

// Include sidebar styles
echo theme_remui_kids_get_elementary_sidebar_styles();

// Render the page with sidebar
echo '<div class="main-content-with-sidebar">';
echo $OUTPUT->render_from_template('theme_remui_kids/code_editor_page', $templatecontext);
echo '</div>';

echo $OUTPUT->footer();
