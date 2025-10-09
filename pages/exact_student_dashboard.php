<?php
// Exact Student Dashboard - Same UI as shown in image

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

require_login();

$studentid = optional_param('id', $USER->id, PARAM_INT);
$student = core_user::get_user($studentid, '*', MUST_EXIST);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/theme/remui_kids/pages/exact_student_dashboard.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Student Dashboard - ' . fullname($student));
$PAGE->set_heading('Student Dashboard');

// Include exact dashboard CSS with cache busting
$PAGE->requires->css('/theme/remui_kids/style/exact_student_dashboard.css?v=' . time());

// Fetch exact dashboard data
$dashboard_data = theme_remui_kids_get_exact_student_dashboard($student->id);

// Template context
$templatecontext = array_merge([
    'student' => [
        'id' => $student->id,
        'fullname' => fullname($student),
        'firstname' => $student->firstname,
        'lastname' => $student->lastname,
        'email' => $student->email,
        'avatar_url' => (new moodle_url('/user/pix.php/' . $student->id . '/f1.jpg'))->out(),
        'profile_url' => (new moodle_url('/user/profile.php', ['id' => $student->id]))->out()
    ]
], $dashboard_data);

echo $OUTPUT->header();

// Simple layout matching the image exactly
echo html_writer::start_div('', ['style' => 'padding: 20px; background: #f8fafc; min-height: 100vh; font-family: Arial, sans-serif;']);

// Student profile section
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 20px; margin-bottom: 30px;']);
echo html_writer::img($templatecontext['student']['avatar_url'], 'Profile', ['style' => 'width: 80px; height: 80px; border-radius: 50%; object-fit: cover;']);
echo html_writer::start_div('');
echo html_writer::tag('h1', fullname($student), ['style' => 'margin: 0; font-size: 24px; color: #2d3748;']);
echo html_writer::tag('span', 'Message', ['style' => 'color: #6b7280; font-size: 14px; margin-left: 10px;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Status messages
echo html_writer::div('Student Overview Page Loading...', 'alert alert-info', ['style' => 'background: #dbeafe; color: #1e40af; padding: 12px; border-radius: 8px; margin-bottom: 10px;']);
echo html_writer::div('Student: ' . fullname($student), 'alert alert-success', ['style' => 'background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 10px;']);
echo html_writer::div('Data loaded: Yes', 'alert alert-warning', ['style' => 'background: #fef3c7; color: #d97706; padding: 12px; border-radius: 8px; margin-bottom: 30px;']);

// Main content
echo html_writer::tag('h1', 'Student Overview', ['style' => 'text-align: center; color: #2d3748; margin-bottom: 30px; font-size: 28px;']);

// Student Information
echo html_writer::start_div('', ['style' => 'background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('h2', 'Student Information', ['style' => 'color: #2d3748; margin-bottom: 15px;']);
echo html_writer::tag('p', 'Name: ' . fullname($student), ['style' => 'margin: 5px 0;']);
echo html_writer::tag('p', 'Email: ' . $student->email, ['style' => 'margin: 5px 0;']);
echo html_writer::tag('p', 'ID: ' . $student->id, ['style' => 'margin: 5px 0;']);
echo html_writer::end_div();

// Statistics
echo html_writer::start_div('', ['style' => 'background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('h2', 'Statistics', ['style' => 'color: #2d3748; margin-bottom: 15px;']);
echo html_writer::tag('p', 'Total Courses: ' . $dashboard_data['overview_counts']['total_courses'], ['style' => 'margin: 5px 0;']);
echo html_writer::tag('p', 'Completed Courses: ' . $dashboard_data['overview_counts']['completed_courses'], ['style' => 'margin: 5px 0;']);
echo html_writer::tag('p', 'Hours Spent: ' . $dashboard_data['overview_counts']['hours_spent'], ['style' => 'margin: 5px 0;']);
echo html_writer::tag('p', 'Overall Performance: ' . $dashboard_data['overall']['percent'] . '%', ['style' => 'margin: 5px 0;']);
echo html_writer::end_div();

echo html_writer::end_div();

echo $OUTPUT->footer();
?>
