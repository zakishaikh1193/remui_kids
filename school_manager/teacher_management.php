<?php
/**
 * School Manager Teacher Management Page
 * 
 * This file demonstrates how to create a school manager page with shared sidebar
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Check if user is logged in
require_login();

// Check if user has school manager capabilities
$context = context_system::instance();
$isschoolmanager = false;

// Check for school manager role
$schoolmanagerroles = $DB->get_records_sql(
    "SELECT DISTINCT r.shortname
     FROM {role} r
     JOIN {role_assignments} ra ON r.id = ra.roleid
     JOIN {context} ctx ON ra.contextid = ctx.id
     WHERE ra.userid = ?
     AND ctx.contextlevel = ?
     AND r.shortname IN ('school_manager', 'manager')",
    [$USER->id, CONTEXT_SYSTEM]
);

if (!empty($schoolmanagerroles)) {
    $isschoolmanager = true;
}

// Also check for school manager capabilities
if (!$isschoolmanager && (has_capability('moodle/site:config', $context, $USER) ||
                         has_capability('moodle/user:create', $context, $USER))) {
    $isschoolmanager = true;
}

// Redirect if not a school manager
if (!$isschoolmanager) {
    redirect(new moodle_url('/my/'));
}

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/teacher_management.php');
$PAGE->set_title('Teacher Management - School Manager');
$PAGE->set_heading('Teacher Management');

// Prepare template context
$templatecontext = [
    'teacher_management_active' => true, // This will make the Teacher Management menu item active
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

// Must be called before rendering the template
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');

// Render the teacher management template
echo $OUTPUT->render_from_template('theme_remui_kids/school_manager_teacher_management', $templatecontext);
