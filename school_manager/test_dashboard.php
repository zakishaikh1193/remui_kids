<?php
/**
 * Test School Manager Dashboard
 * Direct test page to verify the dashboard works
 */

require_once('../../../../config.php');
require_login();

global $USER, $DB, $OUTPUT, $CFG;

// Force school manager mode for testing
$templatecontext = [];
$templatecontext['custom_dashboard'] = true;
$templatecontext['dashboard_type'] = 'schoolmanager';
$templatecontext['school_manager_dashboard'] = true;
$templatecontext['school_manager_stats'] = [
    'students_count' => 150,
    'teachers_count' => 12,
    'courses_count' => 25,
    'active_students' => 120,
    'total_enrollments' => 300,
    'completion_rate' => 85.5
];
$templatecontext['department_name'] = 'Al-Faisaliah Islamic School';
$templatecontext['company_name'] = 'Al-Faisaliah Company';
$templatecontext['manager_name'] = fullname($USER);
$templatecontext['hello_message'] = "Hello " . $USER->firstname . "!";
$templatecontext['wwwroot'] = $CFG->wwwroot;

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/school_manager/test_dashboard.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Test School Manager Dashboard');

echo $OUTPUT->header();

// Render our custom school manager dashboard template
echo $OUTPUT->render_from_template('theme_remui_kids/school_manager_dashboard', $templatecontext);

echo $OUTPUT->footer();
?>

