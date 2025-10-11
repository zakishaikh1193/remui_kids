<?php
// Simple test page for Student Overview

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/theme/remui_kids/pages/test_student_overview.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Test Student Overview');
$PAGE->set_heading('Test Student Overview');

// Include CSS
$PAGE->requires->css('/theme/remui_kids/style/student_overview.css');

echo $OUTPUT->header();

echo html_writer::start_div('student-overview-wrapper');
echo html_writer::tag('h1', 'Test Student Overview Page', ['style' => 'color: #2d3748; margin-bottom: 2rem;']);

// Test basic structure
echo html_writer::start_div('overview-header');
echo html_writer::img('/user/pix.php/' . $USER->id . '/f1.jpg', 'Test User', ['class' => 'student-avatar']);
echo html_writer::tag('h1', fullname($USER), ['class' => 'student-name']);
echo html_writer::tag('p', $USER->email, ['class' => 'student-submeta']);
echo html_writer::end_div();

// Test cards
echo html_writer::start_div('overview-grid');
echo html_writer::start_div('card simple');
echo html_writer::tag('div', 'Test Card', ['class' => 'card-title']);
echo html_writer::tag('div', 'This is a test card to verify CSS is working', ['class' => 'card-body']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo $OUTPUT->footer();
?>


