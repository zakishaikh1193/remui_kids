<?php
/**
 * School Manager - Assign Teachers
 * Assign teachers to courses
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();

global $USER, $DB;

$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager assign teachers page');
}

$department = $DB->get_record('department', ['id' => $company_user->departmentid]);

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/assign_teachers.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Assign Teachers');
$PAGE->set_heading('Assign Teachers');

echo $OUTPUT->header();
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
?>

<div style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
    <div style="background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <div style="font-size: 4rem; color: #667eea; margin-bottom: 1rem;">
            <i class="fa fa-user-plus"></i>
        </div>
        <h1 style="font-size: 2rem; color: #2c3e50; margin-bottom: 1rem;">Assign Teachers</h1>
        <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 2rem;">
            Coming soon! Assign teachers to courses in your department.
        </p>
        <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/teachers.php" 
           style="padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
            <i class="fa fa-chalkboard-teacher"></i> View Teachers
        </a>
    </div>
</div>

<?php
echo '</div>';
echo $OUTPUT->footer();
?>


